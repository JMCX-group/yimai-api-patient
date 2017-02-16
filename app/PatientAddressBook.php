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
        'del_list',
        'invited_list',
        'doctor_list',
        'upload_time'
    ];
}
