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
     * date fields
     *
     * @var array
     */
    protected $dates = ['contacted_at'];

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
     */
    public function positions() {
        return $this->hasMany('App\Position');
    }

    /**
     * returns the positions of all people who are a team leader of the person
     */
    public function leadersAsPositions() {
        return $this->hasMany('App\Position')->leftJoin('positions as parents', 'positions.parent_id', '=', 'parents._internal_id')->whereNull('parents.deleted_at')->whereNotNull('parents._internal_id')->select('parents.*');
    }

    /**
     * returns the persons which are a leader for this person
     */
    public function leadersAsPersons() {
        return $this->belongsTo('App\Person')->whereNotNull('persons._internal_id')->orWhere('persons._internal_id', '=', $this->_internal_id)->leftJoin('positions', 'persons._internal_id', '=', 'positions.person_id')->whereNull('positions.deleted_at')->leftJoin('positions as parents', 'parents._internal_id', '=', 'positions.parent_id')->whereNull('parents.deleted_at')->leftJoin('persons as leaders', 'leaders._internal_id', '=', 'parents.person_id')->whereNotNull('leaders._internal_id')->distinct()->select('leaders.*');
    }

    /**
     * returns all positions of members of this person
     */
    public function membersAsPositions() {
        return $this->hasMany('App\Position')->leftJoin('positions as members', 'members.parent_id', '=', 'positions._internal_id')->whereNull('members.deleted_at')->whereNotNull('members._internal_id');
    }

    /**
     * returns the persons which are a leader for this person in any position
     */
    public function membersAsPersons() {
        return $this->belongsTo('App\Person')->whereNotNull('persons._internal_id')->orWhere('persons._internal_id', '=', $this->_internal_id)->leftJoin('positions', 'persons._internal_id', '=', 'positions.person_id')->whereNull('positions.deleted_at')->leftJoin('positions as childs', 'positions._internal_id', '=', 'childs.parent_id')->whereNull('childs.deleted_at')->leftJoin('persons as members', 'members._internal_id', '=', 'childs.person_id')->whereNotNull('members._internal_id')->distinct()->select('members.*');
    }

    /**
     * returns all teams the person has a position in
     */
    public function teams() {
        return $this->hasManyThrough('App\Team', 'App\Position');
    }

    /**
     * returns all KPIs of this person
     */
    public function KPIs() {
        return $this->hasMany('App\KPI');
    }

    /**
     * scope query to a specific team type
     */
    public function scopeTeamType($query, $team_type) {
        return $query->leftJoin('teams', 'positions.team_id', '=', 'teams._internal_id')->where('team_type', '=', $team_type)->whereNull('teams.deleted_at')->select('positions.*');
    }

    /**
     * scope query to non leader positions
     */
    public function scopeNonLeader($query) {
        return $query->leftJoin('positions as subpos', 'subpos.parent_id', '=', 'positions._internal_id')->whereNull('subpos._internal_id')->whereNull('subpos.deleted_at')->whereNotNull('positions.parent_id')->select('positions.*');
    }

    /**
     * scope query to leader positions only
     */
    public function scopeLeader($query) {
        return $query->leftJoin('positions as subpos', 'subpos.parent_id', '=', 'positions._internal_id')->whereNull('subpos.deleted_at')->whereNotNull('subpos._internal_id')->distinct()->select('positions.*');
    }

    /**
     * scope query to non team leader positions (works only with Leader scope)
     */
    public function scopeNonTeamLeader($query) {
        return $query->whereNotNull('positions.parent_id');
    }

    /**
     * scope query to non sub team leader positions (works only with Leader scope)
     */
    public function scopeNonSubTeamLeader($query) {
        return $query->whereNull('positions.parent_id');
    }

    /**
     * scope query to only currently active positions
     */
    public function scopeCurrent($query) {
        $query = $query->where('positions.start_date', '<=', Carbon::now())->where('positions.end_date', '>=', Carbon::now());
        foreach($query->getQuery()->joins as $join) {
            switch($join->table) {
                case 'positions as childs':
                    $query = $query->where('childs.start_date', '<=', Carbon::now())->where('childs.end_date', '>=', Carbon::now());
                    break;

                case 'positions as parents':
                    $query = $query->where('parents.start_date', '<=', Carbon::now())->where('parents.end_date', '>=', Carbon::now());
                    break;
            }
        }
        return $query;
    }

    /**
     * updates this persons values with the values from a GIS response
     *
     * @param object $remote person object from the GIS
     */
    public function updateFromGIS($remote) {
        /*
         * prepare remote object
         */
        // make is_employee an bool
        if(isset($remote->is_employee) && is_null($remote->is_employee)) $remote->is_employee = false;
        // profile_picture_url is sometimes called profile_photo_url
        if(isset($remote->profile_photo_url) && !isset($remote->profile_picture_url)) $remote->profile_picture_url = $remote->profile_photo_url;
        // the url of the cv is in the cv_url object
        if(isset($remote->cv_url) && is_object($remote->cv_url)) $remote->cv_url = $remote->cv_url->url;
        // translate home_lc id to home_entity
        if(isset($remote->home_lc) && isset($remote->home_lc->id)) {
            $lc = Entity::where('id', $remote->home_lc->id)->first();
            $remote->home_entity = (is_null($lc)) ? null : $lc->_internal_id;
        }
        // translate contacted_by id
        if(isset($remote->contacted_by) && isset($remote->contacted_by->id)) {
            $person = Person::where('id', $remote->contacted_by->id)->first();
            $remote->contacted_by = (is_null($person)) ? null : $person->_internal_id;
        }

        /*
         * update scalar values (and those which were made scalar)
         */
        $scalarFields = ['email', 'first_name', 'middle_name', 'last_name', 'dob', 'home_entity', 'profile_picture_url', 'interviewed', 'is_employee', 'status', 'phone', 'location', 'nps_score', 'contacted_at', 'contacted_by', 'cv_url'];
        foreach($scalarFields as $field) {
            if(isset($remote->$field) && $remote->$field != $this->$field) {
                $this->$field = $remote->$field;
                //@Todo update push queue
            }
        }

        // save to database
        $this->save();

        /*
         * sync programmes
         *
         * (programmes have no internal id, relationships are directly mapped via the gis id)
         * These programmes are calculated on GIS side and different from those in the profile -> no queue update necessary
         */
        $programmeIds = [];
        foreach($remote->programmes as $programme) {
            $programmeNational = Programme::find($programme->id);
            if($programmeNational != null) {
                $programmeIds[] = $programme->id;
            }
        }
        $this->programmes()->sync($programmeIds);

        /*
         * sync managers
         */
        $managerIds = [];
        foreach($remote->managers as $manager) {
            $managerNational = Person::where('id', $manager->id)->first();
            if($managerNational != null) {
                $managerIds[] = $managerNational->_internal_id;
            }
        }
        $this->managers()->sync($managerIds);
        // @Todo update push queue

    }
}
