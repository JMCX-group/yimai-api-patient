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
use App\Appointment;
use App\AppointmentFee;
use App\Doctor;
use App\PatientRechargeRecord;
use App\PatientWallet;
use Illuminate\Http\Request;
use App\Order;
use Illuminate\Support\Facades\Log;

class PayController extends BaseController
{
    /**
     * @var WeiXinPay
     */
    private $wxPay;

    /**
     * PaymentController constructor.
     */
    public function __construct()
    {
        $this->wxPay = new WeiXinPay();
    }

    /**
     * 微信支付回調函數
     */
    public function notifyUrl()
    {
        $wxData = (array)simplexml_load_string(file_get_contents('php://input'), 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($wxData[0]) {
            Log::info('appointment-pay', ['context' => json_encode($wxData)]); //测试期间
            if ($wxData['return_code'] == 'SUCCESS' && $wxData['result_code'] == 'SUCCESS') {
                if ($wxData['attach'] == 'recharge') {
                    $this->rechargeProcessing($wxData);
                } else {
                    $this->paymentProcessing($wxData);
                }

                echo 'SUCCESS';
            } else {
                echo 'FAIL';
            }
        } else {
            echo 'NULL';
        }
    }

    /**
     * @param Request $request
     * @return array
     */
    public function wxPayOrderQuery(Request $request)
    {
        $wxData = $this->wxPay->wxOrderQuery($request['id']);
        if ($wxData['return_code'] == 'SUCCESS' && $wxData['trade_state'] == 'SUCCESS') {
            $data = $this->paymentProcessing($wxData);
        } else {
            $data = ['result' => 'fail', 'debug' => $wxData];
        }

        return response()->json(compact('data'));
    }

    /**
     * 批量处理一些订单
     *
     * @param $idArr
     */
    public function batProcessing($idArr)
    {
        foreach ($idArr as $item) {
            $wxData = $this->wxPay->wxOrderQuery($item->id);
            if ($wxData['return_code'] == 'SUCCESS' && $wxData['result_code'] == 'SUCCESS' && $wxData['trade_state'] == 'SUCCESS') {
                $this->paymentProcessing($wxData);
            } else {
                Log::info('wx-order-query-error', ['context' => json_encode($wxData)]);
                if ($wxData['trade_state'] == 'NOTPAY') {
                    $this->notPayProcessing($wxData);
                }
            }
        }
    }

    /**
     * 处理未支付的
     *
     * @param $wxData
     */
    public function notPayProcessing($wxData)
    {
        $outTradeNo = $wxData['out_trade_no'];
        $order = Order::where('out_trade_no', $outTradeNo)->first();
        if (!empty($order->id)) {
            $order->ret_data = json_encode($wxData);
            $order->save();

            $appointment = Appointment::find($outTradeNo);
            if (!empty($appointment->id)) {
                if ($appointment->status == 'wait-1') {
                    $appointment->is_pay = '0';
                    $appointment->save();
                }
            }
        }
    }

    /**
     * 统一处理
     *
     * @param $wxData
     * @return array
     */
    public function paymentProcessing($wxData)
    {
        $outTradeNo = $wxData['out_trade_no'];

        $appointmentFee = AppointmentFee::where('appointment_id', $outTradeNo)->first();
        if (!empty($appointmentFee->id)) {
            $appointmentFee->status = 'end';
            $appointmentFee->time_expire = $wxData['time_end'];
            $appointmentFee->ret_data = json_encode($wxData);
            $appointmentFee->save();

            $appointment = Appointment::find($outTradeNo);
            if ($appointment->status == 'wait-1') {
                $appointment->is_pay = '1';
                $appointment->status = 'wait-2';
                $appointment->transaction_id = $wxData['transaction_id'];
                $appointment->save();

                MsgAndNotification::sendAppointmentsMsg($appointment); //推送消息记录

                $doctor = Doctor::where('id', $appointment['doctor_id'])->first();
                if (isset($doctor->id) && ($doctor->device_token != '' && $doctor->device_token != null)) {
                    MsgAndNotification::pushAppointmentMsg($doctor->device_token, $appointment->status, $appointment->id, 'doctor'); //向医生端推送消息
                }
            }

            $data = ['result' => 'success'];
        } else {
            $data = ['result' => 'fail', 'debug' => '木有订单信息啊'];
        }

        return $data;
    }

    /**
     * 充值处理
     *
     * @param $wxData
     * @return array
     */
    public function rechargeProcessing($wxData)
    {
        $outTradeNo = $wxData['out_trade_no'];

        /**
         * 订单状态更新
         */
        $order = PatientRechargeRecord::where('out_trade_no', $outTradeNo)->first();
        if (!empty($order->id)) {
            $order->status = 'end';
            $order->time_expire = $wxData['time_end'];
            $order->ret_data = json_encode($wxData);
            $order->save();

            /**
             * 钱包信息更新
             */
            $wallet = PatientWallet::where('patient_id', $order->patient_id)->first();
            $wallet->total += ($wxData['total_fee'] / 100);
            $wallet->save();

            $data = ['result' => 'success'];
        } else {
            $data = ['result' => 'fail'];
        }

        return $data;
    }
}
