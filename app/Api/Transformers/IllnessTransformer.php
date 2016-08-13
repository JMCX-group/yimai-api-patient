<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/18
 * Time: 下午4:08
 */

namespace App\Api\Transformers;

use App\Illness;
use League\Fractal\TransformerAbstract;

class IllnessTransformer extends TransformerAbstract
{
    public function transform(Illness $illness)
    {
        return [
            'id' => $illness['id'],
            'name' => $illness['name']
        ];
    }
}
