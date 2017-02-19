<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 2017/2/16
 * Time: 12:14
 */

namespace App\Api\Controllers;

use App\Api\Helper\SmsContent;
use App\Api\Requests\AddressRequest;
use App\Api\Requests\ZoneDelRequest;
use App\Api\Transformers\AddressBookTransformer;
use App\Doctor;
use App\InvitedDoctor;
use App\Patient;
use App\PatientAddressBook;
use App\User;
use Illuminate\Http\Request;

class AddressBookController extends BaseController
{
    /**
     * Get all.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function index()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $addressBook = PatientAddressBook::where('patient_id', $user->id)->first();
        if (!isset($addressBook->id)) {
            $addressBook = new PatientAddressBook();
        } else {
            /**
             * 计算是否可以重新邀请和刷新其他的状态：
             */
            $invitedDoctor = InvitedDoctor::where('patient_id', $user->id)->get();
            $doctorListArr = json_decode($addressBook->doctor_list, true);
            $newDoctorListArr = array();
            foreach ($doctorListArr as $item) {
                /**
                 * 刷新已经加入之后的状态：
                 */
                foreach ($invitedDoctor as $value) {
                    if ($item['phone'] == $value->doctor_phone) {
                        $tmp = [
                            'name' => $item['name'],
                            'phone' => $item['phone'],
                            'status' => $value->status, //wait：等待邀请；invited：已邀请/未加入；re-invite：可以重新邀请了；join：已加入；processing：认证中；completed：完成认证
                            'time' => ''
                        ];
                        break;
                    }
                }

                if (!isset($tmp)) {
                    /**
                     * 当前时间如果小于一个月前，则可以重新邀请：
                     */
                    if ($item['time'] != '' && (strtotime($item['time']) < date('Y-m-d H:i:s', time() - 30 * 24 * 3600))) {
                        $tmp = [
                            'name' => $item['name'],
                            'phone' => $item['phone'],
                            'status' => 're-invite', //wait：等待邀请；invited：已邀请/未加入；re-invite：可以重新邀请了；join：已加入；processing：认证中；completed：完成认证
                            'time' => $item['time']
                        ];
                        array_push($newDoctorListArr, $tmp);
                    } else {
                        $tmp = $item;
                    }
                }

                array_push($newDoctorListArr, $tmp);
            }

