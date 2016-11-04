<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class Doctor extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'doctors';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dp_code',
        'phone',
        'email',
        'password',
        'rong_yun_token',
        'name',
        'avatar',
        'gender',
        'province_id',
        'city_id',
        'hospital_id',
        'dept_id',
        'title',
        'college_id',
        'id_num',
        'tag_list',
        'profile',
        'auth',
        'auth_img',
        'fee_switch',
        'fee',
        'fee_face_to_face',
        'admission_set_fixed',
        'admission_set_flexible',
        'verify_switch',
        'friends_friends_appointment_switch',
        'inviter_dp_code',
        'remember_token'
    ];

    /**
     * 获得某个医生主页信息
     *
     * @param $id
     * @return mixed
     */
    public static function findDoctor($id)
    {
        return Doctor::select(
            'doctors.id', 'doctors.name', 'doctors.avatar', 'doctors.title', 'doctors.auth',
            'doctors.province_id', 'doctors.city_id', 'doctors.hospital_id', 'doctors.dept_id', 'doctors.college_id',
            'doctors.tag_list', 'doctors.profile',
            'doctors.fee_switch', 'doctors.fee', 'doctors.fee_face_to_face', 'doctors.admission_set_fixed', 'doctors.admission_set_flexible',
            'provinces.name AS province', 'citys.name AS city',
            'hospitals.name AS hospital', 'dept_standards.name AS dept',
            'colleges.name AS college')
            ->leftJoin('provinces', 'provinces.id', '=', 'doctors.province_id')
            ->leftJoin('citys', 'citys.id', '=', 'doctors.city_id')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'doctors.hospital_id')
            ->leftJoin('dept_standards', 'dept_standards.id', '=', 'doctors.dept_id')
            ->leftJoin('colleges', 'colleges.id', '=', 'doctors.college_id')
            ->where('doctors.id', $id)
            ->get()
            ->first();
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
     * @param $title
     * @return mixed
     */
    public static function searchDoctor($field, $cityId, $hospitalId, $deptId, $title)
    {
        $condition = "where ";
        $condition .= $cityId ? "doctors.city_id = '$cityId' " : "";
        $condition .= $cityId ? "and " : "";
        $condition .= $hospitalId ? "doctors.hospital_id = '$hospitalId' " : "";
        $condition .= $hospitalId ? "and " : "";
        $condition .= $deptId ? "doctors.dept_id = '$deptId' " : "";
        $condition .= $deptId ? "and " : "";
        $condition .= $title ? "doctors.title = '$title' " : "";
        $condition .= $title ? "and " : "";
        $condition .= " (";
        $condition .= "doctors.name like '%$field%' ";
        $condition .= $hospitalId ? "" : "or hospitals.name like '%$field%' ";
        $condition .= $deptId ? "" : "or dept_standards.name like '%$field%' ";
        $condition .= "or doctors.tag_list like '%$field%' ";
        $condition .= ") ";

        return self::defaultSearchSql($condition, "ORDER BY hospitals.three_a desc");
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
     * 通过手机号获取医生信息
     *
     * @param $phone
     * @return mixed
     */
    public static function findDoctor_byPhone($phone)
    {
        return Doctor::select(
            'doctors.id', 'doctors.name', 'doctors.avatar', 'doctors.title', 'doctors.auth', 'doctors.phone',
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
            ->where('doctors.phone', $phone)
            ->get()
            ->first();
    }

    /**
     * 通过手机号或医脉码获取医生信息
     *
     * @param $code
     * @return mixed
     */
    public static function findDoctor_byCode($code)
    {
        $deptId = substr($code, 0, 3);
        $dpCode = substr($code, 3);

        return Doctor::select(
            'doctors.id', 'doctors.name', 'doctors.avatar', 'doctors.title', 'doctors.auth', 'doctors.phone',
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
            ->where('dept_id', $deptId)
            ->where('dp_code', $dpCode)
            ->get()
            ->first();
    }
}
