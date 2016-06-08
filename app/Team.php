<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'teams';

    /**
     * the primary key
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
    protected $hidden = ['deleted_at', 'term_id', 'department_id', 'function_id'];

    public function department() {
        return $this->belongsTo('App\Department');
    }

    public function term() {
        return $this->belongsTo('App\Term');
    }

    public function _function() {
        return $this->belongsTo('App\_Function', 'function_id');
    }
}