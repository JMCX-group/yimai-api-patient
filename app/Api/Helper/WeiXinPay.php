<?php
/**
 * Created by PhpStorm.
 * User: liyingxuan
 * Date: 2016/8/16
 * Time: 19:52
 */

namespace App\Api\Helper;

/**
 * 微信支付
 * Class WeiXinPay
 * @package App\Api\Helper
 */
class WeiXinPay
{
    public $parameters;
    private $url;
    private $key;
    private $appId;
    private $mchId;
    private $notifyUrl;

    public function __construct()
    {
        $this->url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $this->key = 'YegenshenYejiquan197806212009111';
        $this->appId = 'wx2097e8b109f9dc35';
        $this->mchId = '1273535201';
        $this->notifyUrl = 'http://139.129.167.9/api/pay/notify_url';
    }

    /**
     * MD5加密
     *
     * @param $content
     * @param $key
     * @return string
     */
    public function wxMd5Sign($content, $key)
    {
        try {
            if (is_null($key)) {
                throw new \Exception("财付通签名key不能为空！");
            }
            if (is_null($content)) {
                throw new \Exception("财付通签名内容不能为空");
            }
            $signStr = $content . "&key=" . $key;
            return strtoupper(md5($signStr));
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * 数组转化为xml
     *
     * @param null $parameters
     * @return string
     */
    public function wxArrayToXml($parameters = NULL)
    {
        if (is_null($parameters)) {
            $parameters = $this->parameters;
        }

        if (!is_array($parameters) || empty($parameters)) {
            die("参数不为数组无法解析");
        }

        $xml = "<xml>";
        foreach ($parameters as $key => $val) {
            $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";

        return $xml;
    }

    /**
     * 微信支付。
     *
     * @param $outTradeNo
     * @param $body
     * @param $totalFee
     * @param $timeExpire
     * @return array
     */
    public function wxPay($outTradeNo, $body, $totalFee, $timeExpire)
    {
        // 参数数组
        $data = array(
            'appid' => $this->appId,
            'attach' => 'weixinpay',
            'body' => $body,
            'mch_id' => $this->mchId,
            'nonce_str' => $this->random('15'),
            'notify_url' => $this->notifyUrl,
            'out_trade_no' => $outTradeNo,
            'spbill_create_ip' => $this->get_real_ip(),
            'time_expire' => $timeExpire,
            'total_fee' => $totalFee,
            'trade_type' => 'APP'
        );

        $str = '';
        foreach ($data as $key => $value) {
            if ($str != '') {
                $str .= '&' . $key . '=' . $value;
            } else {
                $str = $key . '=' . $value;
            }
        }

        $data['sign'] = $this->wxMd5Sign($str, $this->key);
        $data = $this->wxArrayToXml($data);

        file_put_contents('pay.file', json_encode($data));
        $second = 30000;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $return = curl_exec($ch);
        curl_close($ch);

        //return $return;
        return $this->wxTwoSign($return);
    }

    /**
     *
     *
     * @param $wxData
     * @return array
     */
    public function wxTwoSign($wxData)
    {
        $wxData = (array)simplexml_load_string($wxData, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!empty($wxData)) {
            if ($wxData['return_code'] == 'SUCCESS') {
                $data['appid'] = $wxData['appid'];
                $data['noncestr'] = $wxData['nonce_str'];
                $data['package'] = "Sign=WXPay";
                $data['partnerid'] = $wxData['mch_id'];
                $data['prepayid'] = $wxData['prepay_id'];
                $data['timestamp'] = time();

                $str = '';
                foreach ($data as $key => $value) {
                    if ($str != '') {
                        $str .= '&' . $key . '=' . $value;
                    } else {
                        $str = $key . '=' . $value;
                    }
                }
                $data['sign'] = $this->wxMd5Sign($str, $this->key);
                return $data;
            }
            return $data = ['message' => 'false'];
        }
        return $data = ['message' => 'false'];
    }

    /**
     * 返回随机数
     *
     * @param $length
     * @param int $numeric
     * @return string
     */
    public function random($length, $numeric = 0)
    {
        if ($numeric) {
            $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
        } else {
            $hash = '';
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
            $max = strlen($chars) - 1;
            for ($i = 0; $i < $length; $i++) {
                $hash .= $chars[mt_rand(0, $max)];
            }
        }
        return $hash;
    }

    /**
     * 获取ip
     *
     * @return mixed
     */
    public function get_real_ip()
    {
        return $_SERVER["REMOTE_ADDR"];
    }
}
