<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Task extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tasks';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'priority', 'estimated', 'needed'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['deleted_at', 'person_id'];

    /**
     * date attributes
     *
     * @var array
     */
    protected $dates = ['done_at', 'approved_at', 'due'];

    public function person() {
        return $this->belongsTo('App\Person');
    }

    public function added_by() {
        return $this->belongsTo('App\Person', 'added_by');
    }

    public function approved_by() {
        return $this->belongsTo('App\Person', 'approved_by');
    }
}