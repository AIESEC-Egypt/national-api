<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Term extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'terms';

    /**
     * set primary key
     *
     * @var string
     */
    protected $primaryKey = '_internal_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['deleted_at', '_internal_id'];

    /**
     * date attributes
     *
     * @var array
     */
    protected $dates = ['start_date', 'end_date'];

    /**
     * returns the entity the term belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entity() {
        return $this->belongsTo('App\Entity');
    }

    /**
     * returns all the teams of the term
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function teams() {
        return $this->hasMany('App\Team');
    }

    /**
     * returns all positions in the teams of the term as flat Collection
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function allPositions() {
        return $this->hasManyThrough('App\Position', 'App\Team');
    }

    /**
     * return all persons which have positions in this term as flat Collection
     *
     * @param null|Carbon $from
     * @param null|Carbon $to
     * @return \Illuminate\Database\Query\Builder
     */
    public function persons($from = null, $to = null) {
        $query = Person::query()->select('persons.*')->distinct()->from('teams')
            ->where('teams.term_id', '=', $this->original['_internal_id'])->whereNull('teams.deleted_at')
            ->leftJoin('positions', 'positions.team_id', '=', 'teams._internal_id')
            ->whereNull('positions.deleted_at')
            ->leftJoin('persons', 'persons._internal_id', '=', 'positions.person_id');

        if(!is_null($from)) {
            $query = $query->where('positions.start_date', '<=', $from);
        }
        
        if(!is_null($to)) {
            $query = $query->where('positions.end_date', '<=', $to);
        }
        return $query;
    }

    /**
     * scopes the query to return only current terms
     *
     * @param $query
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeCurrent($query) {
        return $query->where('terms.start_date', '<=', DB::raw('NOW()'))->where('terms.end_date', '>=', DB::raw('NOW()'));
    }
}