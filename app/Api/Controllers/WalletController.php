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
use App\Api\Requests\AppointmentIdRequest;
use App\Api\Requests\RechargeRequest;
use App\Api\Transformers\TransactionRecordTransformer;
use App\Api\Transformers\WalletTransformer;
use App\Appointment;
use App\AppointmentFee;
use App\Doctor;
use App\Order;
use App\PatientRechargeRecord;
use App\PatientWallet;
use App\User;
use Tymon\JWTAuth\Exceptions\JWTException;

class WalletController extends BaseController
{
    private $wxPayClass;

    public function __construct()
    {
        $this->wxPayClass = new WeiXinPay();
    }

    /**
     * 钱包基础信息
     *
     * @return \Dingo\Api\Http\Response|mixed
     */
    public function info()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        /**
         * 查询患者充值总额：
         */
        $rechargeRecords = PatientRechargeRecord::rechargeTotal($user->id)[0];

        /**
         * 查询患者冻结总额：
         */
        $freezeFees = AppointmentFee::getfreezeFees($user->id)[0];

        /**
         * 查询患者消费总额：
         */
        $totalFees = AppointmentFee::getTotalFees($user->id)[0];
        $defaultFees = AppointmentFee::getDefaultFees($user->id)[0];
        $total = $totalFees->fee + $defaultFees->fee;

        /**
         * 查询患者钱包基本信息：
         */
        $walletInfo = PatientWallet::where('patient_id', $user->id)->first();
        if (!isset($walletInfo->patient_id)) {
            $walletInfo = new PatientWallet();
            $walletInfo->patient_id = $user->id;
        }
        $walletInfo->total = (($rechargeRecords->total) / 100) - ($total / 100) - ($freezeFees->fee / 100); //分转元
        $walletInfo->freeze = ($freezeFees->fee) / 100; //分转元
        $walletInfo->save();

        /**
         * 患者未支付的列表：
         */
        $appointments = Appointment::where('appointments.patient_id', $user->id)
            ->leftJoin('doctors', 'doctors.id', '=', 'appointments.doctor_id')
            ->leftJoin('dept_standards', 'dept_standards.id', '=', 'doctors.dept_id')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'doctors.hospital_id')
            ->select('appointments.*', 'doctors.name', 'doctors.avatar', 'doctors.title', 'doctors.auth', 'dept_standards.name AS dept_name', 'hospitals.name AS hospital_name')
            ->where('appointments.status', 'wait-1')
            ->orderBy('updated_at', 'desc')
            ->get();
        $retAppointments = array();
        foreach ($appointments as $appointment) {
            array_push($retAppointments, WalletTransformer::appointmentTransform($appointment));
        }

        $data = [
            'wallet' => WalletTransformer::retTransform($walletInfo),
            'appointment_list' => $retAppointments
        ];

        return response()->json(compact('data'));
    }

    /**
     * 收支明细列表
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function record()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $record = AppointmentFee::where('patient_id', $user->id)->orderBy('created_at', 'DESC')->get();
        $data = array();
        foreach ($record as $item) {
            $recordData = TransactionRecordTransformer::transformData_fee($item);
            array_push($data, $recordData);
        }

        return response()->json(compact('data'));
    }

    /**
     * 用户充值
     *
     * @param RechargeRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function recharge(RechargeRequest $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        /**
         * 支付信息获取
         */
        $outTradeNo = date('YmdHis') . str_pad($user->id, 6, '0', STR_PAD_LEFT);
        $body = '医者脉连-会员充值';
        $fee = $request['fee'] * 100; //单位要换算成分
        $timeExpire = date('YmdHis', (time() + 600)); //过期时间600秒

        /**
         * 充值记录入库
         */
        $rechargeRecord = [
            'patient_id' => $user->id,
            'out_trade_no' => $outTradeNo,
            'total_fee' => $fee,
            'body' => $body,
            'detail' => '',
            'time_start' => date('Y-m-d H:i:s'),
            'source' => 'WeChat',
            'status' => 'start' //start:开始; end:结束
        ];
        PatientRechargeRecord::create($rechargeRecord);

        /**
         * 微信支付
         */
        if ($fee > 0) {
            try {
                $data = $this->wxPayClass->wxPay($outTradeNo, $body, $fee, $timeExpire, 'recharge');
                return response()->json(compact('data'), 200);
            } catch (JWTException $e) {
                return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
            }
        }

        return response()->json(['error' => '金额异常'], 400);
    }

    /**
     * 使用余额支付
     *
     * @param AppointmentIdRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function pay(AppointmentIdRequest $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $appointmentId = $request['id'];
        $appointment = Appointment::find($appointmentId);
        if ($appointment->patient_id != $user->id || $appointment->status != 'wait-1') {
            return response()->json(['message' => '状态不对，请刷新再请求'], 400);
        }
        $doctor = Doctor::find($appointment->doctor_id);

        /**
         * 没有支付金额修复：
         */
        if ($appointment->price == null) {
            $appointment->price = $doctor->fee;
        }

        /**
         * 支付信息记录和推送
         */
        try {
            /**
             * 钱包信息判断
             */
            $wallet = PatientWallet::where('patient_id', $user->id)->first();
            if (isset($wallet->patient_id) && $wallet->total > $appointment->price) {
                /**
                 * 余额支付信息：
                 */
                $appointmentFeeData = [
                    'doctor_id' => $appointment->doctor_id,
                    'patient_id' => $appointment->patient_id,
                    'locums_id' => $appointment->locums_id,
                    'appointment_id' => $appointment->id,
                    'total_fee' => $appointment->price * 100, //元转分
                    'reception_fee' => 0, //诊疗费
                    'platform_fee' => 0, //平台费
                    'intermediary_fee' => 0, //中介费
                    'guide_fee' => 0, //导诊费
                    'default_fee' => 0, //违约费用
                    'status' => 'paid' //资金状态：paid（已支付）、completed（已完成）、cancelled（已取消）
                ];
                AppointmentFee::create($appointmentFeeData);

                /**
                 * 更新订单支付信息
                 */
                $appointment->is_pay = '1'; //已经支付记录
                $appointment->status = 'wait-2'; //使用余额支付无需等待微信支付回调
                $appointment->save();

                /**
                 * 钱包信息更新
                 */
                $wallet->total -= $appointment->price;
                $wallet->freeze += $appointment->price;
                $wallet->save();

                /**
                 * 推送消息
                 */
                MsgAndNotification::sendAppointmentsMsg($appointment); //推送消息记录

                if (isset($doctor->id) && ($doctor->device_token != '' && $doctor->device_token != null)) {
                    MsgAndNotification::pushAppointmentMsg($doctor->device_token, $appointment->status, $appointment->id, 'doctor'); //向医生端推送消息
                }

                $data = [
                    'info' => '支付成功',
                    'appointment_info' => AppointmentController::appointmentDetailInfo($appointment->id, $user->id)
                ];

                return response()->json(compact('data'), 200);
            } else {
                $data = ['info' => '余额不足，请去充值'];

                return response()->json(compact('data'), 400);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
