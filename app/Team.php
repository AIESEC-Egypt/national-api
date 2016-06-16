<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'teams';

    /**
     * the primary key
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
    protected $hidden = ['deleted_at', '_internal_id', 'term_id', 'department_id', 'function_id'];

    /**
     * returns the department the team belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function department() {
        return $this->belongsTo('App\Department');
    }

    /**
     * returns the term the team belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function term() {
        return $this->belongsTo('App\Term');
    }

    /**
     * returns the entity of the team
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entity() {
        return Entity::query()->leftJoin('terms', 'terms.entity_id', '=', 'entities._internal_id')->whereNull('terms.deleted_at')->where('terms._internal_id', '=', $this->attributes['term_id']);
    }

    /**
     * returns the function the team belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function _function() {
        return $this->belongsTo('App\_Function', 'function_id');
    }

    /**
     * returns all positions of the team as a flat collection
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allPositions() {
        return $this->hasMany('App\Position');
    }

    /**
     * returns the top level positions of the team
     * @return \Illuminate\Database\Query\Builder
     */
    public function topPositions() {
        return $this->hasMany('App\Position')->whereNull('positions.parent_id');
    }

    /**
     * returns the position tree of the team
     * @return \Illuminate\Database\Query\Builder
     */
    public function positions() {
        return $this->topPositions()->with('childs');
    }

    /**
     * returns all KPIs of the team
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function KPIs() {
        return $this->morphMany('App\KPI', 'measurable');
    }

    /**
     * scopes the query to return only teams of current terms
     *
     * @param $query
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeCurrent($query) {
        return $query->leftJoin('terms', 'terms._internal_id', '=', 'teams.term_id')->where('terms.start_date', '<=', Carbon::now())->where('terms.end_date', '>=', Carbon::now());
    }

    /**
     * scopes a team query to a specific team type
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $team_type eb|normal
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeType($query, $team_type) {
        return $query->where('teams.team_type', '=', $team_type);
    }

    /**
     * Determines if $person is a member of the team
     *
     * @param Person|integer $person
     * @return bool
     */
    public function isMember($person) {
        // make $person a scalar value
        if($person instanceof Person) {
            $person = $person->_internal_id;
        }

        // count current positions of this person in the team
        $count = $this->allPositions()->current()->where('positions.person_id', '=', $person)->count();

        // parse result to bool
        return ($count > 0);
    }
    
    public function inTopPosition($person) {
        // make $person a scalar value
        if($person instanceof Person) {
            $person = $person->_internal_id;
        }

        // count current positions of this person in the team
        $count = $this->topPositions()->current()->where('positions.person_id', '=', $person)->count();

        // parse result to bool
        return ($count > 0);
    }
}