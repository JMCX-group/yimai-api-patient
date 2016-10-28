<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/21
 * Time: 上午9:45
 */

namespace App\Api\Controllers;

use App\Api\Helper\WeiXinPay;
use App\Appointment;
use App\AppointmentMsg;
use App\Order;

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
        $outTradeNo = $wxData['out_trade_no'];
        $retCode = $wxData['return_code'];

        file_put_contents('pay.file', json_encode($wxData));

        if ($retCode == 'SUCCESS' || $retCode == 'TRADE_FINISHED') {
            $data['status'] = 'end';
        } else {
            $data['status'] = 'error';
        }

        $data['pay_time'] = time();
        $order = Order::where('out_trade_no', $outTradeNo)->first();
        if (!empty($order->id)) {
            $order->status = $data['status'];
            $order->time_expire = $data['pay_time'];
            $order->save();

            $appointment = Appointment::find($outTradeNo);
            if ($appointment->status == 'wait-1') {
                $appointment->status = 'wait-2';
                $appointment->save();

                /**
                 * 推送消息记录
                 */
                $msgData = [
                    'appointment_id' => $appointmentId,
                    'locums_id' => 99999999, //代理医生ID； 99999999为平台代约
                    'patient_name' => $appointment->patient_name,
                    'doctor_id' => $appointment->doctor_id,
                    'doctor_name' => $doctorName,
                    'status' => 'wait-2' //患者已付款
                ];
                AppointmentMsg::create($msgData);
            }


            /**
             * wait:
             * wait-0: 待代约医生确认
             * wait-1: 待患者付款
             * wait-2: 患者已付款，待医生确认
             * wait-3: 医生确认接诊，待面诊
             * wait-4: 医生改期，待患者确认
             * wait-5: 患者确认改期，待面诊
             */
        }

        echo 'SUCCESS';
    }
}
