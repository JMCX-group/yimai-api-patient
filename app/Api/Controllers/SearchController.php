<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/21
 * Time: 上午9:39
 */

namespace App\Api\Controllers;

use App\Api\Requests\SearchUserRequest;
use App\Api\Transformers\Transformer;
use App\Appointment;
use App\Doctor;
use App\User;

class SearchController extends BaseController
{
    /**
     * 默认进入搜索页面获取的数据
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function index()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        /**
         * 因为没有标签选择，暂时屏蔽：
         */
//        if (!empty($user->tag_list)) {
//            $tag = json_decode($user->tag_list, true);
//            $data = User::defaultInfo($tag['tag_list']);
            $data = User::newDefaultInfo();

            foreach ($data as &$item) {
                $item = Transformer::searchDoctorTransform($item, $user->id);
            }

            return response()->json(compact('data'));
//        } else {
//            return response()->json(['message' => '标签信息未填写'], 400);
//        }
    }

    /**
     * @param SearchUserRequest $request
     * @return mixed
     */
    public function searchUser_admissions(SearchUserRequest $request)
    {
        return $this->searchUser($request, 'admissions');
    }

    /**
     * @param SearchUserRequest $request
     * @return mixed
     */
    public function searchUser_sameHospital(SearchUserRequest $request)
    {
        return $this->searchUser($request, 'same_hospital');
    }

    /**
     * @param SearchUserRequest $request
     * @return mixed
     */
    public function searchUser_sameDept(SearchUserRequest $request)
    {
        return $this->searchUser($request, 'same_department');
    }

    /**
     * @param SearchUserRequest $request
     * @return mixed
     */
    public function searchUser_sameCollege(SearchUserRequest $request)
    {
        return $this->searchUser($request, 'same_college');
    }

    /**
     * Search for doctors.
     * Order by.
     *
     * @param SearchUserRequest $request
     * @param null $type
     * @return array
     */
    public function searchUser(SearchUserRequest $request, $type = null)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        /**
         * 获取前台传参:
         * 兼容不同形式的……蛋疼:
         */
        if (isset($request['city']) && !empty($request['city'])) {
            $cityID = $request['city'];
        } elseif (isset($request['city_id']) && !empty($request['city_id'])) {
            $cityID = $request['city_id'];
        } else {
            $cityID = false;
        }
        if (isset($request['hospital']) && !empty($request['hospital'])) {
            $hospitalID = $request['hospital'];
        } elseif (isset($request['hospital_id']) && !empty($request['hospital_id'])) {
            $hospitalID = $request['hospital_id'];
        } else {
            $hospitalID = false;
        }
        if (isset($request['department']) && !empty($request['department'])) {
            $deptID = $request['department'];
        } elseif (isset($request['dept_id']) && !empty($request['dept_id'])) {
            $deptID = $request['dept_id'];
        } else {
            $deptID = false;
        }
        if (isset($request['job_title']) && !empty($request['job_title'])) {
            $jobTitle = $request['job_title'];
        } else {
            $jobTitle = false;
        }
        $data = [
            'field' => isset($request['field']) && !empty($request['field']) ? $request['field'] : false,
            'city_id' => $cityID,
            'hospital_id' => $hospitalID,
            'dept_id' => $deptID,
            'title' => $jobTitle
        ];

        /**
         * 获取基础数据: 符合条件的所有医生数据.
         *
         * type参数 ( false: 不传或传空的默认值) :
         * same_hospital: 医脉处进行同医院搜索;
         *
         */
//        $searchType = (($type == null || $type == '') && isset($request['type']) && !empty($request['type'])) ? $request['type'] : $type;
//
//        switch ($searchType) {
//            case 'admissions':
//                $users = User::searchDoctor_admissions($data['field'], $user->city_id);
//                break;
//            case 'same_hospital':
//                $users = User::searchDoctor_sameHospital($data['field'], $user->hospital_id, $data['city_id'], $data['dept_id']);
//                break;
//            case 'same_department':
//                $deptIdList = DeptStandard::getSameFirstLevelDeptIdList($user->dept_id);
//                $users = User::searchDoctor_sameDept($data['field'], $deptIdList, $data['city_id'], $data['hospital_id']);
//                break;
//            case 'same_college':
//                $users = User::searchDoctor_sameCollege($data['field'], $user->college_id, $data['city_id'], $data['hospital_id'], $data['dept_id']);
//                break;
//            default:
        $users = Doctor::searchDoctor($data['field'], $data['city_id'], $data['hospital_id'], $data['dept_id'], $data['title']);
