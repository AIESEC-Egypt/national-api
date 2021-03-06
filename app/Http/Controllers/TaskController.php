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
     * @GetParam integer $needed optional
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
     * delete a task
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
     * @GetParam integer $priority optional
     * @GetParam integer $needed optional
     * @GetParam integer $estimated optioanl
     * @GetParam integer $due optional
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
     * @getParam array $ids required
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