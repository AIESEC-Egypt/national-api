<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class KPI extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'kpis';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['type', 'subtype', 'unit'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['deleted_at', 'measurable_id'];

    /**
     * date attributes
     *
     * @var array
     */
    protected $dates = [];

    /**
     * returns the object this KPI is assigned to
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function measurable() {
        return $this->morphTo();
    }

    /**
     * returns the values of this kpi
     * @return \Illuminate\Database\Query\Builder
     */
    public function values() {
        return $this->hasMany('App\KPIvalue', 'kpi_id')->with('date');
    }
    
    /**
     * returns the relation to the latest value (for performance reasons use this only on collections)
     */
    public function latestValue() {
        return $this->hasMany('App\KPIvalue', 'kpi_id')->latest();
    }

    /**
     * return the query to select the newest
     */
    public function singleLatestValue() {

    }
}