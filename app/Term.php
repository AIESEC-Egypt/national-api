<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'terms';

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
    protected $hidden = ['deleted_at'];

    /**
     * date attributes
     *
     * @var array
     */
    protected $dates = ['start_date', 'end_date'];

    public function entity() {
        return $this->belongsTo('App\Entity');
    }

    public function teams() {
        return $this->hasMany('App\Team');
    }
}