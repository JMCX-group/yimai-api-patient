<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/18
 * Time: 下午4:08
 */

namespace App\Api\Transformers;

use App\AppointmentFee;
use App\Config;
use App\Hospital;

class TimeLineTransformer
{
    /**
     * 生成时间轴及其文案。
     *
     * @param $appointments
     * @param $doctors
     * @param $myId
     * @param $locumsDoctor
     * @return array|mixed
     */
    public static function generateTimeLine($appointments, $doctors, $myId, $locumsDoctor)
    {
        $retData = array();

        /**
         * 发起约诊的第一个时间点内容:
         */
        $time = $appointments->created_at->format('Y-m-d H:i:s');

        if ($appointments->doctor_or_patient == 'd') { //医生帮患者约
            $text = \Config::get('constants.APPOINTMENT_DEFAULT');
            $infoText = str_replace('{医生}', $doctors->name, $text);
            $infoText = str_replace('{代约医生}', $locumsDoctor->name, $infoText);
            $infoOther = self::otherInfoContent_initiateAppointments($appointments);
            $retData = self::copyTransformer($retData, $time, $infoText, $infoOther, 'pass');
        } else {
            if ($appointments->platform_or_doctor == '' || $appointments->platform_or_doctor == null) { //患者直接约诊医生
                $text = \Config::get('constants.PATIENT_REQUEST_APPOINTMENT');
                $infoText = str_replace('{医生}', $doctors->name, $text);
                $infoOther = self::otherInfoContent_initiateAppointments($appointments);
                $retData = self::copyTransformer($retData, $time, $infoText, $infoOther, 'pass');
            } elseif ($appointments->platform_or_doctor == 'p') { //平台代约
                $infoText = self::confirmLocumsText($locumsDoctor->name);
                $retData = self::copyTransformer($retData, $time, $infoText, null, 'pass');
            } else { //代约医生
                $infoText = self::confirmLocumsText($locumsDoctor->name);
                $retData = self::copyTransformer($retData, $time, $infoText, null, 'pass');
            }
        }

        switch ($appointments->status) {
            /**
             * wait:
             * wait-0: 待代约医生确认
             * wait-1: 待患者付款
             * wait-2: 患者已付款，待医生确认
             * wait-3: 医生确认接诊，待面诊
             * wait-4: 医生改期，待患者确认
             * wait-5: 患者确认改期，待面诊
             */
            case 'wait-0':
                $infoText = \Config::get('constants.WAIT_DOCTOR_CONFIRM');
                $retData = self::copyTransformer($retData, null, $infoText, null, 'wait');
                break;

            case 'wait-1':
                $infoText = \Config::get('constants.WAIT_PAYMENT');
                $infoOther = self::otherInfoContent_waitPay($appointments);
                $retData = self::copyTransformer($retData, null, $infoText, $infoOther, 'wait');
                break;

            case 'wait-2':
                $retData = self::otherInfoContent_alreadyPaid($appointments, $retData);

                $infoText = \Config::get('constants.ALREADY_PAID_WAIT_CONFIRM');
                $retData = self::copyTransformer($retData, null, $infoText, null, 'wait');
                break;

            case 'wait-3':
                $retData = self::otherInfoContent_alreadyPaid($appointments, $retData);

                $infoText = \Config::get('constants.CONFIRM_ADMISSIONS_WAIT_FACE_CONSULTATION');
                $infoOther = self::infoOther_faceConsultation($appointments, $doctors);
                $retData = self::copyTransformer($retData, null, $infoText, $infoOther, 'notepad');
                break;

            case 'wait-4':
                $retData = self::otherInfoContent_alreadyPaid($appointments, $retData);
                $retData = self::otherInfoContent_confirmAdmissions($appointments, $doctors, $retData);
                $retData = self::otherInfoContent_doctorRescheduled($appointments, $retData);

                $infoText = \Config::get('constants.DOCTOR_RESCHEDULED_WAIT_CONFIRM');
                $retData = self::copyTransformer($retData, null, $infoText, null, 'wait');
                break;

            case 'wait-5':
                $retData = self::otherInfoContent_alreadyPaid($appointments, $retData);
                $retData = self::otherInfoContent_confirmAdmissions($appointments, $doctors, $retData);
                $retData = self::otherInfoContent_doctorRescheduled($appointments, $retData);
                $retData = self::otherInfoContent_confirmRescheduled($appointments, $retData);

                $infoText = \Config::get('constants.WAIT_FACE_CONSULTATION');
                $retData = self::copyTransformer($retData, null, $infoText, null, 'wait');
                break;

            /**
             * close:
             * close-1: 待患者付款
             * close-2: 医生过期未接诊,约诊关闭
             * close-3: 医生拒绝接诊
             * close-4: 患者过期未确认,约诊关闭
             * close-5: 医生转诊,约诊关闭
             */
            case 'close-1':
                $infoText = \Config::get('constants.NOT_PAY_CLOSE');
                $retData = self::copyTransformer($retData, null, $infoText, null, 'close');
                break;

            case 'close-2':
                $retData = self::otherInfoContent_alreadyPaid($appointments, $retData);

                $infoText = \Config::get('constants.DOCTOR_EXPIRED_APPOINTMENT_CLOSE');
                $retData = self::copyTransformer($retData, null, $infoText, null, 'close');
                break;

            case 'close-3':
                $retData = self::otherInfoContent_alreadyPaid($appointments, $retData);

                $infoText = \Config::get('constants.DOCTOR_APPOINTMENT_CLOSE');
                $retData = self::copyTransformer($retData, null, $infoText, null, 'close');
                break;

            case 'close-4':
                $retData = self::otherInfoContent_alreadyPaid($appointments, $retData);
                $retData = self::otherInfoContent_confirmAdmissions($appointments, $doctors, $retData);
                $retData = self::otherInfoContent_doctorRescheduled($appointments, $retData);

                $infoText = \Config::get('constants.PATIENT_EXPIRED_APPOINTMENT_CLOSE');
                $retData = self::copyTransformer($retData, null, $infoText, null, 'close');
                break;

            case 'close-5':
                $retData = self::otherInfoContent_alreadyPaid($appointments, $retData);

                $infoText = \Config::get('constants.DOCTOR_TRANSFER');
                $retData = self::copyTransformer($retData, null, $infoText, null, 'close');
                break;

            /**
             * cancel:
             * cancel-1: 患者取消约诊; 未付款
             * cancel-2: 医生取消约诊
             * cancel-3: 患者取消约诊; 已付款后
             * cancel-4: 医生改期之后,医生取消约诊;
             * cancel-5: 医生改期之后,患者取消约诊;
             * cancel-6: 医生改期之后,患者确认之后,患者取消约诊;
             * cancel-7: 医生改期之后,患者确认之后,医生取消约诊;
             */
            case 'cancel-1':
                $infoText = \Config::get('constants.PATIENT_CANCEL_APPOINTMENT');
                $retData = self::copyTransformer($retData, null, $infoText, null, 'no');
                break;

            case 'cancel-2':
                $retData = self::otherInfoContent_alreadyPaid($appointments, $retData);
                $retData = self::otherInfoContent_confirmAdmissions($appointments, $doctors, $retData);
                $retData = self::otherInfoContent_doctorCancelAdmissions($retData);
                break;

            case 'cancel-3':
                $retData = self::otherInfoContent_alreadyPaid($appointments, $retData);
                $retData = self::otherInfoContent_confirmAdmissions($appointments, $doctors, $retData);

                $infoText = \Config::get('constants.PATIENT_CANCEL_APPOINTMENT');
                $retData = self::copyTransformer($retData, null, $infoText, null, 'no');
                break;

            case 'cancel-4':
                $retData = self::otherInfoContent_alreadyPaid($appointments, $retData);
                $retData = self::otherInfoContent_confirmAdmissions($appointments, $doctors, $retData);
                $retData = self::otherInfoContent_doctorRescheduled($appointments, $retData);
                $retData = self::otherInfoContent_doctorCancelAdmissions($retData);
                break;

            case 'cancel-5':
                $retData = self::otherInfoContent_alreadyPaid($appointments, $retData);
                $retData = self::otherInfoContent_confirmAdmissions($appointments, $doctors, $retData);
                $retData = self::otherInfoContent_doctorRescheduled($appointments, $retData);

                $infoText = \Config::get('constants.PATIENT_CANCEL_APPOINTMENT');
                $retData = self::copyTransformer($retData, null, $infoText, null, 'no');
                break;

            case 'cancel-6':
                $retData = self::otherInfoContent_alreadyPaid($appointments, $retData);
                $retData = self::otherInfoContent_confirmAdmissions($appointments, $doctors, $retData);
                $retData = self::otherInfoContent_doctorRescheduled($appointments, $retData);
                $retData = self::otherInfoContent_confirmRescheduled($appointments, $retData);

                $infoText = \Config::get('constants.PATIENT_CANCEL_APPOINTMENT');
                $retData = self::copyTransformer($retData, null, $infoText, null, 'no');
                break;

            case 'cancel-7':
                $retData = self::otherInfoContent_alreadyPaid($appointments, $retData);
                $retData = self::otherInfoContent_confirmAdmissions($appointments, $doctors, $retData);
                $retData = self::otherInfoContent_doctorRescheduled($appointments, $retData);
                $retData = self::otherInfoContent_confirmRescheduled($appointments, $retData);
                $retData = self::otherInfoContent_doctorCancelAdmissions($retData);
                break;

            /**
             * completed:
             * completed-1:最简正常流程
             * completed-2:改期后完成
             */
            case 'completed-1':
                $retData = self::otherInfoContent_alreadyPaid($appointments, $retData);
                $retData = self::otherInfoContent_confirmAdmissions($appointments, $doctors, $retData);

                $time = $appointments->completed_admissions_time;
                $infoText = \Config::get('constants.CONFIRM_FACE_CONSULTATION');
                $retData = self::copyTransformer($retData, $time, $infoText, null, 'pass');

                $retData = self::otherInfoContent_completed($appointments, $retData);
                break;

            case 'completed-2':
                $retData = self::otherInfoContent_alreadyPaid($appointments, $retData);
                $retData = self::otherInfoContent_confirmAdmissions($appointments, $doctors, $retData);
                $retData = self::otherInfoContent_doctorRescheduled($appointments, $retData);
                $retData = self::otherInfoContent_confirmRescheduled($appointments, $retData);
                $retData = self::otherInfoContent_completed($appointments, $retData);
                break;

            default:
                $retData = [];
                break;
        }

        return $retData;
    }

