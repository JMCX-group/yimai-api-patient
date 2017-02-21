<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/18
 * Time: 下午4:08
 */

namespace App\Api\Transformers;

use App\College;
use App\DeptStandard;
use App\Hospital;
use App\Patient;
use App\User;
use App\Province;
use App\City;
use League\Fractal\TransformerAbstract;

/**
 * Class UserTransformer
 * @package App\Api\Transformers
 */
class UserTransformer extends TransformerAbstract
{
    /**
     * @param User $user
     * @return array
     */
    public function transform(User $user)
    {
        return self::transformUser($user);
    }

    /**
     * @param $user
     * @return array
     */
    public static function transformUser($user)
    {
        // ID convert id:name
        self::idToName($user);

        return [
            'id' => $user['id'],
            'phone' => $user['phone'],
            'device_token' => $user['device_token'],
            'name' => $user['name'],
            'nickname' => $user['nickname'],
            'head_url' => ($user['avatar'] == '') ? null : $user['avatar'],
            'sex' => $user['gender'],
            'birthday' => $user['birthday'],
            'province' => $user['province_id'],
            'city' => $user['city_id'],
            'code' => ($user['code']) ? Patient::getHealthConsultantCode($user['city_id']['id'], $user['code']) : $user['code'],
            'tags' => $user['tag_list'],
            'blacklist' => $user['blacklist'],
            'protocol_read' => $user['protocol_read']
        ];
    }

    /**
     * 删除过期时间
     *
     * @param $data
     * @return string
     */
    public static function delOutdated($data)
    {
        if ($data == '' || $data == null) {
            return null;
        }

        $now = time();
        $newData = array();
        foreach ($data as $item) {
            if (strtotime($item['date']) > $now) {
                array_push($newData, $item);
            }
        }

        return json_encode($newData);
    }

    /**
     * ID to id:name.
     *
     * @param $user
     * @return mixed
     */
    public static function idToName($user)
    {
        if (!empty($user['province_id'])) {
            $user['province_id'] = Province::find($user['province_id']);
        }

        if (!empty($user['city_id'])) {
            $user['city_id'] = City::select('id', 'name')->find($user['city_id']);
        }

        if (!empty($user['hospital_id'])) {
            $user['hospital_id'] = Hospital::select('id', 'name')->find($user['hospital_id']);
        }

        if (!empty($user['dept_id'])) {
            $user['dept_id'] = DeptStandard::select('id', 'name')->find($user['dept_id']);
        }

        if (!empty($user['college_id'])) {
            $user['college_id'] = College::select('id', 'name')->find($user['college_id']);
        }

        // Spell dp code.
        if (!empty($user['dp_code'])) {
            $user['dp_code'] = User::getDpCode($user['id']);
        }

        return $user;
    }
}
