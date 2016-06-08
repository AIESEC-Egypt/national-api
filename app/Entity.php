<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

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
    protected $hidden = ['deleted_at'];
    
    public function terms() {
        return $this->hasMany('App\Term');
    }

    public function departments() {
        return $this->hasMany('App\Departments');
    }

    public function teams() {
        return $this->hasManyThrough('App\Team', 'App\Term');
    }
}