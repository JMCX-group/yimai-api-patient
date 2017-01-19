<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/21
 * Time: 上午9:45
 */

namespace App\Api\Controllers;

use App\Api\Helper\WeiXinPay;
use App\Api\Requests\RechargeRequest;
use App\Api\Transformers\TransactionRecordTransformer;
use App\Api\Transformers\WalletTransformer;
use App\Appointment;
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

        $walletInfo = PatientWallet::where('patient_id', $user->id)->first();
        if (!isset($walletInfo->patient_id)) {
            $walletInfo = new PatientWallet();
            $walletInfo->patient_id = $user->id;
            $walletInfo->save();
        }

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

        $record = Order::where('patient_id', $user->id)->orderBy('created_at', 'DESC')->get();
        $data = array();
        foreach ($record as $item) {
            $recordData = TransactionRecordTransformer::transformData($item);
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
                $data = $this->wxPayClass->wxPay($outTradeNo, $body, $fee, $timeExpire);
                //TODO ：回调还需要处理
                return response()->json(compact('data'), 200);
            } catch (JWTException $e) {
                return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
            }
        }

        return response()->json(['error' => '金额异常'], 400);
    }
}
