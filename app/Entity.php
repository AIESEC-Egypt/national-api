<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Entity extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'entities';

    /**
     * The primary key column
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
     * returns all terms of the entity
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function terms() {
        return $this->hasMany('App\Term');
    }

    /**
     * return all departments of the entity
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function departments() {
        return $this->hasMany('App\Departments');
    }

    /**
     * returns all teams of the entity (Attention: take care about terms! You also get old teams)
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function teams() {
        return $this->hasManyThrough('App\Team', 'App\Term');
    }

    /**
     * returns the parent entity
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent() {
        return $this->belongsTo('App\Entity', 'parent_id');
    }

    /**
     * returns all parent entities recursive
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parents() {
        return $this->parent()->with('parents');
    }

    /**
     * returns all child entities
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function directChilds() {
        return $this->hasMany('App\Entity', 'parent_id');
    }

    /**
     * returns all child entities recursive
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function childs() {
        return $this->directChilds()->with('childs');
    }

    /**
     * returns all KPIs of this entity
     * @returns \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function KPIs() {
        return $this->morphMany('App\KPI', 'measurable');
    }

    /**
     * scope query to entities of active positions, when querying entities via positions
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeCurrent($query) {
        return $query->where('positions.start_date', '<=', Carbon::now())->where('positions.end_date', '>=', Carbon::now());
    }

    /**
     * Determines if $person is currently in the EB of the entity
     *
     * @param Person|integer $person
     * @return bool
     */
    public function isCurrentEB($person) {
        if($person instanceof Person) {
            $person = $person->_internal_id;
        }

        $count = Position::query()->current()->where('person_id', '=', $person)
            ->leftJoin('teams', 'teams._internal_id', '=', 'positions.team_id')
            ->where('team_type', '=', 'eb')->whereNull('teams.deleted_at')
            ->leftJoin('terms', 'terms._internal_id', '=', 'teams.term_id')
            ->where('terms.entity_id', '=', $this->original['_internal_id'])->whereNull('terms.deleted_at')
            ->count();

        return ($count > 0);
    }
}