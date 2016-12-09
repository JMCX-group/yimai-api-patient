<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class Appointment extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'appointments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'locums_id',
        'confirm_locums_time',
        'patient_name',
        'patient_phone',
        'patient_gender',
        'patient_age',
        'patient_history',
        'patient_imgs',
        'doctor_id',
        'patient_id',
        'patient_demand',
        'request_mode',
        'platform_or_doctor',
        'doctor_or_patient',
        'expect_visit_date',
        'expect_am_pm',
        'visit_time',
        'am_pm',
        'supplement',
        'remark',
        'refusal_reason',
        'deposit',
        'price',
        'transaction_id',
        'confirm_admissions_time',
        'completed_rescheduled_time',
        'rescheduled_time',
        'new_visit_time',
        'new_am_pm',
        'confirm_rescheduled_time',
        'status'
    ];

    /**
     * 获取全部待缴费状态的id list。
     *
     * @param $id
     * @param $phone
     * @return mixed
     */
    public static function getAllWait1AppointmentIdList($id, $phone)
    {
        return DB::select(
            "select `id` from `appointments` where ((`patient_id`='$id' OR `patient_phone`='$phone') AND `status`='wait-1')"
        );
    }

    /**
     * @param $id
     * @return mixed
     */
    public static function getMyDoctors($id)
    {
        /**
         * 获取约诊成功的医生列表：
         */
        $ret = DB::select(
            "select distinct `doctor_id` from `appointments` where `patient_id` = '$id' AND (`status`='completed-1' OR `status`='completed-2')"
        );

        /**
         * 获取扫码添加的医生列表：
         */
        $patientMyDoctors = Patient::select('my_doctors')->where('id', $id)->get()->toArray();

        /**
         * 去重：
         */
        $myDoctors = explode(',', $patientMyDoctors[0]['my_doctors']);
        $tmpIdArr = array();
        foreach ($ret as $item) {
            if (!in_array($item->doctor_id, $myDoctors)) {
                array_push($tmpIdArr, $item->doctor_id);
            }
        }
        $retArr = array_merge($tmpIdArr, $myDoctors);

        return $retArr;
    }
}
