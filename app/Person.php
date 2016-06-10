<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
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
     */
    public function tasks() {
        return $this->hasMany('App\Task');
    }

    /**
     * returns all tasks which were added by the person
     */
    public function added_tasks() {
        return $this->hasMany('App\Task', 'added_by');
    }

    /**
     * returns all managers of this person (this is related to EP managers and not positions in a team)
     */
    public function managers() {
        return $this->belongsToMany('App\Person', 'persons_managers', 'person_id', 'manager_id');
    }

    /**
     * returns all persons which are managed by this person (this is related to EP managers and not positions in a team)
     */
    public function managing() {
        return $this->belongsToMany('App\Person', 'persons_managers', 'manager_id', 'person_id');
    }

    /**
     * returns all programmes of this person
     */
    public function programmes() {
        return $this->belongsToMany('App\Programme', 'persons_programmes');
    }

    /**
     * returns all positions of the person
     * @param bool $current true if only currently active positions should be considered
     * @return mixed
     */
    public function positions($current) {
        if($current) {
            return $this->hasMany('App\Position')->where('positions.start_date', '<=', Carbon::now())->where('positions.end_date', '>=', Carbon::now());
        } else {
            return $this->hasMany('App\Position');
        }
    }

    /**
     * return all positions in an EB
     * @param bool $current true if only currently active positions should be considered
     * @return mixed
     */
    public function positionsEB($current = false) {
        return $this->positions($current)->leftJoin('teams', 'positions.team_id', '=', 'teams._internal_id')->where('team_type', '=', 'eb')->whereNull('teams.deleted_at')->select('positions.*');
    }

    /**
     * return all positions as member. This excludes positions in an EB, or as any kind of leader
     * @param bool|true $current true if only currently active positions should be considered
     * @return mixed
     */
    public function positionsMember($current = false) {
        return $this->positions($current)->leftJoin('teams', 'positions.team_id', '=', 'teams._internal_id')->where('team_type', '=', 'normal')->whereNull('teams.deleted_at')->leftJoin('positions as subpos', 'subpos.parent_id', '=', 'positions._internal_id')->whereNull('subpos._internal_id')->whereNull('subpos.deleted_at')->whereNotNull('positions.parent_id')->select('positions.*');
    }

    /**
     * return all positions as leader. This includes all positions, which have child positions
     * @param bool $current set true of only currently active positions shold be considered
     * @param string $team_type standard '*', set to 'eb'|'normal' to consider only positions in those team types
     * @param null|bool $tl_only standard null to return team leader and subteam leader positions. Set to true to return only teamleader positions and set to false to return only subteam leader positions.
     * @return mixed
     */
    public function positionsLeader($current = false, $team_type = '*', $tl_only = null) {
        $r = $this->positions($current)->leftJoin('positions as subpos', 'subpos.parent_id', '=', 'positions._internal_id')->whereNull('subpos.deleted_at')->whereNotNull('subpos._internal_id')->distinct()->select('positions.*');
        if($team_type != '*') {
            $r = $r->leftJoin('teams', 'positions.team_id', '=', 'teams._internal_id')->whereNull('teams.deleted_at')->where('team_type', $team_type);
        }
        if($tl_only !== null) {
            if($tl_only === true) {
                $r = $r->whereNull('positions.parent_id');
            } else {
                $r = $r->whereNotNull('positions.parent_id');
            }
        }
        return $r;
    }

    /**
     * returns the positions of all people who are a team leader of one of the persons positions
     * @param bool $current set to true to only recognise currently active positions
     * @return mixed
     */
    public function leadersAsPositions($current = false) {
        $r = $this->hasMany('App\Position')->leftJoin('positions as parents', 'positions.parent_id', '=', 'parents._internal_id')->whereNull('parents.deleted_at')->whereNotNull('parents._internal_id')->select('parents.*');
        if($current) {
            return $r->where('positions.start_date', '<=', Carbon::now())->where('positions.end_date', '>=', Carbon::now())->where('parents.start_date', '<=', Carbon::now())->where('parents.end_date', '>=', Carbon::now());
        } else {
            return $r;
        }
    }

    /**
     * returns the persons which are a leader for this person in any position
     * @param bool $current set to true to only recognise currently active positions
     * @return mixed
     */
    public function leadersAsPersons($current = false) {
        $r = $this->belongsTo('App\Person')->whereNotNull('persons._internal_id')->orWhere('persons._internal_id', '=', $this->_internal_id)->leftJoin('positions', 'persons._internal_id', '=', 'positions.person_id')->whereNull('positions.deleted_at')->leftJoin('positions as parents', 'parents._internal_id', '=', 'positions.parent_id')->whereNull('parents.deleted_at')->leftJoin('persons as leaders', 'leaders._internal_id', '=', 'parents.person_id')->whereNotNull('leaders._internal_id')->distinct()->select('leaders.*');
        if($current) {
            return $r->where('positions.start_date', '<=', Carbon::now())->where('positions.end_date', '>=', Carbon::now())->where('parents.start_date', '<=', Carbon::now())->where('parents.end_date', '>=', Carbon::now());
        } else {
            return $r;
        }
    }

    /**
     * returns all positions of members of this person
     * @param bool $current set to true to only recognise currently active positions
     * @return mixed
     */
    public function membersAsPositions($current = false) {
        $r = $this->positions($current)->leftJoin('positions as members', 'members.parent_id', '=', 'positions._internal_id')->whereNull('members.deleted_at')->whereNotNull('members._internal_id');
        if($current) {
            return $r->where('members.start_date', '<=', Carbon::now())->where('members.end_date', '>=', Carbon::now());
        } else {
            return $r;
        }
    }

    /**
     * returns the persons which are a leader for this person in any position
     * @param bool $current set to true to only recognise currently active positions
     * @return mixed
     */
    public function membersAsPersons($current = false) {
        $r = $this->belongsTo('App\Person')->whereNotNull('persons._internal_id')->orWhere('persons._internal_id', '=', $this->_internal_id)->leftJoin('positions', 'persons._internal_id', '=', 'positions.person_id')->whereNull('positions.deleted_at')->leftJoin('positions as childs', 'positions._internal_id', '=', 'childs.parent_id')->whereNull('childs.deleted_at')->leftJoin('persons as members', 'members._internal_id', '=', 'childs.person_id')->whereNotNull('members._internal_id')->distinct()->select('members.*');
        if($current) {
            return $r->where('positions.start_date', '<=', Carbon::now())->where('positions.end_date', '>=', Carbon::now())->where('childs.start_date', '<=', Carbon::now())->where('childs.end_date', '>=', Carbon::now());
        } else {
            return $r;
        }
    }


    /**
     * returns all teams the person has a position in
     * @param bool $current set to true to only recognise currently active positions
     * @return mixed
     */
    public function teams($current = false) {
        $r = $this->hasManyThrough('App\Team', 'App\Position');
        if($current) {
            return $r->where('positions.start_date', '<=', Carbon::now())->where('positions.end_date', '>=', Carbon::now());
        } else {
            return $r;
        }
    }
}
