<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class TmpSort extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tmp_sort';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'phone',
        'status',
        'time'
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return array
     */
    public static function orderByGBK()
    {
        return DB::select(
            "SELECT name,phone " .
            "FROM tmp_sort " .
            "ORDER BY CONVERT(name USING gbk) COLLATE gbk_chinese_ci;"
        );
    }
}
