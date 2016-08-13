<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Illness extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'illnesss';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'general_name',
        'dept1_id',
        'dept2_id',
        'symptom_1',
        'symptom_2',
        'symptom_3',
        'symptom_4',
        'symptom_5',
        'pathogen',
        'clinical_manifestations',
        'diagnosis',
        'treatment',
        'prognosis',
        'prevention'
    ];
}
