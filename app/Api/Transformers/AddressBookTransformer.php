<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/18
 * Time: 下午4:08
 */

namespace App\Api\Transformers;

use League\Fractal\TransformerAbstract;

class AddressBookTransformer extends TransformerAbstract
{
    public static function transform($data)
    {
        return [
            'view_list' => json_decode($data['view_list'], true),
            'invited_list' => json_decode($data['invited_list'], true),
            'doctor_list' => json_decode($data['doctor_list'], true),
            'upload_time' => $data['upload_time']
        ];
    }
}
