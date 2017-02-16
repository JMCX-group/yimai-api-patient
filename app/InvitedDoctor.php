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
}