//                break;
//        }

        /**
         * 分组:
         */
        $provinces = array();
        $citys = array();
        $hospitals = array();
        $departments = array();
        $cityIdList = array();
        $provinceIdList = array();
        $hospitalIdList = array();
        $departmentIdList = array();

        $groupByNameArr = array();
        $groupByHospitalArr = array();
        $groupByDeptArr = array();
        $groupByTagArr = array();
        $otherArr = array();

        foreach ($users as $userItem) {
            $this->groupByCitys($userItem, $citys, $cityIdList);
            $this->groupByProvinces($userItem, $provinces, $provinceIdList);
            $this->groupByHospitals($userItem, $hospitals, $hospitalIdList);
            $this->groupByDepartments($userItem, $departments, $departmentIdList);

            if (strstr($userItem->name, $data['field'])) {
                array_push($groupByNameArr, Transformer::searchDoctorTransform($userItem, $user->id));
                continue;
            }

            if (strstr($userItem->hospital, $data['field'])) {
                array_push($groupByHospitalArr, Transformer::searchDoctorTransform($userItem, $user->id));
                continue;
            }

            if (strstr($userItem->dept, $data['field'])) {
                array_push($groupByDeptArr, Transformer::searchDoctorTransform($userItem, $user->id));
                continue;
            }

            if (strstr($userItem->tag_list, $data['field'])) {
                array_push($groupByTagArr, Transformer::searchDoctorTransform($userItem, $user->id));
                continue;
            }

            array_push($otherArr, Transformer::searchDoctorTransform($userItem, $user->id));
        }

        /**
         * 把医院数据格式特殊处理:
         */
        if (isset($request['format']) && $request['format'] == 'android') {
            $newHospital = array();
            foreach ($hospitals as $key => $val) {
                $newCityList = [
                    'province_id' => $key,
                    'data' => []
                ];
                foreach ($val as $keyItem => $valItem) {
                    $newHospitalList = [
                        'city_id' => $keyItem,
                        'data' => $valItem
                    ];
                    array_push($newCityList['data'], $newHospitalList);
                }
                array_push($newHospital, $newCityList);
            }
        }

        /**
         * 只有普通搜索有分组:
         */
//        if ($request['type'] == 'same_hospital' || $request['type'] == 'same_department' || $request['type'] == 'same_college' || ($type != null && $type != 'admissions')) {
//            $retData = array_merge($groupByNameArr, $friendArr, $sameCityArr, $b_s_g_threeA, $otherArr);
//
//            return [
//                'provinces' => $provinces,
//                'citys' => $citys,
//                'hospitals' => isset($newHospital) ? $newHospital : $hospitals,
//                'departments' => $departments,
//                'count' => count($retData),
//                'users' => $retData
//            ];
//        } else {
//            if ($type == 'admissions') {
//                $retDataFriends = array_merge($groupByNameArr, $friendArr);
//                $retDataFriendsFriends = $friendsFriendsArr;
//                $retDataOther = array();
//            }

        return [
            'provinces' => $provinces,
            'citys' => $citys,
            'hospitals' => isset($newHospital) ? $newHospital : $hospitals,
            'departments' => $departments,
            'count' => (count($groupByNameArr) + count($groupByHospitalArr) + count($groupByDeptArr) + count($groupByTagArr) + count($otherArr)),
            'users' => [
                'name' => $groupByNameArr,
                'hospital' => $groupByHospitalArr,
                'dept' => $groupByDeptArr,
                'tag' => $groupByTagArr,
                'other' => $otherArr
            ]
        ];
