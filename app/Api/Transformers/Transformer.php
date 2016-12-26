<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/18
 * Time: 下午4:08
 */

namespace App\Api\Transformers;

use App\Appointment;
use App\DeptStandard;
use App\Hospital;

class Transformer
{
    /**
     * Transform user list.
     *
     * @param $users
     * @return array
     */
    public static function userListTransform($users)
    {
        $hospitalIdList = array();
        $deptIdList = array();
        $newUsers = array();

        foreach ($users as $user) {
            array_push($hospitalIdList, $user->hospital_id);
            array_push($deptIdList, $user->dept_id);

            array_push($newUsers, self::userTransform($user));
        }

        return [
            'friends' => self::idToName($newUsers, $hospitalIdList, $deptIdList),
            'hospital_count' => count(array_unique($hospitalIdList))
        ];
    }

    /**
     * Transform users.
     * @param $users
     * @return mixed
     */
    public static function usersTransform($users)
    {
        $hospitalIdList = array();
        $deptIdList = array();
        $newUsers = array();

        foreach ($users as $user) {
            array_push($hospitalIdList, $user->hospital_id);
            array_push($deptIdList, $user->dept_id);

            array_push($newUsers, self::userTransform($user));
        }

        return self::idToIdName($newUsers, $hospitalIdList, $deptIdList);
    }

