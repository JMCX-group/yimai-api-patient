<?php
namespace App\Api\Helper;

/**
 * 微信支付
 *
 * Class WeiXinPay
 * @package App\Api\Helper
 */
class WeiXinPay
{
    /**
     * @var
     */
    public $parameters;
    /**
     * @var
     */
    private $url;
    /**
     * @var
     */
    private $key;
    /**
     * @var
     */
    private $appid;
    /**
     * @var
     */
    private $mch_id;
    /**
     * @var
     */
    private $notify_url;

    /**
     * WeiXinPay constructor.
     */
    public function __construct()
    {
//        $data = file_get_contents('../weixinpay.txt');
//        $array = explode(',',$data);
//        $this->url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
//        $this->key = explode(':',$array[1])[1];
//        $this->appid = explode(':',$array[0])[1];
//        $this->mch_id = explode(':',$array[2])[1];
//        $this->notify_url = \Config::get('constants.WEIXINNOTIFYURL');
    }

    /**
     * MD5加密
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
     * 微信支付
     */
    public function weixinpay($order_num, $act_name, $money, $time_expire)
    {
        // 参数数组
        $data = array(
            'appid' => $this->appid,
            'attach' => 'weixinpay',
            'body' => $act_name,
            'mch_id' => $this->mch_id,
            'nonce_str' => $this->random('15'),
            'notify_url' => $this->notify_url,
            'out_trade_no' => $order_num,
            'spbill_create_ip' => $this->get_real_ip(),
            'time_expire' => $time_expire,
            'total_fee' => $money,
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
        return $this->weixintwosign($return);
    }

    /**
     * @param $datawx
     * @return array
     */
    public function weixintwosign($datawx)
    {
        $datawx = (array)simplexml_load_string($datawx, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!empty($datawx)) {
            if ($datawx['return_code'] == 'SUCCESS') {
                $data['appid'] = $datawx['appid'];
                $data['noncestr'] = $datawx['nonce_str'];
                $data['package'] = "Sign=WXPay";
                $data['partnerid'] = $datawx['mch_id'];
                $data['prepayid'] = $datawx['prepay_id'];
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
            return $data = ['message' => '错误！'];
        }
        return $data = ['message' => '错误！'];
    }

    /**
     * 返回随机数
     */
    public function random($length, $numeric = 0)
    {
        if ($numeric) {
            $hash = sprintf('%0' . length . 'd', mt_rand(0, pow(10, $length) - 1));
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
     * @return mixed
     */
    public function get_real_ip()
    {
        return $_SERVER["REMOTE_ADDR"];;
    }
}
