<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/18
 * Time: 下午4:08
 */

namespace App\Api\Transformers;

use App\Api\Helper\AppointmentStatus;

class AppointmentMsgTransformer
{
    /**
     * @param $appointmentMsg
     * @return array
     */
    public static function transformerMsgList($appointmentMsg)
    {
        return [
            'id' => $appointmentMsg['id'],
            'appointment_id' => $appointmentMsg['appointment_id'],
            'text' => AppointmentStatus::appointmentMsgContent_patient($appointmentMsg['status'], $appointmentMsg['doctor_name'], $appointmentMsg['locums_name'], $appointmentMsg['appointment_id']),
            'type' => $appointmentMsg['type'],
            'read' => $appointmentMsg['patient_read'],
            'time' => $appointmentMsg['created_at']->format('Y-m-d H:i:s')
        ];
    }
}
