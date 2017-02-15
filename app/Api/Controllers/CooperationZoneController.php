<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 2017/2/15
 * Time: 16:58
 */

namespace App\Api\Controllers;

use App\Api\Transformers\UserTransformer;
use App\Patient;
use App\User;

class CooperationZoneController extends BaseController
{
    public function index()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }
    }

    /**
     * Get new code.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function create()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        if ($user->code) {
            $data = ['code' => Patient::getHealthConsultantCode($user->city_id, $user->code)];
        } else {
            $data = ['code' => Patient::generateHealthConsultantCode($user->city_id)];
        }

        return response()->json(compact('data'));
    }

    /**
     * 加入合作专区，生成健康顾问码
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function store()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        if (!($user->code)) {
            $code = Patient::basicHealthConsultantCode($user->city_id);
            $user->code = $code;
            $user->save();
        }

        $data = UserTransformer::transformUser($user);

        return response()->json(compact('data'));
    }

    public function myList()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

    }
}
