<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 2017/2/16
 * Time: 19:27
 */

namespace App\Api\Controllers;

use App\Api\Requests\IdRequest;
use App\Api\Transformers\WithdrawTransformer;
use App\InvitedDoctor;
use App\PatientWithdrawRecord;
use App\User;

class WithdrawController extends BaseController
{
    /**
     * 明细
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function record()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $records = PatientWithdrawRecord::where('patient_id', $user->id)->get();

        $data = array();
        foreach ($records as $record) {
            array_push($data, WithdrawTransformer::transform($record));
        }

        return response()->json(compact('data'));
    }

    /**
     * 申请提现
     *
     * @param IdRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function application(IdRequest $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $bankId = $request['id'];
        $sumTotal = InvitedDoctor::sumTotal($user->id)[0]->total;
        $already = PatientWithdrawRecord::alreadyWithdraw($user->id)[0]->total;

        $data = [
            'patient_id' => $user->id,
            'total' => intval($sumTotal) - intval($already), //总收入减去已提现和已申请提现
            'status' => 'start', //提现状态，是否已提现；start为未提现，completed为成功，end为关闭
            'withdraw_bank_id' => $bankId, //提现的银行ID
            'withdraw_request_date' => date('Y-m-d H:i:s') //提现申请日期
        ];
        $record = PatientWithdrawRecord::create($data);

        if ($record) {
            $newAlready = PatientWithdrawRecord::alreadyWithdraw($user->id)[0]->total;
            $newAlready = empty($newAlready) ? 0 : intval($newAlready);
            $can = $sumTotal - $newAlready;

            $data = [
                'total' => $sumTotal,
                'can' => $can,
                'list' => InvitedDoctor::sumTotal_month($user->id),
            ];

            return response()->json(compact('data'));
        } else {
            return response()->json(['message' => '存储失败'], 500);
        }
    }
}