    /**
     * Transform user.
     *
     * @param $user
     * @return array
     */
    public static function userTransform($user)
    {
        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'head_url' => ($user->avatar == '') ? null : $user->avatar,
            'hospital' => $user['hospital_id'],
            'department' => $user['dept_id'],
            'job_title' => $user['title']
        ];
    }

    /**
     * Transform contacts.
     *
     * @param $user
     * @return array
     */
    public static function contactsTransform($user)
    {
        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'head_url' => ($user->avatar == '') ? null : $user->avatar,
            'department' => $user['dept_id'],
            'is_auth' => $user['auth']
        ];
    }

    /**
     * @param $user
     * @param $myId
     * @return array
     */
    public static function searchDoctorTransform($user, $myId)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'head_url' => ($user->avatar == '') ? null : $user->avatar,
            'job_title' => $user->title,
            'province' => [
                'id' => $user->province_id,
                'name' => $user->province,
            ],
            'city' => [
                'id' => $user->city_id,
                'name' => $user->city,
            ],
            'hospital' => [
                'id' => $user->hospital_id,
                'name' => $user->hospital,
            ],
            'department' => [
                'id' => $user->dept_id,
                'name' => $user->dept,
            ],
            'college' => [
                'id' => $user->college_id,
                'name' => $user->college,
            ],
            'tags' => $user->tag_list,
            'personal_introduction' => $user->profile,
            'is_auth' => $user->auth,
            'fee_switch' => $user->fee_switch,
            'fee' => $user->fee,
            'fee_face_to_face' => $user->fee_face_to_face,
            'admission_set_fixed' => $user->admission_set_fixed,
            'admission_set_flexible' => self::delOutdated(json_decode($user->admission_set_flexible, true)),
            'is_my_doctor' => Appointment::where('patient_id', $myId)
                ->where('doctor_id', $user->id)
                ->whereIn('status', ['completed-1', 'completed-2'])
                ->get()
                ->isEmpty()
                ? 'false' : 'true'
        ];
    }

    /**
     * @param $user
     * @return array
     */
    public static function findDoctorTransform($user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'head_url' => ($user->avatar == '') ? null : $user->avatar,
            'job_title' => $user->title,
            'province' => [
                'id' => $user->province_id,
                'name' => $user->province,
            ],
            'city' => [
                'id' => $user->city_id,
                'name' => $user->city,
            ],
            'hospital' => [
                'id' => $user->hospital_id,
                'name' => $user->hospital,
            ],
            'department' => [
                'id' => $user->dept_id,
                'name' => $user->dept,
            ],
            'college' => [
                'id' => $user->college_id,
                'name' => $user->college,
            ],
            'tags' => $user->tag_list,
            'personal_introduction' => $user->profile,
            'is_auth' => $user->auth
        ];
    }

    /**
     * 删除过期时间
     *
     * @param $data
     * @return string
     */
    public static function delOutdated($data)
    {
        if ($data == '' || $data == null) {
            return null;
        }

        $now = time();
        $newData = array();
        foreach ($data as $item) {
            if (strtotime($item['date']) > $now) {
                array_push($newData, $item);
            }
        }

        return json_encode($newData);
    }

    /**
     * Id to name.
     *
     * @param $users
     * @param $hospitalIdList
     * @param $deptIdList
     * @return mixed
     */
    public static function idToName($users, $hospitalIdList, $deptIdList)
    {
        $hospitals = Hospital::select('id', 'name')->find($hospitalIdList);
        $depts = DeptStandard::select('id', 'name')->find($deptIdList);

        foreach ($users as &$user) {
            foreach ($hospitals as $hospital) {
                if ($user['hospital'] == $hospital['id']) {
                    $user['hospital'] = $hospital['name'];
                }
            }

            foreach ($depts as $dept) {
                if ($user['department'] == $dept['id']) {
                    $user['department'] = $dept['name'];
                }
            }
        }

        return $users;
    }

    /**
     * ID to ID:Name.
     *
     * @param $users
     * @param $hospitalIdList
     * @param $deptIdList
     * @return mixed
     */
    public static function idToIdName($users, $hospitalIdList, $deptIdList)
    {
        $hospitals = Hospital::select('id', 'name')->find($hospitalIdList);
        $depts = DeptStandard::select('id', 'name')->find($deptIdList);

        foreach ($users as &$user) {
            foreach ($hospitals as $hospital) {
                if ($user['hospital'] == $hospital['id']) {
                    $user['hospital'] = [
                        'id' => $hospital['id'],
                        'name' => $hospital['name'],
                    ];
                }
            }

            foreach ($depts as $dept) {
                if ($user['department'] == $dept['id']) {
                    $user['department'] = [
                        'id' => $dept['id'],
                        'name' => $dept['name'],
                    ];
                }
            }
        }

        return $users;
    }

    /**
     * @param $id
     * @param $users
     * @param $list
     * @return mixed
     */
    public static function newFriendTransform($id, $users, $list)
    {
        $retData = array();
        $hospitalIdList = array();
        $deptIdList = array();

        foreach ($users as $user) {
            foreach ($list as $item) {
                if ($user->id == $item->doctor_id || $user->id == $item->doctor_friend_id) {
                    array_push(
                        $retData,
                        [
                            'id' => $user->id,
                            'name' => $user->name,
                            'head_url' => ($user->avatar == '') ? null : $user->avatar,
                            'hospital' => $user->hospital_id,
                            'department' => $user->dept_id,
                            'unread' => ($id == $item->doctor_id) ? $item->doctor_read : $item->doctor_friend_read,
                            'status' => $item->status,
                            'word' => $item->word,
                        ]
                    );
                }
            }

            array_push($hospitalIdList, $user->hospital_id);
            array_push($deptIdList, $user->dept_id);
        };

        return self::idToName(
            $retData,
            array_unique(array_values($hospitalIdList)),
            array_unique(array_values($deptIdList))
        );
    }

    /**
     * Transform friends friends.
     * 按共同好友数量倒序.
     *
     * @param $friends
     * @param $count
     * @return mixed
     */
    public static function friendsFriendsTransform($friends, $count)
    {
        foreach ($friends as &$friend) {
            $friend['common_friend_count'] = $count[$friend['id']];
        }

        usort($friends, function ($a, $b) {
            $al = $a['common_friend_count'];
            $bl = $b['common_friend_count'];
            if ($al == $bl)
                return 0;
            return ($al > $bl) ? -1 : 1;
        });

        return $friends;
    }

    /**
     * 格式化约诊详细信息
     *
     * @param $appointments
     * @param $doctor
     * @param $locumsDoctor
     * @return array
     */
    public static function appointmentsTransform($appointments, $doctor, $locumsDoctor)
    {
        return [
            'doctor_info' => [
                'id' => (empty($doctor)) ? null : $doctor->id,
                'name' => (empty($doctor)) ? null : $doctor->name,
                'head_url' => (empty($doctor)) ? null : (($doctor->avatar == '') ? null : $doctor->avatar),
                'job_title' => (empty($doctor)) ? null : $doctor->title,
                'hospital' => (empty($doctor)) ? null : $doctor->hospital,
                'department' => (empty($doctor)) ? null : $doctor->dept
            ],
            'locums_doctor_info' => [
                'id' => $locumsDoctor->id,
                'name' => $locumsDoctor->name,
                'head_url' => ($locumsDoctor->avatar == '') ? null : ((empty($doctor)) ? null : $doctor->avatar),
                'job_title' => $locumsDoctor->title,
                'hospital' => $locumsDoctor->hospital,
                'department' => $locumsDoctor->dept
            ],
            'patient_info' => [
                'name' => $appointments->patient_name,
                'head_url' => ($appointments->patient_avatar == '') ? null : $appointments->patient_avatar,
                'sex' => $appointments->patient_gender,
                'age' => $appointments->patient_age,
                'phone' => $appointments->patient_phone,
                'history' => $appointments->patient_history,
                'img_url' => $appointments->patient_imgs
            ],
            'other_info' => [
                'progress' => $appointments->progress,
                'time_line' => $appointments->time_line,
                'status_code' => $appointments->status,
                'is_pay' => $appointments->is_pay
            ]
        ];
    }
}
