<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/21
 * Time: 上午9:45
 */

namespace App\Api\Controllers;

use App\Api\Helper\MsgAndNotification;
use App\Api\Helper\WeiXinPay;
use App\Api\Helper\SaveImage;
use App\Api\Requests\AppointmentIdRequest;
use App\Api\Requests\AppointmentInsteadRequest;
use App\Api\Requests\AppointmentRequest;
use App\Api\Transformers\ReservationRecordTransformer;
use App\Api\Transformers\TimeLineTransformer;
use App\Api\Transformers\Transformer;
use App\Appointment;
use App\AppointmentFee;
use App\Doctor;
use App\Patient;
use App\User;

class AppointmentController extends BaseController
{
    private $wxPayClass;

    public function __construct()
    {
        $this->wxPayClass = new WeiXinPay();
    }

    /**
     * wait:
     * wait-0: 待代理医生确认
     * wait-1: 待患者付款
     * wait-2: 患者已付款，待医生确认
     * wait-3: 医生确认接诊，待面诊
     * wait-4: 医生改期，待患者确认
     * wait-5: 患者确认改期，待面诊
     * close:
     * close-1: 待患者付款
     * close-2: 医生过期未接诊,约诊关闭
     * close-3: 医生拒绝接诊
     * close-4: 患者过期未确认,约诊关闭
     * cancel:
     * cancel-1: 患者取消约诊; 未付款
     * cancel-2: 医生取消约诊
     * cancel-3: 患者取消约诊; 已付款后
     * cancel-4: 医生改期之后,医生取消约诊;
     * cancel-5: 医生改期之后,患者取消约诊;
     * cancel-6: 医生改期之后,患者确认之后,患者取消约诊;
     * cancel-7: 医生改期之后,患者确认之后,医生取消约诊;
     * completed:
     * completed-1:最简正常流程
     * completed-2:改期后完成
     */

