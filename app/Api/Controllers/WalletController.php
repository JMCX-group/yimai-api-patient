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
use App\Config;
use App\Doctor;
use App\PatientRechargeRecord;
use App\PatientWallet;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            $rate = $this->getRate($appointment);

            /**
             * 费用计算，和约诊文案那段一样：
             */
            $receptionFee = $appointment->price; //诊疗费; 元转分
            $platformFee = $receptionFee * $rate; //平台费
            $totalFee = $receptionFee + $platformFee;

            $appointment->price = $totalFee; //修改实际需要支付的金额

            array_push($retAppointments, WalletTransformer::appointmentTransform($appointment));
        }

        $data = [
            'wallet' => WalletTransformer::retTransform($walletInfo),
            'appointment_list' => $retAppointments
        ];

        return response()->json(compact('data'));
    }

    /**
     * 计算费率
     *
     * @param $appointment
     * @return float
     */
    public function getRate($appointment)
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

        $data = array();

        $recharges = PatientRechargeRecord::where('patient_id', $user->id)->where('status', 'end')->orderBy('created_at', 'DESC')->get();
        foreach ($recharges as $recharge) {
            $tmpData = TransactionRecordTransformer::transformData_recharge($recharge);
            array_push($data, $tmpData);
        }

        $record = AppointmentFee::where('patient_id', $user->id)->orderBy('created_at', 'DESC')->get();
        foreach ($record as $item) {
            $recordData = TransactionRecordTransformer::transformData_fee($item);
            array_push($data, $recordData);
        }

        /**
         * 将多维数组按照键值排序
         */
        array_multisort(array_column($data, 'time'), SORT_DESC, $data);

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
                $data = $this->wxPayClass->wxPay($outTradeNo, $body, ($fee / 10000), $timeExpire, 'recharge'); //TODO 测试期间除以10000
                return response()->json(compact('data'), 200);
            } catch (\Exception $e) {
                Log::info('recharge-record-err', ['context' => $e->getMessage()]);
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
            return response()->json(['data' => ['info' => '状态不对，请刷新再请求']], 403);
        }
        $doctor = Doctor::find($appointment->doctor_id);

        /**
         * 没有支付金额修复：
         */
        if ($appointment->price == null) {
            $appointment->price = $doctor->fee;
        }

        $ret = $this->payAndPush($user, $appointment, $doctor);
        if ($ret['status_code'] == '200') { //成功
            $data = [
                'info' => $ret['info'],
                'appointment_info' => $ret['appointment_info'],
            ];

            return response()->json(compact('data'), 200);
        } elseif ($ret['status_code'] == '400') { //余额不足
            $data = ['info' => $ret['info']];

            return response()->json(compact('data'), 400);

        } else {
            return response()->json(['error' => $ret['info']], 500); //报错
        }
    }

    /**
     * 支付list
     *
     * @param Request $request
     * @return array|mixed
     */
    public function payList(Request $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $requestIdList = $request->get('id_list');
        if (substr($requestIdList, strlen($requestIdList) - 1) == ',') {
            $requestIdList = substr($requestIdList, 0, strlen($requestIdList) - 1);
        }
        $idList = explode(',', $requestIdList);
        $retData = array();

        /**
         * 批量处理
         */
        foreach ($idList as $id) {
            $appointment = Appointment::find($id);
            if ($appointment->patient_id == $user->id && $appointment->status == 'wait-1') {
                $doctor = Doctor::find($appointment->doctor_id);

                array_push($retData, $this->payAndPush($user, $appointment, $doctor));
            } else {
                array_push($retData, ['info' => '状态不对，请刷新再请求', 'appointment_id' => $appointment->id, 'status_code' => '400']);
            }
        }

        $data = $retData;

        return response()->json(compact('data'), 200);
    }

    /**
     * @param $user
     * @param $appointment
     * @param $doctor
     * @return array
     */
    public function payAndPush($user, $appointment, $doctor)
    {
        /**
         * 支付信息记录和推送
         */
        try {
            /**
             * 钱包信息判断
             */
            $wallet = PatientWallet::where('patient_id', $user->id)->first();
            if (isset($wallet->patient_id) && $wallet->total > $appointment->price) {
                $rate = $this->getRate($appointment);

                /**
                 * 费用计算，和约诊文案那段一样：
                 */
                $receptionFee = $appointment->price * 100; //诊疗费; 元转分
                $platformFee = $receptionFee * $rate; //平台费
                $totalFee = $receptionFee + $platformFee;

                /**
                 * 余额支付信息：
                 */
                $appointmentFeeData = [
                    'doctor_id' => $appointment->doctor_id,
                    'patient_id' => $appointment->patient_id,
                    'locums_id' => $appointment->locums_id,
                    'appointment_id' => $appointment->id,
                    'total_fee' => $totalFee,
                    'reception_fee' => $receptionFee,
                    'platform_fee' => $platformFee,
                    'intermediary_fee' => 0, //中介费
                    'guide_fee' => 0, //导诊费
                    'default_fee_rate' => 0, //违约费率
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
                MsgAndNotification::pushAppointmentMsg_doctor($doctor, $appointment); //向医生端推送消息

                return [
                    'info' => '支付成功',
                    'appointment_id' => '' . $appointment->id,
                    'appointment_info' => AppointmentController::appointmentDetailInfo($appointment->id, $user->id),
                    'status_code' => '200'
                ];
            } else {
                return ['info' => '余额不足，请去充值', 'appointment_id' => $appointment->id, 'status_code' => '400'];
            }
        } catch (\Exception $e) {
            return ['info' => $e->getMessage(), 'appointment_id' => $appointment->id, 'status_code' => '500'];
        }
    }
}
