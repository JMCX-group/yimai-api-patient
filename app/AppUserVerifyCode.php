<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class AppUserVerifyCode extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'patient_verify_codes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['phone', 'code'];
}
