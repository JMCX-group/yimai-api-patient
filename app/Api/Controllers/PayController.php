<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/21
 * Time: 上午9:45
 */

namespace App\Api\Controllers;

use App\Api\Helper\WeiXinPay;

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

    public function notifyUrl()
    {
        $wxData = (array)simplexml_load_string(file_get_contents('php://input'), 'SimpleXMLElement', LIBXML_NOCDATA);
        $outTradeNo = $wxData['out_trade_no'];
        $retCode = $wxData['return_code'];

        $time = time();
        file_put_contents($time . 'pay.file', json_encode($wxData));
//
//        /**
//         * 修改状态：
//         */
//        if ($appointment->status == 'wait-1') {
//            $appointment->status = 'wait-2';
//            $appointment->save();
//        }
//
//        /**
//         * 推送消息记录
//         */
//        $msgData = [
//            'appointment_id' => $appointmentId,
//            'locums_id' => 99999999, //代理医生ID； 99999999为平台代约
//            'patient_name' => $appointment->patient_name,
//            'doctor_id' => $appointment->doctor_id,
//            'doctor_name' => $doctorName,
//            'status' => 'wait-2' //患者已付款
//        ];
//        AppointmentMsg::create($msgData);
//
//        /**
//         * 返回数据：
//         */
//        $data = [
//            'debug' => '还未接入，只是测试；支付价格是：' . $appointment->price,
//            'id' => $appointmentId
//        ];
    }
}
