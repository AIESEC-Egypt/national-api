<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    /**
     * returns the person the task belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function person() {
        return $this->belongsTo('App\Person');
    }

    /**
     * returns the person which added the task
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function added_by() {
        return $this->belongsTo('App\Person', 'added_by');
    }

    /**
     * returns the person which approved the task
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approved_by() {
        return $this->belongsTo('App\Person', 'approved_by');
    }

    /**
     * scopes a task query to those which are done
     *
     * @param $query
     * @param null|Carbon $from
     * @param null|Carbon $to
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeDone($query, $from = null, $to = null) {
        $query = $query->where('tasks.done', '=', true);

        if(!is_null($from)) {
            $query = $query->where('tasks.done_at', '>=', $from);
        }

        if(!is_null($to)) {
            $query = $query->where('tasks.done_at', '<=', $to);
        }
        return $query;
    }

    /**
     * scopes a task query to those which are approved
     *
     * @param $query
     * @param null|Carbon $from
     * @param null|Carbon $to
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeApproved($query, $from = null, $to = null) {
        $query = $query->where('tasks.approved', '=', true);

        if(!is_null($from)) {
            $query = $query->where('tasks.approved_at', '>=', $from);
        }

        if(!is_null($to)) {
            $query = $query->where('tasks.approved_at', '<=', $to);
        }
        return $query;
    }

    /**
     * scopes a task query to those which have a due date
     *
     * @param $query
     * @param null|Carbon $from
     * @param null|Carbon $to
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeWithDue($query, $from = null, $to = null) {
        $query = $query->whereNotNull('tasks.due');

        if(!is_null($from)) {
            $query = $query->where('tasks.due', '>=', $from);
        }

        if(!is_null($from)) {
            $query = $query->where('tasks.due', '<=', $to);
        }
        return $query;
    }

    /**
     * scopes a task query to those without a due date
     *
     * @param $query
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeWithoutDue($query) {
        return $query->whereNull('tasks.due');
    }

    /**
     * scopes a task query to those with a missed due date
     *
     * @param $query
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeMissedDue($query) {
        return $query->where('tasks.done_at', '>', 'tasks.due')->orWhere(function($query) {
            return $query->where('tasks.done', '=', false)->where('tasks.due', '<', DB::raw('NOW()'));
        });
    }

}