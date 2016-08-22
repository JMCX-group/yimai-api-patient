<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Comment\Doc;

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
}
