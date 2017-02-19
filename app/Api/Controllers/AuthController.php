<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/18
 * Time: 下午5:45
 */

namespace App\Api\Controllers;

use App\Api\Helper\SmsContent;
use App\Api\Requests\AuthRequest;
use App\Api\Requests\InviterRequest;
use App\Api\Requests\ResetPwdRequest;
use App\Api\Transformers\UserTransformer;
use App\Appointment;
use App\User;
use Illuminate\Http\Request;
use Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends BaseController
{
    /**
     * User auth.
     *
     * @param Request $request
     * @return mixed
     */
    public function authenticate(Request $request)
    {
        /*
         * 用于自定义用户名和密码字段:
         */
        $credentials = [
            'phone' => $request->get('phone'),
            'password' => $request->get('password')
        ];

        try {
            // attempt to verify the credentials and create a token for the user
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['message' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['message' => 'could_not_create_token'], 500);
        }

        // all good so return the token
        return response()->json(compact('token'));
    }

    /**
     * User register.
     *
     * @param AuthRequest $request
     * @return mixed
     */
    public function register(AuthRequest $request)
    {
        $domain = \Config::get('constants.DOMAIN');
        $newUser = [
            'phone' => $request->get('phone'),
            'password' => bcrypt($request->get('password')),

            'avatar' => $domain . '/uploads/avatar/default.jpg'
        ];
        $user = User::create($newUser);
        $this->setUserIdToAppointment($user->id, $newUser['phone']);
        $token = JWTAuth::fromUser($user);

        return response()->json(compact('token'));
    }

    /**
     * 患者注册,自动更新约诊ID
     *
     * @param $id
     * @param $phone
     */
    public function setUserIdToAppointment($id, $phone)
    {
        Appointment::where('patient_phone', $phone)->update(['patient_id' => $id]);
    }

    /**
     * User reset password.
     *
     * @param ResetPwdRequest $request
     * @return mixed
     */
    public function resetPassword(ResetPwdRequest $request)
    {
        $userId = User::where('phone', $request->get('phone'))
            ->update(['password' => bcrypt($request->get('password'))]);

        if (!$userId) {
            return response()->json(['message' => '该手机号未注册'], 404);
        }

        $credentials = [
            'phone' => $request->get('phone'),
            'password' => $request->get('password')
        ];

        try {
            // attempt to verify the credentials and create a token for the user
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['message' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['message' => 'could_not_create_token'], 500);
        }

        // all good so return the token
        return response()->json(compact('token'));

//        $user = User::where('phone', $request->get('phone'))->get();
//        $token = JWTAuth::fromUser($user);
//
//        return response()->json(compact('token'));
    }

    /**
     * Get logged user info.
     *
     * @return \Dingo\Api\Http\Response|mixed
     */
    public function getAuthenticatedUser()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        return $this->response->item($user, new UserTransformer());
    }

    /**
     * Send verify code.
     *
     * @param AuthRequest $request
     * @return mixed
     */
    public function sendVerifyCode(AuthRequest $request)
    {
        return SmsContent::sendSMS_newUser($request->get('phone'));
    }

    /**
     * Send reset pwd verify code.
     *
     * @param AuthRequest $request
     * @return mixed
     */
    public function sendResetPwdVerifyCode(AuthRequest $request)
    {
        $phone = $request->get('phone');
        $patient = User::where('phone', $phone)->first();
        if ($patient) {
            return SmsContent::sendSMS_newUser($phone);
        } else {
            return response()->json(['message' => '该手机号未注册'], 404);
        }
    }

    /**
     * Get inviter name.
     *
     * @param InviterRequest $request
     * @return mixed
     */
    public function getInviter(InviterRequest $request)
    {
        $data = User::getInviter($request->get('inviter'));

        if ($data) {
            return response()->json(['name' => $data]);
        } else {
            return response()->json(['message' => '无法识别邀请人']);
        }
    }
}
