<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PatientRechargeRecord extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'patient_recharge_records';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'patient_id',
        'out_trade_no',
        'total_fee',
        'body',
        'detail',
        'time_start',
        'time_expire',
        'ret_data',
        'source',
        'status',
        'settlement_status'
    ];
}
