<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Programme extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'programmes';

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
    protected $hidden = [];

    public function persons() {
        return $this->belongsToMany('App\Person', 'persons_programmes');
    }
}