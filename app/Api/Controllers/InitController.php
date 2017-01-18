<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/21
 * Time: 上午9:47
 */

namespace App\Api\Controllers;

use App\AppointmentMsg;
use App\Api\Transformers\Transformer;
use App\Api\Transformers\UserTransformer;
use App\Appointment;
use App\Doctor;
use App\DoctorContactRecord;
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
         * Get my doctors.
         */
        $doctorIdList = Appointment::getMyDoctors($user->id);
        $doctors = Doctor::whereIn('id', $doctorIdList)->get();
        $myDoctors = array();
        foreach ($doctors as $doctor) {
            array_push($myDoctors, Transformer::searchDoctorTransform($doctor, $user->id));
        }

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

        /**
         * Get all system notification.
         */
        $radioStationUnreadCount = RadioStation::getUnreadRadioCount($user);

        return [
            'user' => $retUser,
            'my_doctors' => $myDoctors,
            'sys_info' => [
                'radio_unread_count' => $radioStationUnreadCount,
//                'appointment_unread_count' => $appointmentUnreadCount
            ]
        ];
    }
}