//        }
    }

    /**
     * 将城市按省分组
     *
     * @param $userItem
     * @param $citys
     * @param $cityIdList
     */
    public function groupByCitys($userItem, &$citys, &$cityIdList)
    {
        if (!in_array($userItem->city_id, $cityIdList)) {
            array_push($cityIdList, $userItem->city_id);
            if (isset($citys[$userItem->province_id])) {
                array_push(
                    $citys[$userItem->province_id],
                    ['id' => $userItem->city_id, 'name' => $userItem->city]
                );
            } else {
                $citys[$userItem->province_id] = [
                    ['id' => $userItem->city_id, 'name' => $userItem->city]
                ];
            }
        }
    }

    /**
     * @param $userItem
     * @param $provinces
     * @param $provinceIdList
     */
    public function groupByProvinces($userItem, &$provinces, &$provinceIdList)
    {
        if ($userItem->province_id && !in_array($userItem->province_id, $provinceIdList)) {
            array_push($provinceIdList, $userItem->province_id);
            array_push(
                $provinces,
                ['id' => $userItem->province_id, 'name' => $userItem->province]
            );
        }
    }

    /**
     * 将医院按省和市层级分组
     *
     * @param $userItem
     * @param $hospitals
     * @param $hospitalIdList
     */
    public function groupByHospitals($userItem, &$hospitals, &$hospitalIdList)
    {
        if (!in_array($userItem->hospital_id, $hospitalIdList)) {
            array_push($hospitalIdList, $userItem->hospital_id);

            if (isset($hospitals[$userItem->province_id]) && isset($hospitals[$userItem->province_id][$userItem->city_id])) {
                array_push(
                    $hospitals[$userItem->province_id][$userItem->city_id],
                    ['id' => $userItem->hospital_id, 'name' => $userItem->hospital,
                        'province_id' => $userItem->province_id, 'city_id' => $userItem->city_id]
                );
            } else {
                $hospitals[$userItem->province_id][$userItem->city_id] = [
                    ['id' => $userItem->hospital_id, 'name' => $userItem->hospital,
                        'province_id' => $userItem->province_id, 'city_id' => $userItem->city_id]
                ];
            }
        }
    }

    /**
     * @param $userItem
     * @param $departments
     * @param $departmentIdList
     */
    public function groupByDepartments($userItem, &$departments, &$departmentIdList)
    {
        if (!in_array($userItem->dept_id, $departmentIdList)) {
            array_push($departmentIdList, $userItem->dept_id);
            array_push(
                $departments,
                ['id' => $userItem->dept_id, 'name' => $userItem->dept]
            );
        }
    }

    /**
     * 查看其他医生的主页所需的信息
     *
     * @param $id
     * @return array|mixed
     */
    public function findDoctor($id)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $doctor = Doctor::findDoctor($id);

        $data = Transformer::searchDoctorTransform($doctor, $user->id);

        return response()->json(compact('data'));
    }

    /**
     * 首页点击"约我的医生"
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function findMyDoctor()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $doctorIdList = Appointment::getMyDoctors($user->id);
        $doctors = Doctor::findDoctors($doctorIdList);
        $data = array();
        foreach ($doctors as $doctor) {
            array_push($data, Transformer::searchDoctorTransform($doctor, $user->id));
        }

        return response()->json(compact('data'));
    }

    /**
     * 通过手机号或医脉码查看其他医生的信息
     *
     * @param $getData
     * @return array|mixed
     */
    public function findDoctor_byPhoneOrCode($getData)
    {
        $doctor = Doctor::findDoctor_byPhone($getData);
        if (isset($doctor['id']) && $doctor['id'] != '' && $doctor['id'] != null) {
            $data =  Transformer::findDoctorTransform($doctor);
            return response()->json(compact('data'));
        } else {
            $doctor = Doctor::findDoctor_byCode($getData);
            if (isset($doctor['id']) && $doctor['id'] != '' && $doctor['id'] != null) {
                $data = Transformer::findDoctorTransform($doctor);
                return response()->json(compact('data'));
            }

            return response()->json(['success' => ''], 204); //给肠媳适配。。
        }
    }
}
