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
        if(is_array($query->getQuery()->joins)) {
            foreach ($query->getQuery()->joins as $join) {
                if($join->table == 'teams') {
                    return $query->where('teams.team_type', '=', $team_type);
                }
            }
        }
        return $query->leftJoin('teams', 'positions.team_id', '=', 'teams._internal_id')->whereNull('teams.deleted_at')->where('teams.team_type', '=', $team_type)->select('positions.*');
    }

    /**
     * scope query to non leader positions
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeNonLeader($query) {
        return $query->leftJoin('positions as subpos', 'subpos.parent_id', '=', 'positions._internal_id')->whereNull('subpos._internal_id')->whereNull('subpos.deleted_at')->whereNotNull('positions.parent_id')->select('positions.*');
    }

    /**
     * scope query to leader positions only
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeLeader($query) {
        return $query->leftJoin('positions as subpos', 'subpos.parent_id', '=', 'positions._internal_id')->whereNull('subpos.deleted_at')->whereNotNull('subpos._internal_id')->distinct()->select('positions.*');
    }

    /**
     * scope query to non team leader positions, so only sub team leaders (works only with Leader scope)
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeNonTeamLeader($query) {
        return $query->whereNotNull('positions.parent_id');
    }

    /**
     * scope query to non sub team leader positions, so only team leaders (works only with Leader scope)
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
        $query = $query->where('positions.start_date', '<=', Carbon::now())->where('positions.end_date', '>=', Carbon::now());
        if(is_array($query->getQuery()->joins)) {
            foreach ($query->getQuery()->joins as $join) {
                switch ($join->table) {
                    case 'positions as childs':
                        $query = $query->where('childs.start_date', '<=', Carbon::now())->where('childs.end_date', '>=', Carbon::now());
                        break;

                    case 'positions as parents':
                        $query = $query->where('parents.start_date', '<=', Carbon::now())->where('parents.end_date', '>=', Carbon::now());
                        break;
                }
            }
        }
        return $query;
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
        return $query->leftJoin(DB::raw('(select `person_id`, COUNT(*) as count from `tasks` where `done_at` >= ? and `done_at` <= ? and `tasks`.`deleted_at` is null group by `person_id`) tasks_count'), 'positions.person_id', '=', 'tasks_count.person_id')
            ->addBinding($from, 'join')
            ->addBinding($to, 'join')
            ->whereNotNull('positions.person_id')
            ->where('tasks_count.count', '>', 0);
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
        return $query->leftJoin(DB::raw('(select `person_id`, COUNT(*) as count from `tasks` where `done_at` >= ? and `done_at` <= ? and `tasks`.`deleted_at` is null group by `person_id`) tasks_count'), 'positions.person_id', '=', 'tasks_count.person_id')
            ->addBinding($from, 'join')
            ->addBinding($to, 'join')
            ->whereNotNull('positions.person_id')
            ->where('tasks_count.count', '=', 0);
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
        return $query->leftJoin(DB::raw('(select `person_id`, COUNT(*) as count from `tasks` where `done_at` >= ? and `done_at` <= ? and `approved_at` >= ? and `approved_at` <= ? and `tasks`.`deleted_at` is null group by `person_id`) tasks_approved_count'), 'positions.person_id', '=', 'tasks_approved_count.person_id')
            ->addBinding($from, 'join')
            ->addBinding($to, 'join')
            ->addBinding($from, 'join')
            ->addBinding($to, 'join')
            ->whereNotNull('positions.person_id')
            ->where('tasks_approved_count.count', '>', 0);
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
        return $query->leftJoin(DB::raw('(select `person_id`, COUNT(*) as count from `tasks` where `done_at` >= ? and `done_at` <= ? and `approved_at` >= ? and `approved_at` <= ? and `tasks`.`deleted_at` is null group by `person_id`) tasks_approved_count'), 'positions.person_id', '=', 'tasks_approved_count.person_id')
            ->addBinding($from, 'join')
            ->addBinding($to, 'join')
            ->addBinding($from, 'join')
            ->addBinding($to, 'join')
            ->whereNotNull('positions.person_id')
            ->where('tasks_approved_count.count', '=', 0);
    }
}