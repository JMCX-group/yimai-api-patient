<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PatientAddressBook extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'patient_address_book';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'patient_id',
        'view_list',
        'view_phone_arr',
        'del_list',
        'del_phone_arr',
        'invited_list',
        'invited_phone_arr',
        'doctor_list',
        'doctor_phone_arr',
        'upload_time'
    ];
}
