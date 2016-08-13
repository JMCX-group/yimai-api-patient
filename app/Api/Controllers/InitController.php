<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/21
 * Time: 上午9:47
 */

namespace App\Api\Controllers;

use App\AdmissionsMsg;
use App\Api\Transformers\Transformer;
use App\Api\Transformers\UserTransformer;
use App\AppointmentMsg;
use App\DoctorContactRecord;
use App\DoctorRelation;
use App\RadioStation;
use App\User;

class InitController extends BaseController
{
    /**
     * Get init info.
     * 
     * @return array|mixed
     */
    public function index()
    {
        $user = User::getAuthenticatedUser();
        if (!isset($user->id)) {
            return $user;
        }

        /**
         * Get user info.
         */
        $retUser = UserTransformer::transformUser($user);

        /**
         * Get recent contacts.
         */
        $contactRecords = DoctorContactRecord::where('doctor_id', $user->id)->lists('contacts_id_list');
        $contactRecordsIdList = (count($contactRecords) != 0) ? explode(',', $contactRecords[0]) : $contactRecords;
        $contacts = User::whereIn('id', $contactRecordsIdList)->get();
        $retContact = array();
        foreach ($contacts as $contact) {
            array_push($retContact, Transformer::contactsTransform($contact));
        }

        return [
            'user' => $retUser,
            'sys_info' => [
//                'radio_unread_count' => $radioStationUnreadCount,
//                'admissions_unread_count' => $admissionsUnreadCount,
//                'appointment_unread_count' => $appointmentUnreadCount
            ]
        ];
    }
}
