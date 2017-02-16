<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 2017/2/15
 * Time: 16:58
 */

namespace App\Api\Controllers;

use App\Api\Transformers\UserTransformer;
use App\InvitedDoctor;
use App\Patient;
use App\PatientWithdrawRecord;
use App\User;
use Illuminate\Http\Request;

class CooperationZoneController extends BaseController
{
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

    /**
     * 获取近3个月收益数据/历史收益数据
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function income()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $sumTotal = InvitedDoctor::sumTotal($user->id)[0]->total;
        $already = PatientWithdrawRecord::alreadyWithdraw($user->id)[0]->total;
        $already = empty($already) ? 0 : intval($already);
        $can = $sumTotal - $already;

        $data = [
            'total' => $sumTotal,
            'can' => $can,
            'list' => InvitedDoctor::sumTotal_month($user->id),
        ];

        return response()->json(compact('data'));
    }

    /**
     * 每个月的收益数据
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function incomeDetail(Request $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        if (!isset($request['date']) || $request['date'] == '') {
            return response()->json(['message' => '时间不能为空'], 400);
        }

        $date = $request['date'];
        $year = substr($date, 0, 4);
        $month = substr($date, 7, 2);
        $data = [
            'total' => InvitedDoctor::sumMonthTotal($user->id, $year, $month)[0]->total,
            'list' => InvitedDoctor::monthTotal($user->id, $year, $month)
        ];

        return response()->json(compact('data'));
    }

    /**
     * 邀请的医生列表
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function invited()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $data = InvitedDoctor::myInvitedList($user->id);

        return response()->json(compact('data'));
    }
}
