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

class BankTransformer extends TransformerAbstract
{
    /**
     * @param $bank
     * @return array
     */
    public static function transform($bank)
    {
        return [
            'id' => $bank['id'],
            'name' => $bank['bank_name'],
            'info' => $bank['bank_info'],
            'no' => $bank['bank_no'],
            'status' => $bank['status'],
            'desc' => $bank['desc']
        ];
    }
}