            $addressBook->doctor_list = json_encode($newDoctorListArr);
        }

        $addressBook->save();

        $data = AddressBookTransformer::transform($addressBook);

        return response()->json(compact('data'), 200);
    }

    /**
     * 上传通讯录和更新通讯录
     *
     * @param AddressRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function update(AddressRequest $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $viewList = json_decode($request['view_list'], true);
        if (!$viewList) {
            return response()->json(['message' => '格式错误或数据为空'], 400);
        }

        $addressBook = PatientAddressBook::where('patient_id', $user->id)->first();
        if (!isset($addressBook->id)) {
            $addressBook = new PatientAddressBook();
            $addressBook->patient_id = $user->id;
        }

        $lists = $this->analysisInvitedList($user, $viewList, $addressBook);

        $addressBook->view_list = json_encode($lists['view_list']);
        $addressBook->view_phone_arr = json_encode($lists['view_phone_arr']);
        $addressBook->invited_list = json_encode($lists['invited_list']);
        $addressBook->invited_phone_arr = json_encode($lists['invited_phone_arr']);
        $addressBook->upload_time = (isset($request['upload_time']) && $request['upload_time'] != '') ? date('Y-m-d H:i:s', strtotime($request['upload_time'])) : $addressBook->upload_time;
        $addressBook->save();

        $data = AddressBookTransformer::transform($addressBook);

        return response()->json(compact('data'), 200);
    }

    /**
     * 不显示的联系人
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function delContacts(Request $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        if (!isset($request['phone_list']) || $request['phone_list'] == '') {
            return response()->json(['message' => '手机列表未传值或为空'], 400);
        }

        $delPhones = $request['phone_list'];
        if (substr($delPhones, strlen($delPhones) - 1) == ',') {
            $delPhones = substr($delPhones, 0, strlen($delPhones) - 1);
        }
        $delPhoneArr = explode(',', $delPhones);

        $addressBook = PatientAddressBook::where('patient_id', $user->id)->first();

        /**
         * 生成新的
         */
        $viewListArr = json_decode($addressBook->view_list, true);
        $delListArr = json_decode($addressBook->del_list, true);
        if (!$delListArr) {
            $delListArr = array();
        }
        $newViewListArr = array();
        foreach ($viewListArr as $item) {
            if (in_array($item['phone'], $delPhoneArr)) {
                array_push($delListArr, $item);
            } else {
                array_push($newViewListArr, $item);
            }
        }

        $addressBook->view_list = json_encode($newViewListArr);
        $addressBook->del_list = json_encode($delListArr);
        $addressBook->save();

        $data = AddressBookTransformer::transform($addressBook);

        return response()->json(compact('data'), 200);
    }

    /**
     * 添加到医生列表里
     *
     * @param ZoneDelRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addDoctor(ZoneDelRequest $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $addPhone = $request['phone'];
        $addressBook = PatientAddressBook::where('patient_id', $user->id)->first();

        $viewListArr = json_decode($addressBook->view_list, true);
        $doctorListArr = json_decode($addressBook->doctor_list, true);
        $newViewListArr = array();
        foreach ($viewListArr as $item) {
            if ($item['phone'] == $addPhone) {
                $tmp = [
                    'name' => $item['name'],
                    'phone' => $item['phone'],
                    'status' => 'wait', //wait：等待邀请；invited：已邀请/未加入；re-invite：可以重新邀请了；join：已加入；processing：认证中；completed：完成认证
                    'time' => ''
                ];
                array_push($doctorListArr, $tmp);
            } else {
                array_push($newViewListArr, $item);
            }
        }

        $addressBook->view_list = json_encode($newViewListArr);
        $addressBook->doctor_list = json_encode($doctorListArr);
        $addressBook->save();

        $data = AddressBookTransformer::transform($addressBook);

        return response()->json(compact('data'), 200);
    }

    /**
     * 取消在医生列表里显示的医生
     *
     * @param ZoneDelRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function delDoctor(ZoneDelRequest $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        $delPhone = $request['phone'];
        $addressBook = PatientAddressBook::where('patient_id', $user->id)->first();

        $viewListArr = json_decode($addressBook->view_list, true);
        if(!$viewListArr){
            $viewListArr = array();
        }

        $doctorListArr = json_decode($addressBook->doctor_list, true);
        $newDoctorListArr = array();
        foreach ($doctorListArr as $item) {
            if ($item['phone'] == $delPhone) {
                $tmp = [
                    'name' => $item['name'],
                    'phone' => $item['phone'],
                ];
                array_push($viewListArr, $tmp);
            } else {
                array_push($newDoctorListArr, $item);
            }
        }

        $addressBook->view_list = json_encode($viewListArr);
        $addressBook->doctor_list = json_encode($newDoctorListArr);
        $addressBook->save();

        $data = AddressBookTransformer::transform($addressBook);

        return response()->json(compact('data'), 200);
    }

    /**
     * 分析生成invited list和新的view list。
     *
     * @param $user
     * @param $viewListArr
     * @param $addressBook
     * @return array
     */
    public function analysisInvitedList($user, $viewListArr, $addressBook)
    {
        $invitedListArr = json_decode($addressBook->invited_list, true);
        $invitedListArr = (empty($invitedListArr)) ? array() : $invitedListArr;
        $invitedPhoneArr = json_decode($addressBook->invited_phone_arr, true);
        $invitedPhoneArr = (empty($invitedPhoneArr)) ? array() : $invitedPhoneArr;
        $oldViewListArr = json_decode($addressBook->view_list, true);
        $oldViewListArr = (empty($oldViewListArr)) ? array() : $oldViewListArr;
        $oldViewPhoneArr = json_decode($addressBook->view_phone_arr, true);
        $oldViewPhoneArr = (empty($oldViewPhoneArr)) ? array() : $oldViewPhoneArr;

        /**
         * 获取邀请code：
         */
        $code = Patient::getHealthConsultantCode($user->city_id, $user->code);

        /**
         * 剔除已有invited list中的phone，如果被邀请或在原有列表里，则进行剔除
         */
        $newPhoneArr = array();
        foreach ($viewListArr as $item) {
            if ((!in_array($item['phone'], $invitedPhoneArr)) && (!in_array($item['phone'], $oldViewPhoneArr))) {
                array_push($newPhoneArr, $item['phone']);
            }
        }

        /**
         * 通过phone批量找出已注册，有code，且不是自己code的phone list
         */
        $newInvitedList = Doctor::where('inviter_dp_code', $code)
            ->whereIn('phone', $newPhoneArr)
            ->get();

        /**
         * 如果返回不为空，则遍历view list分成两组返回：
         */
        if (!empty($newInvitedList)) {
            /**
             * 将新上传已注册的加入invited组
             */
            foreach ($newInvitedList as $item) {
                $tmp = [
                    'name' => $item->name,
                    'phone' => $item->phone,
                    'time' => $item->created_at->format('Y/m/d')
                ];
                array_push($invitedListArr, $tmp);
                array_push($invitedPhoneArr, $item['phone']);
            }

            /**
             * 将新上传未注册的加入view组
             */
            foreach ($viewListArr as $item) {
                if (!in_array($item['phone'], $invitedPhoneArr)) {
                    $tmp = [
                        'name' => $item['name'],
                        'phone' => $item['phone']
                    ];
                    array_push($oldViewListArr, $tmp);
                    array_push($oldViewPhoneArr, $item['phone']);
                }
            }

        }

        $data = [
            'view_list' => $oldViewListArr,
            'view_phone_arr' => $oldViewPhoneArr,
            'invited_list' => $invitedListArr,
            'invited_phone_arr' => $invitedPhoneArr
        ];

        return $data;
    }

    /**
     * 邀请短信
     *
     * @param ZoneDelRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function invite(ZoneDelRequest $request)
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        /**
         * 数据获取
         */
        $phone = $request['phone'];
        $name = '';
        if (isset($request['txt']) && $request['txt'] != '') {
            $txt = $request['txt'];
            if(strpos($txt, '【医者脉连】') === false){
                $txt = '【医者脉连】' . $txt;
            }
        } else {
            $txt = '';
        }

        /**
         * 判断是否已登录
         */
        $doctor = Doctor::where('phone', $phone)->first();
        if ($doctor) {
            return response()->json(['message' => '该手机号已注册'], 400);
        }

        /**
         * 已有数据处理
         */
        $addressBook = PatientAddressBook::where('patient_id', $user->id)->first();
        $doctorListArr = json_decode($addressBook->doctor_list, true);
        $newDoctorListArr = array();
        $isAddToArr = false;
        if($doctorListArr) {
            foreach ($doctorListArr as $item) {
                if ($item['phone'] == $phone) {
                    $tmp = [
                        'name' => $item['name'],
                        'phone' => $item['phone'],
                        'status' => 'invited', //wait：等待邀请；invited：已邀请/未加入；re-invite：可以重新邀请了；join：已加入；processing：认证中；completed：完成认证
                        'time' => date('Y-m-d H:i:s')
                    ];
                    $name = $item['name'];
                    array_push($newDoctorListArr, $tmp);
                    $isAddToArr = true;
                } else {
                    array_push($newDoctorListArr, $item);
                }
            }
        }

        /**
         * 如果没有加入已知就单独生成一条：
         */
        if (!$isAddToArr && isset($request['name']) && $request['name'] != '') {
            $name = $request['name'];
            $tmp = [
                'name' => $name,
                'phone' => $phone,
                'status' => 'invited', //wait：等待邀请；invited：已邀请/未加入；re-invite：可以重新邀请了；join：已加入；processing：认证中；completed：完成认证
                'time' => date('Y-m-d H:i:s')
            ];
            array_push($newDoctorListArr, $tmp);
        }

        /**
         * 发送短信和保存数据
         */
        $ret = SmsContent::sendSMS_zoneInvite($phone, $name, $txt);
        if ($ret) {
            $addressBook->doctor_list = json_encode($newDoctorListArr);
            $addressBook->save();

            $data = AddressBookTransformer::transform($addressBook);

            return response()->json(compact('data'), 200);
        } else {
            return response()->json(['message' => '短信发送失败'], 500);
        }
    }
}
