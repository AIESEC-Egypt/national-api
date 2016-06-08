<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Person extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'persons';
    
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
    protected $hidden = ['_internal_id'];

    /**
     * Set the primary key to _internal_id, because this one is used for relationships
     *
     * @var string
     */
    protected $primaryKey = '_internal_id';

    /**
     * returns all tasks of the person
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tasks() {
        return $this->hasMany('App\Task');
    }

    /**
     * returns all tasks which were added by the person
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function added_tasks() {
        return $this->hasMany('App\Task', 'added_by');
    }

    /**
     * returns all managers of this person
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function managers() {
        return $this->belongsToMany('App\Person', 'persons_managers', 'person_id', 'manager_id');
    }

    /**
     * returns all persons which are managed by this person
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function managing() {
        return $this->belongsToMany('App\Person', 'persons_managers', 'manager_id', 'person_id');
    }

    /**
     * returns all programmes of this person
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function programmes() {
        return $this->belongsToMany('App\Programme', 'persons_programmes');
    }

    /**
     * returns all positions of the person
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function positions() {
        return $this->hasMany('App\Position');
    }

    /**
     * return all positions in an EB
     *
     * @return Relation
     */
    public function positionsEB() {
        return $this->positions()->leftJoin('teams', 'positions.team_id', '=', 'teams._internal_id')->where('team_type', '=', 'eb')->select('positions.*');
    }

    /**
     * return all positions as member. This excludes positions in an EB, or as any kind of leader
     *
     * @return Relation
     */
    public function positionsMember() {
        return $this->positions()->leftJoin('teams', 'positions.team_id', '=', 'teams._internal_id')->leftJoin('positions as subpos', 'subpos.parent_id', '=', 'positions._internal_id')->where('team_type', '=', 'normal')->whereNull('subpos._internal_id')->whereNotNull('positions.parent_id')->select('positions.*');
    }

    /**
     * return all positions as leader. This includes all positions, which have child positions
     *
     * @return Relation
     */
    public function positionsLeader() {
        return $this->positions()->leftJoin('positions as subpos', 'subpos.parent_id', '=', 'positions._internal_id')->whereNotNull('subpos._internal_id')->distinct()->select('positions.*');
    }

    /**
     * returns all positions as team leader, this excludes leading a sub team
     *
     * @return Relation
     */
    public function positionsTeamLeader() {
        return $this->positionsLeader()->whereNull('positions.parent_id');
    }

    /**
     * returns all positions as leader of a subteam
     *
     * @return Relation
     */
    public function positionsSubTeamLeader() {
        return $this->positionsLeader()->whereNotNull('positions.parent_id');
    }

    /**
     * returns all positions as leader, besides those in an EB
     *
     * @return Relation
     */
    public function positionsLeaderNonEB() {
        return $this->positionsLeader()->leftJoin('teams', 'positions.team_id', '=', 'teams._internal_id')->where('team_type', 'normal');
    }

    /**
     * returns all positions as team leader without those in an EB. This also excludes leading a sub team
     *
     * @return Relation
     */
    public function positionsTeamLeaderNonEB() {
        return $this->positionsLeaderNonEB()->whereNull('positions.parent_id');
    }

    /**
     * returns all positions as leader of a subteam, besides those in an EB
     *
     * @return Relation
     */
    public function positionsSubTeamLeaderNonEb() {
        return $this->positionsLeaderNonEB()->whereNotNull('positions.parent_id');
    }

    /**
     * returns all teams the person has a position in
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function teams() {
        return $this->hasManyThrough('App\Team', 'App\Position');
    }
}
