<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class InvitedDoctor extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'invited_doctors';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'doctor_id',
        'doctor_phone',
        'patient_id',
        'status', //wait：等待邀请；invited：已邀请/未加入；re-invite：可以重新邀请了；join：已加入；processing：认证中；completed：完成认证
        'bonus'
    ];

    /**
     * 每个月的收益
     *
     * @param $patientId
     * @return array
     */
    public static function sumTotal_month($patientId)
    {
        return DB::select("
            SELECT 
                date_format(`updated_at`, '%Y年%m月') AS 'date',
                sum(`bonus`) AS total 
            FROM `invited_doctors` 
            WHERE `patient_id`=$patientId AND `status`='completed' 
            GROUP BY date_format(`updated_at`, '%Y-%m') 
            ORDER BY date_format(`updated_at`, '%Y-%m') DESC;
        ");
    }

    /**
     * 总收益
     *
     * @param $patientId
     * @return array
     */
    public static function sumTotal($patientId)
    {
        return DB::select("
            SELECT sum(`bonus`) AS total 
            FROM `invited_doctors` 
            WHERE `patient_id`=$patientId AND `status`='completed';
        ");
    }

    /**
     * 每个月收入列表
     *
     * @param $patientId
     * @param $year
     * @param $month
     * @return array
     */
    public static function monthTotal($patientId, $year, $month)
    {
        return DB::select("
            SELECT invited_doctors.bonus AS total, doctors.name, doctors.title AS job_title 
            FROM `invited_doctors` LEFT JOIN `doctors` ON invited_doctors.doctor_id=doctors.id 
            WHERE invited_doctors.patient_id=$patientId 
              AND invited_doctors.status='completed' 
              AND date_format(invited_doctors.updated_at, '%Y')='$year' 
              AND date_format(invited_doctors.updated_at, '%m')='$month' 
            ORDER BY invited_doctors.updated_at DESC;
        ");
    }

    /**
     * 每个月的总收入
     *
     * @param $patientId
     * @param $year
     * @param $month
     * @return array
     */
    public static function sumMonthTotal($patientId, $year, $month)
    {
        return DB::select("
            SELECT sum(`bonus`) AS total 
            FROM `invited_doctors` 
            WHERE `patient_id`=$patientId AND `status`='completed' 
              AND date_format(invited_doctors.updated_at, '%Y')='$year' 
              AND date_format(invited_doctors.updated_at, '%m')='$month' ;
        ");
    }

    /**
     * 我邀请的医生列表
     *
     * @param $patientId
     * @return mixed
     */
    public static function myInvitedList($patientId)
    {
        return InvitedDoctor::select(
            'invited_doctors.bonus AS total',
            'doctors.id', 'doctors.name', 'doctors.avatar AS head_url', 'doctors.title AS job_title',
            'hospitals.name AS hospital', 'dept_standards.name AS department')
            ->leftJoin('doctors', 'doctors.id', '=', 'invited_doctors.doctor_id')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'doctors.hospital_id')
            ->leftJoin('dept_standards', 'dept_standards.id', '=', 'doctors.dept_id')
            ->where('invited_doctors.patient_id', $patientId)
            ->where('invited_doctors.status', 'completed')
            ->orderBy('invited_doctors.updated_at', 'DESC')
            ->get();
    }
}
