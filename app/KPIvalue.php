<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class KPIvalue extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'kpi_values';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['value', 'calculated_at', 'from', 'to'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * date attributes
     *
     * @var array
     */
    protected $dates = ['calculated_at', 'from', 'to'];

    public function kpi() {
        return $this->belongsTo('App\KPI', 'kpi_id');
    }
}