    /**
     * 发起约诊的第一个时间点内容。
     *
     * @param $appointments
     * @param $doctors
     * @param $retData
     * @return mixed
     */
    private static function otherInfoContent_firstInfo($appointments, $doctors, $retData)
    {
        $time = $appointments->created_at->format('Y-m-d H:i:s');
        $infoText = self::beginText($doctors);
        $infoOther = [[
            'name' => \Config::get('constants.DESIRED_TREATMENT_TIME'),
            'content' => PublicTransformer::expectVisitDateTransform($appointments->expect_visit_date, $appointments->expect_am_pm)
        ]];
        return self::copyTransformer($retData, $time, $infoText, $infoOther, 'begin');
    }

    /**
     * 等待支付的文案
     *
     * @param $appointments
     * @return array
     */
    private static function otherInfoContent_waitPay($appointments)
    {
        $rate = self::getRate($appointments);

        /**
         * 费用计算，和约诊文案那段一样：
         */
        $receptionFee = $appointments->price; //诊疗费; 不需要元转分
        $platformFee = $receptionFee * $rate; //平台费
        $totalFee = $receptionFee + $platformFee;

        if ($appointments->confirm_locums_time == '' || $appointments->confirm_locums_time == null) {
            $time = date('Y-m-d H:i:s', strtotime($appointments->created_at) + 12 * 3600);
        } else {
            $time = date('Y-m-d H:i:s', strtotime($appointments->confirm_locums_time) + 12 * 3600);
        }

        return [[
            'name' => '预估金额',
            'content' => $totalFee . '元'
        ], [
            'name' => '过期时间',
            'content' => $time
        ]];
    }

