<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/21
 * Time: 上午9:45
 */

namespace App\Api\Controllers;

use App\Api\Transformers\AppointmentMsgTransformer;
use App\AppointmentMsg;
use App\User;
use Illuminate\Http\Request;

class AppointmentMsgController extends BaseController
{
    /**
     * @return array|mixed
     */
    public function index()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $allMsg = AppointmentMsg::where('patient_id', $user->id)
            ->whereIn('status', array('wait-1', 'wait-3', 'wait-4', 'close-2', 'close-3', 'close-4', 'cancel-2', 'cancel-4', 'cancel-7'))
            ->orderBy('id', 'DESC')
            ->get();

        $retData = array();
        foreach ($allMsg as $item) {
            $text = AppointmentMsgTransformer::transformerMsgList($item);

            if ($text && $text['text']) {
                array_push($retData, $text);
            }
        }

        return ['data' => $retData];
    }

    /**
     * 未读。
     *
     * @return array|mixed
     */
    public function newMessage()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $allMsg = AppointmentMsg::where('patient_id', $user->id)
            ->where('patient_read', 0)
            ->whereIn('status', array('wait-1', 'wait-3', 'wait-4', 'close-2', 'close-3', 'close-4', 'cancel-2', 'cancel-4', 'cancel-7'))
            ->orderBy('id', 'DESC')
            ->get();

        $retData = array();
        foreach ($allMsg as $item) {
            $text = AppointmentMsgTransformer::transformerMsgList($item);

            if ($text && $text['text']) {
                array_push($retData, $text);
            }
        }

        return ['data' => $retData];
    }

    /**
     * 已读状态更新。
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function readMessage(Request $request)
    {
        $msg = AppointmentMsg::find($request['id']);
        if ($msg) {
            $msg->patient_read = 1;
            $msg->save();
            return response()->json(['success' => ''], 204);
        } else {

            return response()->json(['message' => '没有查到此消息'], 400);
        }
    }

    /**
     * All read.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function allRead()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        AppointmentMsg::where('patient_id', $user->id)->update(['patient_read' => 1]);

        return response()->json(['success' => ''], 204);
    }
}
