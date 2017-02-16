<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/18
 * Time: 下午4:08
 */

namespace App\Api\Transformers;

use App\DoctorBank;
use League\Fractal\TransformerAbstract;

class WithdrawTransformer extends TransformerAbstract
{
    /**
     * @param $records
     * @return array
     */
    public static function transform($records)
    {
        if ($records['status'] == 'start') {
            $status = '已申请';
        } elseif ($records['status'] == 'completed') {
            $status = '已提现';
        } else {
            $status = '已关闭';
        }

        return [
            'total' => $records['total'],
            'status' => $status,
            'date' => $records['withdraw_request_date']
        ];
    }
}