    /**
     * 预约:通过搜索找到我的医生/直接预约我的医生
     *
     * @param AppointmentRequest $request
     * @return array|mixed
     */
    public function store(AppointmentRequest $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        /**
         * 计算预约码做ID.
         * 规则:01-99 . 年月日各两位长 . 0001-9999
         */
        $frontId = '99' . date('ymd');
        $lastId = Appointment::where('id', 'like', $frontId . '%')
            ->orderBy('id', 'desc')
            ->lists('id');
        if ($lastId->isEmpty()) {
            $nowId = '0001';
        } else {
            $lastId = intval(substr($lastId[0], 8));
            $nowId = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        }

        /**
         * 时间过滤：
         */
        $expectVisitDate = $request['date'];
        if (substr($expectVisitDate, strlen($expectVisitDate) - 1) == ',') {
            $expectVisitDate = substr($expectVisitDate, 0, strlen($expectVisitDate) - 1);
        }
        $expectAmPm = $request['am_or_pm'];
        if (substr($expectAmPm, strlen($expectAmPm) - 1) == ',') {
            $expectAmPm = substr($expectAmPm, 0, strlen($expectAmPm) - 1);
        }

        /**
         * 发起约诊信息记录
         */
        $doctor = Doctor::find($request['doctor']);
        $data = [
            'id' => $frontId . $nowId,
            'locums_id' => 0, //代理医生ID,1为平台代约,0为没有代约医生
            'patient_id' => $user->id,
            'patient_name' => $request['name'],
            'patient_phone' => $request['phone'],
            'patient_gender' => $request['sex'],
            'patient_age' => $request['age'],
            'patient_history' => $request['history'],
            'doctor_id' => $request['doctor'],
            'request_mode' => '找专家', //我的医生、找专家、医生代约
            'platform_or_doctor' => 'p',
            'doctor_or_patient' => 'p', //患者发起
            'expect_visit_date' => $expectVisitDate,
            'expect_am_pm' => $expectAmPm,
            'price' => $doctor->fee,
            'status' => 'wait-1' //新建约诊之后,进入待患者付款阶段
        ];

        try {
            $appointment = Appointment::create($data);
            $appointment['id'] = $frontId . $nowId;

            MsgAndNotification::sendAppointmentsMsg($appointment); //推送消息
            $patient = Patient::where('phone', $appointment['patient_phone'])->first();
            if (isset($patient->id) && ($patient->device_token != '' && $patient->device_token != null)) {
                MsgAndNotification::pushAppointmentMsg($patient->device_token, $appointment['status'], $appointment['id'], 'patient'); //向患者端推送消息
            }

            $data = [
                'id' => $appointment['id'],
                'appointment_info' => $this->appointmentDetailInfo($appointment['id'], $user->id)
            ];

            return response()->json(compact('data'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    /**
     * 患者发起的代约请求
     *
     * @param AppointmentInsteadRequest $request
     * @return array|\Illuminate\Http\JsonResponse|mixed
     */
    public function insteadAppointment(AppointmentInsteadRequest $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        /**
         * 计算预约码做ID.
         * 规则:01-99 . 年月日各两位长 . 0001-9999
         */
        $frontId = '88' . date('ymd');
        $lastId = Appointment::where('id', 'like', $frontId . '%')
            ->orderBy('id', 'desc')
            ->lists('id');
        if ($lastId->isEmpty()) {
            $nowId = '0001';
        } else {
            $lastId = intval(substr($lastId[0], 8));
            $nowId = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        }

        /**
         * 时间过滤：
         */
        $expectVisitDate = $request['date'];
        if (substr($expectVisitDate, strlen($expectVisitDate) - 1) == ',') {
            $expectVisitDate = substr($expectVisitDate, 0, strlen($expectVisitDate) - 1);
        }
        $expectAmPm = $request['am_or_pm'];
        if (substr($expectAmPm, strlen($expectAmPm) - 1) == ',') {
            $expectAmPm = substr($expectAmPm, 0, strlen($expectAmPm) - 1);
        }

        /**
         * 判断我的医生、找专家（平台代约，ID为1）、医生代约
         */
        if ($request['locums_doctor'] == 1) {
            $requestMode = '找专家';
        } else {
            $isMyDoctor = Appointment::where('locums_id', $request['locums_doctor'])
                ->where('patient_id', $user->id)
                ->first()
                ->get();
            if ($isMyDoctor == null) {
                $requestMode = '医生代约';
            } else {
                $requestMode = '我的医生';
            }
        }

        /**
         * 发起约诊信息记录
         */
        $data = [
            'id' => $frontId . $nowId,
            'locums_id' => $request['locums_doctor'], //代理医生ID,1为平台代约,0为没有代约医生
            'doctor_id' => $request['doctor'], //请求代约哪个医生
            'patient_id' => $user->id,
            'patient_name' => $request['name'],
            'patient_phone' => $request['phone'],
            'patient_gender' => $request['sex'],
            'patient_age' => $request['age'],
            'patient_history' => $request['history'],
            'patient_demand_doctor_name' => isset($request['demand_doctor_name']) ? $request['demand_doctor_name'] : '',
            'patient_demand_hospital' => isset($request['demand_hospital']) ? $request['demand_hospital'] : '',
            'patient_demand_dept' => isset($request['demand_dept']) ? $request['demand_dept'] : '',
            'patient_demand_title' => isset($request['demand_title']) ? $request['demand_title'] : '',
            'request_mode' => $requestMode,
            'platform_or_doctor' => ($request['locums_doctor'] == 1) ? 'p' : 'd',
            'doctor_or_patient' => 'p', //患者发起
            'expect_visit_date' => $expectVisitDate,
            'expect_am_pm' => $expectAmPm,
            'status' => 'wait-0' //请求代约
        ];

        try {
            $appointment = Appointment::create($data);
            $appointment['id'] = $frontId . $nowId;

            MsgAndNotification::sendAppointmentsMsg($appointment); //推送消息
            $doctor = Doctor::where('id', $appointment['locums_id'])->first();
            if (isset($doctor->id) && ($doctor->device_token != '' && $doctor->device_token != null)) {
                MsgAndNotification::pushAppointmentMsg($doctor->device_token, $appointment['status'], $appointment['id'], 'doctor'); //向医生端推送消息
            }

            $data = [
                'id' => $appointment['id'],
                'appointment_info' => $this->appointmentDetailInfo($appointment['id'], $user->id)
            ];

            return response()->json(compact('data'));;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    /**
     * 上传图片
     *
     * @param AppointmentIdRequest $request
     * @return array
     */
    public function uploadImg(AppointmentIdRequest $request)
    {
        $appointment = Appointment::find($request['id']);
        $imgUrl = SaveImage::appointment($appointment->id, $request->file('img'));

        if (strlen($appointment->patient_imgs) > 0) {
            $appointment->patient_imgs .= ',' . $imgUrl;
        } else {
            $appointment->patient_imgs = $imgUrl;
        }

        $appointment->save();

        return ['url' => $imgUrl];
    }

    /**
     * 获取约诊详细信息
     *
     * @param AppointmentIdRequest $request
     * @return array|mixed
     */
    public function detail(AppointmentIdRequest $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        return self::appointmentDetailInfo($request['id'], $user->id);
    }

    /**
     * 约诊信息生成
     *
     * @param $id
     * @param $userId
     * @return array
     */
    public static function appointmentDetailInfo($id, $userId)
    {
        $appointments = Appointment::where('appointments.id', $id)
            ->leftJoin('doctors', 'doctors.id', '=', 'appointments.locums_id')
            ->leftJoin('patients', 'patients.id', '=', 'appointments.patient_id')
            ->select('appointments.*', 'doctors.name as locums_name', 'patients.avatar as patient_avatar')
            ->get()
            ->first();

        $doctors = Doctor::where('doctors.id', $appointments->doctor_id)
            ->select(
                'doctors.id', 'doctors.name', 'doctors.avatar', 'doctors.hospital_id', 'doctors.dept_id', 'doctors.title',
                'hospitals.name AS hospital', 'dept_standards.name AS dept')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'doctors.hospital_id')
            ->leftJoin('dept_standards', 'dept_standards.id', '=', 'doctors.dept_id')
            ->get()
            ->first();

        /**
         * 查询代约医生的信息:
         */
        if ($appointments->locums_id == '1') { //医脉平台代约
            $locumsDoctor = [
                'id' => 1,
                'name' => '医脉平台',
                'avatar' => '/uploads/avatar/default.jpg',
                'title' => '',
                'hospital' => '',
                'dept' => ''
            ];
            $locumsDoctor = (object)$locumsDoctor;
        } elseif ($appointments->locums_id == '0') { //没有代约医生
            $locumsDoctor = [
                'id' => '',
                'name' => '',
                'avatar' => '',
                'title' => '',
                'hospital' => '',
                'dept' => ''
            ];
            $locumsDoctor = (object)$locumsDoctor;
        } else {
            $locumsDoctor = Doctor::where('doctors.id', $appointments->locums_id)
                ->select(
                    'doctors.id', 'doctors.name', 'doctors.avatar', 'doctors.hospital_id', 'doctors.dept_id', 'doctors.title',
                    'hospitals.name AS hospital', 'dept_standards.name AS dept')
                ->leftJoin('hospitals', 'hospitals.id', '=', 'doctors.hospital_id')
                ->leftJoin('dept_standards', 'dept_standards.id', '=', 'doctors.dept_id')
                ->get()
                ->first();
        }

        $appointments['time_line'] = TimeLineTransformer::generateTimeLine($appointments, $doctors, $userId, $locumsDoctor);
        $appointments['progress'] = TimeLineTransformer::generateProgressStatus($appointments->status);

        return Transformer::appointmentsTransform($appointments, $doctors, $locumsDoctor);
    }

    /**
     * 约诊记录。
     *
     * @return array|mixed
     */
    public function getReservationRecord()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        /**
         * 更新订单没有id的：
         */
        Appointment::where('patient_phone', $user->phone)->update(['patient_id' => $user->id]);

        /**
         * 获取该登录用户所有信息：
         */
        $appointments = Appointment::where('appointments.patient_id', $user->id)
            ->leftJoin('doctors', 'doctors.id', '=', 'appointments.doctor_id')
            ->select('appointments.*', 'doctors.name', 'doctors.avatar', 'doctors.title', 'doctors.auth')
            ->orderBy('updated_at', 'desc')
            ->get();

        if ($appointments->isEmpty()) {
            return response()->json(['success' => ''], 204);
        }

        $waitingConfirmed = array();
        $waitingMeet = array();
        $completed = array();
        foreach ($appointments as $appointment) {
            if (in_array($appointment['status'], array('wait-0', 'wait-1', 'wait-2', 'wait-4'))) {
                array_push($waitingConfirmed, ReservationRecordTransformer::appointmentTransform($appointment));
            } elseif (in_array($appointment['status'], array('wait-3', 'wait-5'))) {
                array_push($waitingMeet, ReservationRecordTransformer::appointmentTransform($appointment));
            } else {
                array_push($completed, ReservationRecordTransformer::appointmentTransform($appointment));
            }
        }

        $data = [
            'wait_confirm' => $waitingConfirmed,
            'wait_meet' => $waitingMeet,
            'completed' => $completed
        ];

        return response()->json(compact('data'));
    }

    /**
     * 调用微信支付
     *
     * @param AppointmentIdRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pay(AppointmentIdRequest $request)
    {
//        $appointmentId = $request['id'];
//        $appointment = Appointment::find($appointmentId);
//        $doctor = Doctor::find($appointment->doctor_id);
//
//        /**
//         * 没有支付金额修复：
//         */
//        if ($appointment->price == null) {
//            $appointment->price = $doctor->fee;
//        }
//
//        //微信支付
//        $data = ['message' => 'false'];
//        if (!($appointment->price == 0 || $appointment->price == null || $appointment->price == '')) {
//            $retData = $this->wxPay($appointment);
//            if ($retData != false) {
//                $appointment->is_pay = '1'; //已经支付记录
//                $appointment->save();
//
//                $data = $retData;
//                return response()->json(compact('data'), 200);
//            }
//        }
//
//        return response()->json(compact('data'), 500);
    }

    /**
     * 调用微信支付
     *
     * @param $appointment
     * @return array|bool
     */
    public function wxPay($appointment)
    {
//        try {
//            $order = Order::where('out_trade_no', $appointment->id)->first();
//            if (empty($order->id)) {
//                $newOrder = [
//                    'doctor_id' => $appointment->doctor_id,
//                    'patient_id' => $appointment->patient_id,
//                    'out_trade_no' => $appointment->id,
////                    'total_fee' => ($appointment->price) * 100,
//                    'total_fee' => 1, //临时测试，只支付一分
//                    'body' => '约诊',
//                    'detail' => '',
//                    'type' => '收入',
//                    'time_start' => date('Y-m-d H:i:s'),
//                    'status' => 'start', //start:开始; end:结束
//                    'settlement_status' => '待结算'
//                ];
//                $order = Order::create($newOrder);
//            }
//
//            $timeExpire = date('YmdHis', (time() + 600)); //过期时间600秒
//            return $this->wxPayClass->wxPay($order['out_trade_no'], $order['body'], $order['total_fee'], $timeExpire);
//        } catch (\Exception $e) {
//            return false;
//        }
    }

    /**
     * 患者确认完成面诊
     *
     * @param AppointmentIdRequest $request
     * @return array|\Illuminate\Http\JsonResponse|mixed
     */
    public function complete(AppointmentIdRequest $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $appointment = Appointment::where('id', $request['id'])->first();
        $appointment->status = 'completed-1';

        try {
            if ($appointment->save()) {
                /**
                 * 更新支付信息：
                 */
                AppointmentFee::where('appointment_id', $request['id'])
                    ->update([
                        'status' => 'completed', //资金状态：paid（已支付）、completed（已完成）、cancelled（已取消）
                        'time_expire' => date('Y-m-d H:i:s')
                    ]);

                /**
                 * 推送消息
                 */
                MsgAndNotification::sendAppointmentsMsg($appointment); //推送消息
                $doctor = Doctor::where('id', $appointment->doctor_id)->first();
                if (isset($doctor->id) && ($doctor->device_token != '' && $doctor->device_token != null)) {
                    MsgAndNotification::pushAppointmentMsg($doctor->device_token, $appointment->status, $appointment->id, 'doctor'); //向医生端推送消息
                }

                $data = [
                    'id' => $appointment['id'],
                    'appointment_info' => $this->appointmentDetailInfo($appointment->id, $user->id)
                ];

                return response()->json(compact('data'));
            } else {
                return response()->json(['message' => '保存失败'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    /**
     * 患者确认改期，wait-4 -> wait-5
     *
     * @param AppointmentIdRequest $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function confirmRescheduled(AppointmentIdRequest $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $appointment = Appointment::where('id', $request['id'])->first();
        $appointment->status = 'wait-5';
        $appointment->confirm_rescheduled_time = date('Y-m-d H:i:s');

        try {
            if ($appointment->save()) {
                MsgAndNotification::sendAppointmentsMsg($appointment); //推送消息
                $doctor = Doctor::where('id', $appointment->doctor_id)->first();
                if (isset($doctor->id) && ($doctor->device_token != '' && $doctor->device_token != null)) {
                    MsgAndNotification::pushAppointmentMsg($doctor->device_token, $appointment->status, $appointment->id, 'doctor'); //向医生端推送消息
                }

                $data = [
                    'id' => $appointment['id'],
                    'appointment_info' => $this->appointmentDetailInfo($appointment->id, $user->id)
                ];

                return response()->json(compact('data'));
            } else {
                return response()->json(['message' => '保存失败'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    /**
     * 取消约诊
     *
     * @param AppointmentIdRequest $request
     * @return mixed
     */
    public function cancel(AppointmentIdRequest $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $appointmentId = $request['id'];
        $appointment = Appointment::where('id', $appointmentId)->first();
        $appointmentFee = AppointmentFee::where('appointment_id', $appointmentId)->first();

        /**
         * Wait:
         * wait-0: 待代约医生确认
         * wait-1: 待患者付款
         * wait-2: 患者已付款，待医生确认
         * wait-3: 医生确认接诊，待面诊
         * wait-4: 医生改期，待患者确认
         * wait-5: 患者确认改期，待面诊
         *
         * Close:
         * close-1: 待患者付款
         * close-2: 医生过期未接诊,约诊关闭
         * close-3: 医生拒绝接诊
         * close-4: 患者过期未确认,约诊关闭
         *
         * Cancel:
         * cancel-1: 患者取消约诊; 未付款
         * cancel-2: 医生取消约诊
         * cancel-3: 患者取消约诊; 已付款后
         * cancel-4: 医生改期之后,医生取消约诊;
         * cancel-5: 医生改期之后,患者取消约诊;
         * cancel-6: 医生改期之后,患者确认之后,患者取消约诊;
         * cancel-7: 医生改期之后,患者确认之后,医生取消约诊;
         *
         * Completed:
         * completed-1:最简正常流程
         * completed-2:改期后完成
         */
        switch ($appointment->status) {
            case 'wait-0':
            case 'wait-1':
                $appointment->status = 'cancel-1';
                $needDeductRate = 0; //需要扣除的费率
                break;
            case 'wait-2':
                $appointment->status = 'cancel-3';
                $needDeductRate = 0; //需要扣除的费率
                break;
            case 'wait-3':
                $appointment->status = 'cancel-3';
                if (date('Y-m-d H:i:s', strtotime($appointmentFee->created_at) + 24 * 3600) > date('Y-m-d H:i:s', time())) {
                    $needDeductRate = 0.8; //需要扣除的费率，超过24小时
                } else {
                    $needDeductRate = 0.5; //需要扣除的费率，24小时内
                }
                break;
            case 'wait-4':
                $appointment->status = 'cancel-5';
                $needDeductRate = 0; //需要扣除的费率
                break;
            default: //wait-5
                $appointment->status = 'cancel-6';
                $needDeductRate = 0; //需要扣除的费率
                break;
        }

        try {
            $appointment->patient_cancel_time = date('Y-m-d H:i:s', time());
            $appointment->save();

            /**
             * 费用计算：
             */
            if ($appointmentFee) {
                $appointmentFee->total_fee = ($appointmentFee->total_fee) * $needDeductRate;
                $appointmentFee->reception_fee = ($appointmentFee->reception_fee) * $needDeductRate;
                $appointmentFee->platform_fee = ($appointmentFee->platform_fee) * $needDeductRate;
                $appointmentFee->default_fee_rate = $needDeductRate;
                $appointmentFee->save();
            }

            $data = [
                'id' => $appointment['id'],
                'appointment_info' => $this->appointmentDetailInfo($appointment->id, $user->id)
            ];

            return response()->json(compact('data'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
