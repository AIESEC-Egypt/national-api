<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class KPIvalueDate extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'kpi_values_date';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['date'];

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
    protected $dates = ['date'];

    /**
     * returns the KPI this value belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function values() {
        return $this->hasMany('App\KPIvalue', 'date_id');
    }

    /**
     * mutator for date attribute
     * @param $value
     */
    public function setDateAttribute($value) {
        if(!($value instanceof Carbon)) {
            $value = Carbon::parse($value);
        }
        $this->attributes['date'] = $value;
        $this->attributes['day'] = $value->year . '-' . $value->month . '-' . $value->day;
        $this->attributes['week'] = $value->year . '-' . $value->weekOfYear;
        $this->attributes['month'] = $value->year . '-' . $value->month;
        $this->attributes['quarter'] = $value->year . '-' . $value->quarter;
        $this->attributes['year'] = $value->year;
        $this->attributes['dayOfMonth'] = $value->day;
        $this->attributes['dayOfWeek'] = $value->dayOfWeek;
        $this->attributes['weekOfMonth'] = $value->weekOfMonth;
        $this->attributes['weekOfYear'] = $value->weekOfYear;
        $this->attributes['monthOfYear'] = $value->month;
        $this->attributes['quarterOfYear'] = $value->quarter;
    }
}