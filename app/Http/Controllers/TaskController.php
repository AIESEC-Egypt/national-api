<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 01.06.16
 * Time: 16:07
 */

namespace App\Http\Controllers;

use Gate;
use App\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // save and return task
        return ['task' => $task->save()];
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
            return ['status' => ['code' => 500, 'message' => 'failure']];
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

        // save and return
        return ['task' => $task->save()];
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
        return ['task' => $task->save()];
    }
}