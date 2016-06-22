<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Position extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'positions';

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
    protected $hidden = ['_internal_id', 'deleted_at', 'person_id', 'parent_id', 'team_id'];

    /**
     * date attributes
     *
     * @var array
     */
    protected $dates = ['start_date', 'end_date'];

    /**
     * returns the team the position belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function team() {
        return $this->belongsTo('App\Team');
    }

    /**
     * returns the person which is matched to the position
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function person() {
        return $this->belongsTo('App\Person');
    }

    /**
     * returns the direct parent the position belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent() {
        return $this->belongsTo('App\Position', 'parent_id');
    }

    /**
     * returns the tree of parent positions recursive
     * @return \Illuminate\Database\Query\Builder
     */
    public function parents() {
        return $this->parent()->with('parents');
    }

    /**
     * returns the direct child positions of the position
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function directChilds() {
        return $this->hasMany('App\Position', 'parent_id');
    }

    /**
     * returns the tree of child positions recursive
     * @return \Illuminate\Database\Query\Builder
     */
    public function childs() {
        return $this->directChilds()->with('childs');
    }

    /**
     * returns the entity the position belongs to
     * @return \Illuminate\Database\Query\Builder
     */
    public function entity() {
        return Entity::query()->select('entities.*')->from('teams')
            ->where('teams._internal_id', '=', $this->attributes['team_id'])->whereNull('teams.deleted_at')
            ->leftJoin('terms', 'terms._internal_id', '=', 'teams.term_id')->whereNull('terms.deleted_at')
            ->leftJoin('entities', 'entities._internal_id', '=', 'terms.entity_id');
    }

    /**
     * scope position query to a specific team type
     *
     * @param string $team_type eb|normal
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeTeamType($query, $team_type) {
        return $query->whereIn('positions.team_id', function($query) use ($team_type) {
            $query->select('_internal_id')->from('teams')->whereNull('teams.deleted_at')->where('teams.team_type', '=', $team_type);
        });
    }

    /**
     * scope query to non leader positions
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeNonLeader($query) {
        return $query->whereNotIn('positions._internal_id', function($query) {
            return $query->select('parent_id')->from('positions')->whereNull('deleted_at')->whereNotNull('parent_id');
        })->whereNotNull('positions.parent_id');
    }

    /**
     * scope query to leader positions only
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeLeader($query) {
        return $query->whereIn('positions._internal_id', function($query) {
            return $query->select('parent_id')->from('positions')->whereNull('deleted_at')->whereNotNull('parent_id');
        });
    }

    /**
     * scope query to non team leader positions (only sub team leaders when used with scopeLeader)
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeNonTeamLeader($query) {
        return $query->whereNotNull('positions.parent_id');
    }

    /**
     * scope query to only top positions in a team (should be renamed to scopeTeamLeader)
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeNonSubTeamLeader($query) {
        return $query->whereNull('positions.parent_id');
    }

    /**
     * scope query to only currently active positions
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeCurrent($query) {
        return $query->where('positions.start_date', '<=', DB::raw('NOW()'))->where('positions.end_date', '>=', DB::raw('NOW()'));
    }

    /**
     * scopes a position query to positions which are active in the specified timefram
     *
     * @param $query
     * @param Carbon $from
     * @param Carbon $to
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeTimeframe($query, $from, $to) {
        return $query->where('positions.start_date', '<=', $from)->where('positions.end_date', '>=', $to);
    }

    /**
     * scopes a position query to those positions which are matched
     *
     * @param $query
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeMatched($query) {
        return $query->whereNotNull('positions.person_id');
    }

    /**
     * scopes a position query to those positions which persons had task activities in the specified time frame
     *
     * @param $query
     * @param Carbon $from
     * @param Carbon $to
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeWithActivity($query, $from, $to) {
        return $query->whereIn('positions.person_id', function($query) use ($from, $to) {
            return $query->select('person_id')->from('tasks')->where('done_at', '>=', $from)->where('done_at', '<=', $to)->whereNull('deleted_at')->groupBy('person_id')->having(DB::raw('COUNT(*)'), '>', 0);
        });
    }

    /**
     * scopes a position query to those positions which persons had no task activities in the specified time frame
     *
     * @param $query
     * @param Carbon $from
     * @param Carbon $to
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeWithoutActivity($query, $from, $to) {
        return $query->whereNotIn('positions.person_id', function($query) use ($from, $to) {
            return $query->select('person_id')->from('tasks')->where('done_at', '>=', $from)->where('done_at', '<=', $to)->whereNull('deleted_at')->groupBy('person_id')->having(DB::raw('COUNT(*)'), '>', 0);
        });
    }

    /**
     * scopes a position query to those positions which persons had approved task activities in the specified time frame
     *
     * @param $query
     * @param Carbon $from
     * @param Carbon $to
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeWithApprovedActivity($query, $from, $to) {
        return $query->whereIn('positions.person_id', function($query) use ($from, $to) {
            return $query->select('person_id')->from('tasks')->where('done_at', '>=', $from)->where('done_at', '<=', $to)->where('approved_at', '>=', $from)->where('approved_at', '<=', $to)->whereNull('deleted_at')->groupBy('person_id')->having(DB::raw('COUNT(*)'), '>', 0);
        });
    }

    /**
     * scopes a position query to those positions which persons had no approved task activities in the specified time frame
     *
     * @param $query
     * @param Carbon $from
     * @param Carbon $to
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeWithoutApprovedActivity($query, $from, $to) {
        return $query->whereNotIn('positions.person_id', function($query) use ($from, $to) {
            return $query->select('person_id')->from('tasks')->where('done_at', '>=', $from)->where('done_at', '<=', $to)->where('approved_at', '>=', $from)->where('approved_at', '<=', $to)->whereNull('deleted_at')->groupBy('person_id')->having(DB::raw('COUNT(*)'), '>', 0);
        });
    }
}