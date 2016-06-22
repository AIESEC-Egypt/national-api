<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
    protected $fillable = ['value', 'calculated_at', 'date_id', 'kpi_id'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['kpi_id', 'date_id'];

    /**
     * define always loaded relationships
     *
     * @var array
     */
    protected $with = ['date'];

    /**
     * date attributes
     *
     * @var array
     */
    protected $dates = ['calculated_at', 'valid_from'];

    /**
     * returns the KPI this value belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function kpi() {
        return $this->belongsTo('App\KPI', 'kpi_id');
    }

    /**
     * returns the date dimension of this value
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function date() {
        return $this->belongsTo('App\KPIvalueDate', 'date_id');
    }

    /**
     * scopes the query to select the newest values first
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeNewestFirst($query) {
        return $query->leftJoin('kpi_values_date', 'kpi_values.date_id', '=', 'kpi_values_date.id')->orderBy('kpi_values_date.date', 'desc');
    }

    /**
     * scopes a KPI value query to return only the latest value
     *
     * @param $query
     * @return mixed
     */
    public function scopeLatest($query) {
        return $query
            ->leftJoin('kpi_values_date', 'kpi_values.date_id', '=', 'kpi_values_date.id')
            ->leftJoin(DB::raw('(SELECT `kpi_id`, MAX(`kpi_values_date`.`date`) as \'date\' FROM `kpi_values` LEFT JOIN `kpi_values_date` ON `kpi_values_date`.`id`=`kpi_values`.`date_id` GROUP BY `kpi_id`) as newest'), 'kpi_values.kpi_id', '=', 'newest.kpi_id')
            ->whereColumn('kpi_values_date.date', 'newest.date')
            ->select('kpi_values.*');
    }
}