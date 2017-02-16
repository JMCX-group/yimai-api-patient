<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PatientBank extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'patient_banks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'patient_id',
        'bank_name',
        'bank_info',
        'bank_no',
        'status',
        'desc'
    ];
}
