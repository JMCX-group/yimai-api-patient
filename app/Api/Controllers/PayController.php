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

    public function pay()
    {

    }

    public function notifyUrl()
    {

    }
}
