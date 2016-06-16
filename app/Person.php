<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tasks() {
        return $this->hasMany('App\Task');
    }

    /**
     * returns all tasks which were added by the person
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function added_tasks() {
        return $this->hasMany('App\Task', 'added_by');
    }

    /**
     * returns all managers of this person (this is related to EP managers and not positions in a team)
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function managers() {
        return $this->belongsToMany('App\Person', 'persons_managers', 'person_id', 'manager_id');
    }

    /**
     * returns all persons which are managed by this person (this is related to EP managers and not positions in a team)
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function managing() {
        return $this->belongsToMany('App\Person', 'persons_managers', 'manager_id', 'person_id');
    }

    /**
     * Determines if this person is a manager of $person
     *
     * @param Person|integer $person
     * @return bool
     */
    public function isManagerOf($person) {
        if($person instanceof Person) {
            foreach($person->managers as $manager) {
                if($manager->_internal_id === $this->_internal_id) {
                    return true;
                }
            }
        } else {
            foreach($this->managing as $p) {
                if($p->_internal_id === $person) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * returns all programmes of this person
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function programmes() {
        return $this->belongsToMany('App\Programme', 'persons_programmes');
    }

    /**
     * all positions of the person
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function positions() {
        return $this->hasMany('App\Position');
    }

    /**
     * all direct child positions
     * @return \Illuminate\Database\Query\Builder
     */
    public function childPositions() {
        return Position::query()->select('childs.*')->from('positions')->where('positions.person_id', '=', $this->original['_internal_id'])
            ->leftJoin('positions as childs', 'childs.parent_id', '=', 'positions._internal_id')
            ->whereNull('childs.deleted_at')->whereNotNull('childs._internal_id');
    }

    /**
     * retrieves the child positions of this person recursively and returns them as flat collection (this means without the hierarchy)
     *
     * @param bool $current
     * @param bool $withPerson
     * @return Collection
     */
    public function childPositionsRecursiveAsFlatCollection($current = false, $withPerson = true) {
        $positions = $this->positions();

        if($current) {
            $positions = $positions->current();
        }

        if($withPerson) {
            $positions = $positions->with('person');
        }

        return $this->childPositionRecursion($positions->get(), $current, $withPerson, true);
    }

    /**
     * loads child positions recursive into a flat collection
     *
     * @param $positions
     * @param $current
     * @param $withPerson
     * @param bool $firstLevel
     * @return Collection
     */
    private function childPositionRecursion($positions, $current, $withPerson, $firstLevel = false) {
        $result = new Collection();
        $ids = [];
        foreach($positions as $position) {
            if(!$firstLevel) {
                $result->add($position);
            }
            $ids[] = $position->_internal_id;
        }
        if(count($ids) > 0) {
            $positions = Position::whereIn('parent_id', $ids)->distinct();
            if($current) {
                $positions = $positions->current();
            }
            if($withPerson) {
                $positions = $positions->with('person');
            }
            $result = $result->merge($this->childPositionRecursion($positions, $current, $withPerson));
        }
        return $result;
    }

    /**
     * Determines if this person is a leader for $person
     *
     * @param Person|integer $person either a Person object or the _internal_id attribute of a Person
     * @param bool $current
     * @return bool
     */
    public function isLeaderFor($person, $current = true) {
        if($person instanceof Person) {
            $person = $person->_internal_id;
        }
        foreach($this->childPositionsRecursiveAsFlatCollection($current, true) as $child) {
            if(isset($child->person->_internal_id) && $child->person->_internal_id === $person) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * returns all direct parent positions
     * @return \Illuminate\Database\Query\Builder
     */
    public function parentPositions() {
        return Position::query()->select('parents.*')->from('positions')->where('positions.person_id', '=', $this->original['_internal_id'])
            ->leftJoin('positions as parents', 'parents._internal_id', '=', 'positions.parent_id')
            ->whereNull('parents.deleted_at')->whereNotNull('parents._internal_id');

    }

    /**
     * retrieves the parent positions of this person recursively and returns them as flat collection (this means without the hierarchy)
     *
     * @param bool $current
     * @param bool $withPerson
     * @return Collection
     */
    public function parentPositionsRecursiveAsFlatCollection($current = false, $withPerson = true) {
        $positions = $this->positions();

        if($current) {
            $positions = $positions->current();
        }

        if($withPerson) {
            $positions = $positions->with('person');
        }

        return $this->parentPositionRecursion($positions->get(), $current, $withPerson, true);
    }

    /**
     * loads parent positions recursive into a flat collection
     *
     * @param $positions
     * @param $current
     * @param $withPerson
     * @param bool $firstLevel
     * @return Collection
     */
    private function parentPositionRecursion($positions, $current, $withPerson, $firstLevel = false) {
        $result = new Collection();
        $parentIds = [];
        foreach($positions as $position) {
            if(!$firstLevel) {
                $result->add($position);
            }
            if($position->parent_id != null) {
                $parentIds[] = $position->parent_id;
            }
        }
        if(count($parentIds) > 0) {
            $positions = Position::whereIn('_internal_id', $parentIds)->distinct();
            if($current) {
                $positions = $positions->current();
            }
            if($withPerson) {
                $positions = $positions->with('person');
            }
            $result = $result->merge($this->parentPositionRecursion($positions, $current, $withPerson));
        }
        return $result;
    }

    /**
     * returns all teams the person has a position in
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function teams() {
        return $this->hasManyThrough('App\Team', 'App\Position');
    }

    /**
     * returns all KPIs of this person
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function KPIs() {
        return $this->morphMany('App\KPI', 'measurable');
    }

    /**
     * return all entities the person has a position in
     * @return \Illuminate\Database\Query\Builder
     */
    public function entities() {
        return Entity::query()->select('entities.*')->distinct()->from('positions')
            ->where('positions.person_id', '=', $this->original['_internal_id'])->whereNull('positions.deleted_at')
            ->leftJoin('teams', 'teams._internal_id', '=', 'positions.team_id')->whereNull('teams.deleted_at')
            ->leftJoin('terms', 'terms._internal_id', '=', 'teams.term_id')->whereNull('terms.deleted_at')
            ->leftJoin('entities', 'entities._internal_id', '=', 'terms.entity_id');
    }

    /**
     * Determines if the person has a current position in $entity
     *
     * @param Entity|integer $entity
     * @return bool
     */
    public function isCurrentEntity($entity) {
        // if necessary convert $entity to id
        if($entity instanceof Entity) {
            $entity = $entity->_internal_id;
        }

        // get all the current entities and check them
        foreach($this->entities()->current()->get() as $e) {
            if($e->_internal_id === $entity) {
                return true;
            }
        }
        return false;
    }

    /**
     * checks recursively if $entity is a child in the current entity tree of the person
     *
     * @param Entity|int $entity
     * @return bool
     */
    public function isChildInCurrentEntityTree($entity) {
        // if necessary convert $entity to id
        if($entity instanceof Entity) {
            $entity = $entity->_internal_id;
        }

        // join child entities from current entities query
        $entities = $this->entities()->current()->leftJoin('entities as childs', 'childs.parent_id', '=', 'entities._internal_id')->select('childs.*')->distinct()->get();

        // create array to keep track of entities we already processed
        $done = [];

        // run as long as we got new child entities
        while(count($entities) > 0) {
            // keep track of entity Ids to use them as parent_ids
            $parentIds = [];

            // iterate through all the (child) entities we got
            foreach($entities as $e) {
                // return true if it is the wanted entity
                if($e->_internal_id == $entity) {
                    return true;
                }
                // mark entity as done
                $done[] = $e->_internal_id;
                // save id as parent_id
                $parentIds[] = $e->_internal_id;
            }

            // get all child entities ($parentIds) of the entities we iterated through, but not those we already processed ($done)
            $entities = Entity::query()->whereIn('parent_id', $parentIds)->whereNotIn('_internal_id', $done)->get();
        }
        return false;
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
    }
}