    /**
     * 计算费率
     *
     * @param $appointment
     * @return float
     */
    public static function getRate($appointment)
    {
        $configs = Config::find(1);
        $data = json_decode($configs->json, true);
        if ($appointment->doctor_or_patient == 'p' && $appointment->platform_or_doctor == 'p') {
            $rate = (float)$data['patient_to_platform_appointment'] / 100;; //患者发起的平台代约请求为20%
        } elseif ($appointment->doctor_or_patient == 'd' && $appointment->platform_or_doctor == null) {
            $rate = (float)$data['doctor_to_appointment'] / 100;; //患者发起的平台代约请求为20%
        } elseif ($appointment->doctor_or_patient == 'p' && $appointment->platform_or_doctor == 'd') {
            $rate = (float)$data['patient_to_appointment'] / 100;; //患者发起的平台代约请求为20%
        } else {
            $rate = (float)$data['patient_to_admissions'] / 100;; //患者发起的平台代约请求为20%
        }

        return $rate;
    }

    /**
     * 患者发起的文案段。
     *
     * @param $appointments
     * @return array
     */
    private static function otherInfoContent_initiateAppointments($appointments)
    {
        //开始截取
        $dates = $appointments->expect_visit_date;
        $am_pm = $appointments->expect_am_pm;
        if ($dates == 0 || $dates == '' || $dates == null) {
            $expectVisitDate = '由专家决定约诊时间';
        } else {
            if (strpos($dates, ',')) {
                $expectVisitDateArr = explode(',', $dates);
                $expectVisitAmPmArr = explode(',', $am_pm);
                $expectVisitDate = '';
                for ($i = 0; $i < count($expectVisitDateArr); $i++) {
                    $expectVisitDate .= $expectVisitDateArr[$i] . ' ' . (($expectVisitAmPmArr[$i] == 'am') ? '上午' : '下午');
                    $expectVisitDate .= ',';
                }
                $expectVisitDate = substr($expectVisitDate, 0, strlen($expectVisitDate) - 1);
            } else {
                $expectVisitDate = $dates . ' ' . (($am_pm == 'am') ? '上午' : '下午');
            }
        }

        return [[
            'name' => \Config::get('constants.PATIENT'),
            'content' => $appointments->patient_name . ' ' . (($appointments->patient_gender == 1) ? '男' : '女') . ' ' . ($appointments->patient_age) . '岁'
        ], [
            'name' => \Config::get('constants.DESIRED_TREATMENT_TIME'),
            'content' => $expectVisitDate
        ]];
    }

