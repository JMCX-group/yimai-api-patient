<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use DB;

/**
 * Class User
 * @package App
 */
class User extends Model implements AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'patients';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'phone',
        'password',
        'name',
        'nickname',
        'gender',
        'birthday',
        'province_id',
        'city_id',
        'tag_list',
        'my_doctors'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Get logged user info.
     *
     * @return mixed
     */
    public static function getAuthenticatedUser()
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['message' => 'user_not_found'], 404);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'token_expired'], $e->getStatusCode());
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'token_invalid'], $e->getStatusCode());
        } catch (JWTException $e) {
            return response()->json(['error' => 'token_absent'], $e->getStatusCode());
        }

        // the token is valid and we have found the user via the sub claim
        return $user;
    }

    /**
     * Generate new DP Code.
     * 科室编号3位 + 301开始的编码.
     * 300以内为内定.
     *
     * @param $deptId
     * @return mixed
     */
    public static function generateDpCode($deptId)
    {
        $data = User::select('dp_code')
            ->where('dept_id', $deptId)
            ->orderBy('dp_code', 'desc')
            ->first();

        if (isset($data->dp_code)) {
            return intval($data->dp_code) + 1;
        } else {
            return 301;
        }
    }

    /**
     * Get DP Code.
     *
     * @param $id
     * @return string
     */
    public static function getDpCode($id)
    {
        $data = User::select('dp_code', 'dept_id')->where('id', $id)->first();

        $dpCode = str_pad($data->dept_id, 3, '0', STR_PAD_LEFT) . $data->dp_code;

        return $dpCode;
    }

    /**
     * Get inviter name.
     *
     * @param $dpCode
     * @return bool
     */
    public static function getInviter($dpCode)
    {
        $data = User::select('name')
            ->where('city_id', City::select('id')->where('code', substr($dpCode, 0, 3))->first()->id)
            ->where('dept_id', substr($dpCode, 3, 3))
            ->where('dp_code', substr($dpCode, 6))
            ->get();

        if (isset($data->first()->name)) {
            return $data->first()->name;
        } else {
            return false;
        }
    }

    /**
     * Get same type contact count.
     *
     * @param $hospitalId
     * @param $deptId
     * @param $collegeId
     * @return array
     */
    public static function getSameTypeContactCount($hospitalId, $deptId, $collegeId)
    {
        $hospitalCount = User::where('hospital_id', $hospitalId)->count();
        $deptCount = User::where('dept_id', $deptId)->count();
        $collegeCount = User::where('college_id', $collegeId)->count();

        return [
            'hospital' => $hospitalCount,
            'department' => $deptCount,
            'college' => $collegeCount,
        ];
    }

    /**
     * 默认进入搜索获取的数据,获取相关标签
     *
     * @param $field
     * @return mixed
     */
    public static function defaultInfo($field)
    {
        $condition = "WHERE `dept_id` IN (" . $field . ")";

        return self::defaultSearchSql($condition, "ORDER BY hospitals.three_a desc");
    }

    /**
     * 默认进入搜索获取的数据,获取相关标签
     *
     * @return array|mixed
     */
    public static function newDefaultInfo()
    {
        $idArr = Appointment::getTop10();
        if ($idArr) {
            $idList = join(',', $idArr);
            $condition = "WHERE doctors.id IN (" . $idList . ") ";
            $order = "ORDER BY FIND_IN_SET(doctors.id, '$idList')"; //按照whereIn里的排序

            return self::defaultSearchSql($condition, $order);
        } else {
            return [];
        }
    }

    /**
     * 根据必填的字段值和可选的城市/医院/科室条件搜索符合条件的医生.
     * id转name.
     * 按是否三甲医院排序.
     *
     * @param $field
     * @param $cityId
     * @param $hospitalId
     * @param $deptId
     * @return mixed
     */
    public static function searchDoctor($field, $cityId, $hospitalId, $deptId)
    {
        $condition = "where ";
        $condition .= $cityId ? "doctors.city_id = '$cityId' " : "";
        $condition .= $cityId ? "and " : "";
        $condition .= $hospitalId ? "doctors.hospital_id = '$hospitalId' " : "";
        $condition .= $hospitalId ? "and " : "";
        $condition .= $deptId ? "doctors.dept_id = '$deptId' " : "";
        $condition .= $deptId ? "and " : "";
        $condition .= " (";
        $condition .= "doctors.name like '%$field%' ";
        $condition .= $hospitalId ? "" : "or hospitals.name like '%$field%' ";
        $condition .= $deptId ? "" : "or dept_standards.name like '%$field%' ";
        $condition .= "or doctors.tag_list like '%$field%' ";
        $condition .= ") ";

        return self::defaultSearchSql($condition, "ORDER BY hospitals.three_a desc");
    }

    /**
     * 搜索同城市的医生信息.
     * id转name.
     *
     * @param $field
     * @param $cityId
     * @return mixed
     */
    public static function searchDoctor_admissions($field, $cityId)
    {
        $condition = "where `doctors`.`city_id` = '$cityId' ";
        $condition .= $field ? "and (doctors.name like '%$field%' ) " : "";

        return self::defaultSearchSql($condition);
    }

    /**
     * 搜索同医院的医生信息.
     *
     * @param $field
     * @param $hospitalId
     * @param $cityId
     * @param $deptId
     * @return mixed
     */
    public static function searchDoctor_sameHospital($field, $hospitalId, $cityId, $deptId)
    {
        $condition = "where ";
        $condition .= $cityId ? "doctors.city_id = '$cityId' " : "";
        $condition .= $cityId ? "and " : "";
        $condition .= $deptId ? "doctors.dept_id = '$deptId' " : "";
        $condition .= $deptId ? "and " : "";
        $condition .= "doctors.hospital_id = '$hospitalId' ";
        $condition .= $field ? "and (doctors.name like '%$field%' or dept_standards.name like '%$field%' ) " : "";

        return self::defaultSearchSql($condition);
    }

    /**
     * 搜索相同一级科室的所有一二级科室的医生信息。
     *
     * @param $field
     * @param $deptList
     * @param $cityId
     * @param $hospitalId
     * @return mixed
     */
    public static function searchDoctor_sameDept($field, $deptList, $cityId, $hospitalId)
    {
        $deptList = implode(',', $deptList);

        $condition = "where ";
        $condition .= $cityId ? "doctors.city_id = '$cityId' " : "";
        $condition .= $cityId ? "and " : "";
        $condition .= $hospitalId ? "doctors.hospital_id = '$hospitalId' " : "";
        $condition .= $hospitalId ? "and " : "";
        $condition .= "doctors.dept_id IN ($deptList) ";
        $condition .= $field ? "and (doctors.name like '%$field%' or hospitals.name like '%$field%' or doctors.tag_list like '%$field%') " : "";

        return self::defaultSearchSql($condition);
    }

    /**
     * 搜索同院校下的医生信息。
     *
     * @param $field
     * @param $collegeId
     * @param $cityId
     * @param $hospitalId
     * @param $deptId
     * @return mixed
     */
    public static function searchDoctor_sameCollege($field, $collegeId, $cityId, $hospitalId, $deptId)
    {
        $condition = "where ";
        $condition .= $cityId ? "doctors.city_id = '$cityId' " : "";
        $condition .= $cityId ? "and " : "";
        $condition .= $hospitalId ? "doctors.hospital_id = '$hospitalId' " : "";
        $condition .= $hospitalId ? "and " : "";
        $condition .= $deptId ? "doctors.dept_id = '$deptId' " : "";
        $condition .= $deptId ? "and " : "";
        $condition .= "doctors.college_id = '$collegeId' ";
        $condition .= $field
            ? "and (doctors.name like '%$field%' or dept_standards.name like '%$field%' " .
            "or hospitals.name like '%$field%' or doctors.tag_list like '%$field%') "
            : "";

        return self::defaultSearchSql($condition);
    }

    /**
     * 标准的查询SQL语句
     *
     * @param $condition
     * @param $order
     * @return mixed
     */
    public static function defaultSearchSql($condition, $order = '')
    {
        return DB::select(
            "SELECT doctors.*," .
            "provinces.name AS province, citys.name AS city, hospitals.name AS hospital, dept_standards.name AS dept, colleges.name AS college " .
            "FROM doctors " .
            "LEFT JOIN provinces ON provinces.id=doctors.province_id " .
            "LEFT JOIN dept_standards ON dept_standards.id=doctors.dept_id " .
            "LEFT JOIN citys ON citys.id=doctors.city_id " .
            "LEFT JOIN hospitals ON hospitals.id=doctors.hospital_id " .
            "LEFT JOIN colleges ON colleges.id=doctors.college_id " .
            $condition .
            $order
        );
    }

    /**
     * 获得某个医生主页信息
     *
     * @param $id
     * @return mixed
     */
    public static function findDoctor($id)
    {
        return User::select(
            'doctors.id', 'doctors.name', 'doctors.avatar', 'doctors.title', 'doctors.auth',
            'doctors.province_id', 'doctors.city_id', 'doctors.hospital_id', 'doctors.dept_id', 'doctors.college_id',
            'doctors.tag_list', 'doctors.profile',
            'provinces.name AS province', 'citys.name AS city',
            'hospitals.name AS hospital', 'dept_standards.name AS dept',
            'colleges.name AS college')
            ->leftJoin('provinces', 'provinces.id', '=', 'doctors.province_id')
            ->leftJoin('citys', 'citys.id', '=', 'doctors.city_id')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'doctors.hospital_id')
            ->leftJoin('dept_standards', 'dept_standards.id', '=', 'doctors.dept_id')
            ->leftJoin('colleges', 'colleges.id', '=', 'doctors.college_id')
            ->where('doctors.id', $id)
            ->first();
    }
}
