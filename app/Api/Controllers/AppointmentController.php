<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/21
 * Time: 上午9:45
 */

namespace App\Api\Controllers;

use App\Api\Helper\WeiXinPay;
use App\Api\Requests\AppointmentDetailRequest;
use App\Api\Requests\AppointmentIdRequest;
use App\Api\Requests\AppointmentInsteadRequest;
use App\Api\Requests\AppointmentRequest;
use App\Api\Transformers\ReservationRecordTransformer;
use App\Api\Transformers\TimeLineTransformer;
use App\Api\Transformers\Transformer;
use App\Appointment;
use App\AppointmentMsg;
use App\Doctor;
use App\Order;
use App\User;
use Intervention\Image\Facades\Image;
use Tymon\JWTAuth\Exceptions\JWTException;

class AppointmentController extends BaseController
{
    private $wxPayClass;

    public function __construct()
    {
        $this->wxPayClass = new WeiXinPay();
    }

    /**
     * wait:
     * wait-1: 待患者付款
     * wait-2: 患者已付款，待医生确认
     * wait-3: 医生确认接诊，待面诊
     * wait-4: 医生改期，待患者确认
     * wait-5: 患者确认改期，待面诊
     * close:
     * close-1: 待患者付款
     * close-2: 医生过期未接诊,约诊关闭
     * close-3: 医生拒绝接诊
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

    public function index()
    {

    }

    /**
     * 预约:通过搜索找的医生/预约我的医生
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
         * 发起约诊信息记录
         */
        $doctor = Doctor::find($request['doctor']);
        $data = [
            'id' => $frontId . $nowId,
            'locums_id' => 1, //代理医生ID,1为平台代约,0为没有代约医生
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
            'expect_visit_date' => $request['date'],
            'expect_am_pm' => $request['am_or_pm'],
            'price' => $doctor->fee,
            'status' => 'wait-1' //新建约诊之后,进入待患者付款阶段
        ];

        /**
         * 推送消息记录
         */
        $msgData = [
            'appointment_id' => $frontId . $nowId,
            'locums_id' => 1, //代理医生ID； 1为平台代约
            'patient_name' => $data['patient_name'],
            'doctor_id' => $data['doctor_id'],
            'doctor_name' => $doctor->name,
            'status' => 'wait-1' //新建约诊之后,进入待患者付款阶段
        ];

        try {
            Appointment::create($data);
            AppointmentMsg::create($msgData);
        } catch (JWTException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }

        return ['id' => $frontId . $nowId];
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
         * 发起约诊信息记录
         */
        $data = [
            'id' => $frontId . $nowId,
            'locums_id' => isset($request['demand_hospital']) ? $request['locums_doctor'] : 0, //代理医生ID,1为平台代约,0为没有代约医生
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
            'doctor_id' => $request['doctor'],
            'request_mode' => isset($request['demand_hospital']) ? '医生代约' : '我的医生', //我的医生、找专家、医生代约
            'platform_or_doctor' => isset($request['demand_hospital']) ? 'd' : '',
            'doctor_or_patient' => 'p', //患者发起
            'expect_visit_date' => $request['date'],
            'expect_am_pm' => $request['am_or_pm'],
            'status' => 'wait-0' //请求代约
        ];

        /**
         * 推送消息记录
         */
        $msgData = [
            'appointment_id' => $frontId . $nowId,
            'locums_id' => $request['doctor'], //代理医生ID,0为平台代约
            'locums_name' => Doctor::find($request['doctor'])->first()->name, //代理医生姓名
            'patient_id' => $user->id,
            'patient_name' => $request['name'],
            'status' => 'wait-0' //新建约诊之后,进入待患者付款阶段
        ];

        try {
            Appointment::create($data);
            AppointmentMsg::create($msgData);
        } catch (JWTException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }

        return ['id' => $frontId . $nowId];
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
        $imgUrl = $this->saveImg($appointment->id, $request->file('img'));

        if (strlen($appointment->patient_imgs) > 0) {
            $appointment->patient_imgs .= ',' . $imgUrl;
        } else {
            $appointment->patient_imgs = $imgUrl;
        }

        $appointment->save();