    /**
     * 医生取消约诊的文案段。
     *
     * @param $retData
     * @return mixed
     */
    private static function otherInfoContent_doctorCancelAdmissions($retData)
    {
        $infoText = \Config::get('constants.DOCTOR_CANCEL_ADMISSIONS');
        return self::copyTransformer($retData, null, $infoText, null, 'no');
    }

    /**
     * 医生确认改期的文案段。
     *
     * @param $appointments
     * @param $retData
     * @return mixed
     */
    private static function otherInfoContent_confirmRescheduled($appointments, $retData)
    {
        $time = $appointments->confirm_rescheduled_time;
        $infoText = \Config::get('constants.CONFIRM_RESCHEDULED');
        return self::copyTransformer($retData, $time, $infoText, null, 'pass');
    }

    /**
     * 患者已经支付的文案段。
     *
     * @param $appointments
     * @param $retData
     * @return mixed
     */
    private static function otherInfoContent_alreadyPaid($appointments, $retData)
    {
        $appointmentFee = AppointmentFee::where('appointment_id', $appointments->id)->first();
        if ($appointmentFee) {
            $time = $appointmentFee->created_at->format('Y-m-d H:i:s');
        } else {
            $time = '';
        }

        $infoText = \Config::get('constants.ALREADY_PAID');
        $infoOther = self::infoOther_alreadyPaid($appointments);
        return self::copyTransformer($retData, $time, $infoText, $infoOther, 'pass');
    }

    /**
     * 医生改期的文案段。
     *
     * @param $appointments
     * @param $retData
     * @return mixed
     */
    private static function otherInfoContent_doctorRescheduled($appointments, $retData)
    {
        $time = $appointments->rescheduled_time;
        $infoText = \Config::get('constants.DOCTOR_RESCHEDULED');
        $infoOther = [[
            'name' => \Config::get('constants.RESCHEDULED_TIME'),
            'content' => date('Y-m-d', strtotime($appointments->new_visit_time)) . ' ' . (($appointments->new_am_pm == 'am') ? '上午' : '下午')
        ]];
        return self::copyTransformer($retData, $time, $infoText, $infoOther, 'time');
    }

    /**
     * 患者缴费后,医生确认约诊,等待面诊的文案段。
     *
     * @param $appointments
     * @param $doctors
     * @param $retData
     * @return mixed
     */
    private static function otherInfoContent_confirmAdmissions($appointments, $doctors, $retData)
    {
        $time = $appointments->confirm_admissions_time;
        $infoText = \Config::get('constants.CONFIRM_ADMISSIONS_WAIT_FACE_CONSULTATION');
        $infoOther = self::infoOther_faceConsultation($appointments, $doctors);
        return self::copyTransformer($retData, $time, $infoText, $infoOther, 'notepad');
    }

    /**
     * 完成约诊的文案段。
     *
     * @param $appointments
     * @param $retData
     * @return mixed
     */
    private static function otherInfoContent_completed($appointments, $retData)
    {
//        $time = $appointments->updated_at->format('Y-m-d H:i:s');
        $infoText = \Config::get('constants.FACE_CONSULTATION_COMPLETE');
        return self::copyTransformer($retData, null, $infoText, null, 'completed');
    }

