<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/18
 * Time: 下午4:08
 */

namespace App\Api\Transformers;

use League\Fractal\TransformerAbstract;

class TransactionRecordTransformer extends TransformerAbstract
{
    public function transform()
    {
        //
    }

    /**
     * 返回数据变形
     *
     * @param $record
     * @return array
     */
    public static function transformData($record)
    {
        return [
            'id' => $record['id'],
            'name' => $record['body'],
            'transaction_id' => $record['out_trade_no'],
            'price' => $record['total_fee'] / 100, //单位：分
            'type' => $record['type'],
            'status' => $record['settlement_status'],
            'time' => $record['created_at']->format('Y-m-d H:i:s')
        ];
    }

    /**
     * 返回数据变形
     *
     * @param $record
     * @return array
     */
    public static function transformData_fee($record)
    {
        return [
            'id' => $record['id'],
            'name' => '约诊',
            'transaction_id' => $record['appointment_id'],
            'price' => $record['total_fee'] / 100, //单位：分
            'type' => '支出',
            'status' => $record['settlement_status'],
            'time' => $record['created_at']->format('Y-m-d H:i:s')
        ];
    }

    /**
     * 充值变形
     *
     * @param $recharge
     * @return array
     */
    public static function transformData_recharge($recharge)
    {
        return [
            'id' => $recharge['id'],
            'name' => '充值',
            'transaction_id' => $recharge['out_trade_no'],
            'price' => $recharge['total_fee'] / 100, //单位：分
            'type' => '收入',
            'status' => $recharge['status'],
            'time' => $recharge['created_at']->format('Y-m-d H:i:s')
        ];
    }
}
