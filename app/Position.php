<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

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
    protected $hidden = ['deleted_at', 'person_id', 'parent_id', 'team_id'];

    /**
     * date attributes
     *
     * @var array
     */
    protected $dates = ['start_date', 'end_date'];

    public function team() {
        return $this->belongsTo('App\Team');
    }

    public function person() {
        return $this->belongsTo('App\Person');
    }

    public function parent() {
        return $this->belongsTo('App\Position', 'parent_id');
    }

    public function childs() {
        return $this->hasMany('App\Position', 'parent_id');
    }
}