    /**
     * 面诊的附加信息段
     *
     * @param $appointments
     * @param $doctors
     * @return array
     */
    private static function infoOther_faceConsultation($appointments, $doctors)
    {
        if ($appointments->supplement == null || $appointments->supplement == '') {
            $supplement = Hospital::where('id', $doctors->hospital_id)->get()->lists('address')->first();
        } else {
            $supplement = $appointments->supplement;
        }

        return [[
            'name' => \Config::get('constants.TREATMENT_TIME'),
            'content' => date('Y-m-d', strtotime($appointments->visit_time)) . ' ' . (($appointments->am_pm == 'am') ? '上午' : '下午')
        ], [
            'name' => \Config::get('constants.TREATMENT_HOSPITAL'),
            'content' => $doctors->hospital
        ], [
            'name' => \Config::get('constants.SUPPLEMENT'),
            'content' => $supplement
        ], [
            'name' => \Config::get('constants.TREATMENT_NOTICE'),
            'content' => ($appointments->remark == null) ? '无' : $appointments->remark
        ]];
    }

    /**
     * 已支付的附加信息段
     *
     * @param $appointments
     * @return array
     */
    private static function infoOther_alreadyPaid($appointments)
    {
        $appointmentFee = AppointmentFee::where('appointment_id', $appointments->id)->first();

        return [[
            'name' => \Config::get('constants.COST'),
            'content' => ($appointmentFee->total_fee / 100) . '元'//分转元
        ]];
    }

    /**
     * 生成顶部的进度状态字。
     *
     * @param $status
     * @return array
     */
    public static function generateProgressStatus($status)
    {
        switch ($status) {
            /**
             * wait:
             */
            case 'wait-0':
                $retData = ['milestone' => '发起约诊', 'status' => '待确认'];
                break;
            case 'wait-1':
                $retData = ['milestone' => '发起约诊', 'status' => '待付款'];
                break;
            case 'wait-2':
                $retData = ['milestone' => '确认预约', 'status' => '待确认'];
                break;
            case 'wait-3':
            case 'wait-5':
                $retData = ['milestone' => '医生确认', 'status' => '待面诊'];
                break;
            case 'wait-4':
                $retData = ['milestone' => '医生确认', 'status' => '改期待确认'];
                break;

            /**
             * close:
             */
            case 'close-1':
                $retData = ['milestone' => '发起约诊', 'status' => '已关闭'];
                break;
            case 'close-2':
            case 'close-3':
                $retData = ['milestone' => '患者确认', 'status' => '已关闭'];
                break;
            case 'close-4':
            case 'close-5':
                $retData = ['milestone' => '医生确认', 'status' => '已关闭'];
                break;

            /**
             * cancel:
             */
            case 'cancel-1':
                $retData = ['milestone' => '发起约诊', 'status' => '已取消'];
                break;
            case 'cancel-2':
            case 'cancel-3':
            case 'cancel-4':
            case 'cancel-5':
            case 'cancel-6':
            case 'cancel-7':
                $retData = ['milestone' => '医生确认', 'status' => '已取消'];
                break;

            /**
             * completed:
             */
            case 'completed-1':
            case 'completed-2':
                $retData = ['milestone' => '面诊完成', 'status' => null];
                break;

            default:
                $retData = [];
                break;
        }

        return $retData;
    }

    /**
     * 第一句文案的角色名称替换
     *
     * @param $doctor
     * @return mixed
     */
    public static function beginText($doctor)
    {
        $text = \Config::get('constants.PATIENT_REQUEST_APPOINTMENT');
        $text = str_replace('{医生}', $doctor->name, $text);

        return $text;
    }

    /**
     * 确认代约文案的角色名称替换
     *
     * @param $locumsDoctor
     * @return mixed
     */
    public static function confirmLocumsText($locumsDoctor)
    {
        $text = \Config::get('constants.PATIENT_APPOINTMENT');
        $text = str_replace('{代约医生}', $locumsDoctor, $text);

        return $text;
    }

    /**
     * 格式化文案
     *
     * @param $retData
     * @param $time
     * @param $infoText
     * @param $infoOther
     * @param $type
     * @return mixed
     */
    public static function copyTransformer($retData, $time, $infoText, $infoOther, $type)
    {
        array_push(
            $retData,
            [
                'time' => $time,
                'info' => [
                    'text' => $infoText,
                    'other' => $infoOther
                ],
                'type' => $type
            ]
        );

        return $retData;
    }
}