        return ['url' => $imgUrl];
    }

    /**
     * 保存图片并另存一个压缩图片
     *
     * @param $appointmentId
     * @param $imgFile
     * @return string
     */
    public function saveImg($appointmentId, $imgFile)
    {
        $destinationPath =
            \Config::get('constants.CASE_HISTORY_SAVE_PATH') .
            date('Y') . '/' . date('m') . '/' .
            $appointmentId . '/';
        $filename = time() . '.jpg';

        $imgFile->move($destinationPath, $filename);

        $fullPath = $destinationPath . $filename;
        $newPath = str_replace('.jpg', '_thumb.jpg', $fullPath);

        Image::make($fullPath)->encode('jpg', 30)->save($newPath); //按30的品质压缩图片

        return '/' . $newPath;
    }

    /**
     * @param AppointmentDetailRequest $request
     * @return array|mixed
     */
    public function getDetailInfo(AppointmentDetailRequest $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $id = $request['id'];

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
        $locumsDoctor = array();
        if ($appointments->locums_id == '1') { //医脉平台代约
            $locumsDoctor['name'] = '医脉平台';
            $locumsDoctor = (object)$locumsDoctor;
        } elseif ($appointments->locums_id == '0') { //没有代约医生
            $locumsDoctor = null;
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

        $appointments['time_line'] = TimeLineTransformer::generateTimeLine($appointments, $doctors, $user->id, $locumsDoctor);
        $appointments['progress'] = TimeLineTransformer::generateProgressStatus($appointments->status);

        return Transformer::appointmentsTransform($appointments, $doctors);
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
         * 更新过期未支付的：
         */
        Appointment::where('patient_id', $user->id)
            ->where('is_pay', '0')
            ->where('status', 'wait-1')
            ->where('updated_at', '>', date("Y-m-d H:i:s", time() - 12 * 3600))
            ->update(['status' => 'close-1']); //close-1: 待患者付款，关闭

        /**
         * 处理一些不可描述的订单：
         */
        $needProcessAppointments = Appointment::getAllWait1AppointmentIdList($user->id, $user->phone);
        if ($needProcessAppointments != '' && $needProcessAppointments != null) {
            $payCtrl = new PayController();
            $payCtrl->batProcessing($needProcessAppointments);
        }

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
        $appointmentId = $request['id'];
        $appointment = Appointment::find($appointmentId);
        $doctorName = Doctor::find($appointment->doctor_id)->first()->name;

        //微信支付
        $data = ['message' => 'false'];
        if (!($appointment->price == 0 || $appointment->price == null || $appointment->price == '')) {
            $retData = $this->wxPay($appointment, $doctorName);
            if ($retData != false) {
                $appointment->is_pay = '1'; //已经支付记录
                $appointment->save();

                $data = $retData;
                return response()->json(compact('data'), 200);
            }
        }

        return response()->json(compact('data'), 500);
    }

    /**
     * 调用微信支付
     *
     * @param $appointment
     * @param $doctorName
     * @return array|bool
     */
    public function wxPay($appointment, $doctorName)
    {
        try {
            $order = Order::where('out_trade_no', $appointment->id)->first();
            if (empty($order->id)){
                $newOrder = [
                    'doctor_id' => $appointment->doctor_id,
                    'patient_id' => $appointment->patient_id,
                    'out_trade_no' => $appointment->id,
//                    'total_fee' => ($appointment->price) * 100,
                    'total_fee' => 1, //临时测试，只支付一分
                    'body' => '约诊',
                    'detail' => '',
                    'type' => '收入',
                    'status' => 'start', //start:开始; end:结束
                    'settlement_status' => '待结算'
                ];
                $order = Order::create($newOrder);
            }

            $timeExpire = date('YmdHis', (time() + 600)); //过期时间600秒
            return $this->wxPayClass->wxPay($order['out_trade_no'], $order['body'], $order['total_fee'], $timeExpire);
        } catch (JWTException $e) {
            return false;
        }
    }

    /**
     * 患者确认完成面诊
     *
     * @param AppointmentIdRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function complete(AppointmentIdRequest $request)
    {
        $appointmentId = $request['id'];
        Appointment::where('id', $appointmentId)->update(['status' => 'completed-1']);

        return response()->json(['success' => ''], 204);
    }
}
