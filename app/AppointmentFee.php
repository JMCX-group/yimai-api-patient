<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class AppointmentFee extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'appointment_fees';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'doctor_id',
        'patient_id',
        'locums_id',
        'appointment_id',
        'total_fee',
        'reception_fee',
        'platform_fee',
        'intermediary_fee',
        'guide_fee',
        'default_fee',
        'status',
        'settlement_status'
    ];

    /**
     * 各种状态的总费用
     * 资金状态：paid（已支付）、completed（已完成）、cancelled（已取消）
     *
     * @param $patientId
     * @param string $status
     * @return mixed
     */
    public static function getTotalFees($patientId, $status = 'completed')
    {
        return DB::select("
            SELECT SUM(`total_fee`) AS fee
            FROM `appointment_fees` 
            WHERE patient_id=$patientId AND `status`=$status;
        ");
    }

    /**
     * 总罚款
     *
     * @param $patientId
     * @return mixed
     */
    public static function getDefaultFees($patientId)
    {
        return DB::select("
            SELECT SUM(`default_fee`) AS fee
            FROM `appointment_fees` 
            WHERE patient_id=$patientId AND `status`='cancelled';
        ");
    }

    /**
     * 总冻结费用
     *
     * @param $patientId
     * @return mixed
     */
    public static function getFreezeFees($patientId)
    {
        return self::getTotalFees($patientId, 'paid');
    }
}
