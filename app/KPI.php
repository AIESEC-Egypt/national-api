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
     * returns the relation to the latest value
     */
    public function latestValue() {
        return $this->hasOne('App\KPIvalue', 'kpi_id')->latest();
    }
    
    public function scopeMeasurable($query, $measurable_type, $measurable_id) {
        if(substr($measurable_id, 0, 1) == '_') {
            return $query->where('measurable_type', '=', $measurable_type)->where('measurable_id', '=', substr($measurable_id, 1));
        } else {
            switch($measurable_type) {
                case 'Entity':
                    $whereTable = 'entities';
                    break;

                case 'Person':
                    $whereTable = 'persons';
                    break;

                case 'Team':
                    $whereTable = 'teams';
                    break;
            }
            return $query->where('measurable_type', '=', $measurable_type)->whereIn('measurable_id', function($query) use ($whereTable, $measurable_id) {
                return $query->select('_internal_id')->from($whereTable)->where('id', '=', $measurable_id);
            });
        }
    }
}