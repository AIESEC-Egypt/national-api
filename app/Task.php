<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

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