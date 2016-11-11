<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/18
 * Time: 下午4:08
 */

namespace App\Api\Transformers;

use App\PatientWallet;
use League\Fractal\TransformerAbstract;

class WalletTransformer extends TransformerAbstract
{
    public function transform(PatientWallet $wallet)
    {
        return [
            'total' => $wallet['total'],
            'freeze' => $wallet['freeze']
        ];
    }

    /**
     * 返回变形数据
     *
     * @param $wallet
     * @return array
     */
    public static function retTransform($wallet)
    {
        return [
            'total' => ($wallet['total'] == null) ? '0.00' : $wallet['total'],
            'freeze' => ($wallet['freeze'] == null) ? '0.00' : $wallet['freeze']
        ];
    }
}
