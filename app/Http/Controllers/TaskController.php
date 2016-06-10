<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 01.06.16
 * Time: 16:07
 */

namespace App\Http\Controllers;

use App\Team;
use App\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Request $request
     */
    public function all(Request $request) {
        // get the current user
        $person = Auth::user();

        // start building a query to retrieve the person ids, by starting with all members of the current user
        $persons = $person->membersAsPersons(true);

        // apply the teams filter
        if($request->has('teams')) {
            if(!is_array($request->input('teams')) || count($request->input('teams')) < 1) {
                $persons = null;
            } else {
                $teamIds = [];

                // iterate through teams filter to get the internal ids
                foreach($request->input('teams') as $teamId) {
                    if(substr($teamId, 0, 1) == '_') {
                        $teamId = substr($teamId, 1);
                    } else {
                        $teamId = Team::where('id', $teamId)->firstOrFail()->_internal_id;
                    }
                    $teamIds[] = $teamId;
                }

                // insert teams filter into persons query
                $persons = $persons->whereIn('childs.team_id', $teamIds);
            }
        }

        // apply the persons filter
        if($request->has('persons') && !is_null($persons)) {
            if(!is_array($request->input('persons')) || count($request->input('persons')) < 1) {
                $persons = null;
            } else {
                $personIds = [];
                $inPersonsFilter = false;   // we need to know for the skip_own_tasks filter if the current person is in the persons filter array

                // iterate through persons filter to get internal ids
                foreach ($request->input('persons') as $personId) {
                    if (substr($personId, 0, 1) == '_') {
                        $personId = substr($personId, 1);
                    } else {
                        $personId = Team::where('id', $personId)->firstOrFail()->_internal_id;
                    }
                    $personIds[] = $personId;

                    // also check if the current user is in the array, because we maybe need that later
                    if($personId == $person->_internal_id) $inPersonsFilter = true;
                }

                // insert persons filter into persons query
                $persons = $persons->whereIn('members._internal_id', '=', $personIds);
            }
        }

        // finish preparation of persons query
        if(is_null($persons)) {
            $persons = DB::raw('SELECT 0');
        } else {
            $persons = $persons->select('members._internal_id');
        }

        // create the tasks query and inject the persons query in the where clause
        $tasks = Task::whereIn('person_id', function($query) use ($persons) {
            $query->select(DB::raw(substr($persons->toSql(), 7)))->mergeBindings($persons->getQuery()->getQuery());
        });

        // if skip_own_tasks is not set, check if we also have to retrieve tasks of the current user.
        // Attention: this has to come directly after the persons query injection to make the orWhere work!
        if(!$request->has('skip_own_tasks') || !$request->input('skip_own_tasks')) {
            // teams filter is not relevant, because we only select teams the person is in
            // still check if the persons filter is not set or includes the current user
            if(!$request->has('persons') || $inPersonsFilter) {
                $tasks->orWhere('person_id', '=', $person->_internal_id);
            }
        }

        // done filter
        if($request->has('done')) {
            $tasks = $tasks->where('done', '=', $request->input('done'));
        }

        // approved filter
        if($request->has('approved')) {
            $tasks = $tasks->where('approved', '=', $request->input('approved'));
        }

        // return results
        return $tasks->with('person', 'added_by', 'approved_by')->paginate();
    }

    /**
     * View a task
     *
     * @param $taskId
     * @return array
     */
    public function view($taskId) {
        // get task
        $task = Task::with('person', 'added_by', 'approved_by')->findOrFail($taskId);

        // check permissions
        $this->authorize($task);

        // return task
        return ['task' => $task];
    }


    /**
     * mark a task as done
     *
     * @param Request $request
     * @param $taskId
     * @return array
     */
    public function done(Request $request, $taskId) {
        // get task
        $task = Task::findOrFail($taskId);

        // check permissions
        $this->authorize($task);

        // mark as done
        $task->done = true;
        $task->done_at = Carbon::now();

        // check if needed time is set (optional parameter)
        if($request->has('needed')) $task->needed = $request->input('needed');

        // save and return
        if($task->save()) {
            return ['task' => $task];
        } else {
            abort(500, "Could not save task");
        }
    }

    /**
     * mark a task as done
     *
     * @param $taskId
     * @return array
     */
    public function delete($taskId) {
        // get task
        $task = Task::findOrFail($taskId);

        // check permissions
        $this->authorize($task);

        // delete task
        if(Task::destroy($taskId)) {
            return ['status' => ['code' => 204, 'message' => 'success']];
        } else {
            abort(500, "Database error");
        }
    }

    /**
     * update a task
     *
     * @param Request $request
     * @param $taskId
     * @return array
     */
    public function update(Request $request, $taskId) {
        // get task
        $task = Task::findOrFail($taskId);

        // check permissions for update
        $this->authorize($task);

        // update normal attributes
        if($request->has('priority')) $task->priority = $request->input('priority');
        if($request->has('needed')) $task->needed = $request->input('needed');

        // if update for 'estimated' is requested
        if($request->has('estimated')) {
            // check special permissions
            $this->authorize('update_estimated', $task);

            // update estimated
            $task->estimated = $request->input('estimated');
        }

        // if update for 'due' is requested
        if($request->has('due')) {
            // check special permissions
            $this->authorize('update_due', $task);

            // update due
            $task->due = $request->input('due');
        }

        // save and return
        if($task->save()) {
            return ['task' => $task];
        } else {
            abort(500, "Could not save task");
        }
    }

    /**
     * mark a task as approved
     *
     * @param Request $request
     * @param $taskId
     * @return array
     */
    public function approve(Request $request, $taskId) {
        // get task
        $task = Task::findOrFail($taskId);

        //check permissions
        $this->authorize($task);

        // update attributes
        $task->approved_at = Carbon::now();
        $task->approved_by = Auth::user()->_internal_id;
        $task->approved = true;

        // save and return
        if($task->save()) {
            return ['task' => $task];
        } else {
            abort(500, "Could not save task");
        }
    }

    /**
     * set the priorities of tasks equal to how their ids are ordered in the givven array
     *
     * @param Request $request
     * @return array
     */
    public function prioritize(Request $request) {
        if(!$request->has('ids')) abort(400, "argument 'ids' missing");

        $ids = $request->input('ids', []);

        $prio = 0;
        foreach($ids as $id) {
            $task = Task::findOrFail($id);

            $this->authorize($task);

            $task->priority = $prio;

            if(!$task->save()) abort(500, "Could not save a task");

            $prio++;
        }

        return ['status' => ['code' => 200, 'message' => 'success']];
    }
}