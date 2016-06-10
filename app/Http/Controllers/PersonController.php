<?php

namespace App\Http\Controllers;

use App\Person;
use App\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PersonController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * get the person behind a request id
     *
     * @param $id
     * @return mixed
     */
    private function getPerson($id) {
        // when the request uses the internal id the givven id starts with an underscore
        if(substr($id, 0, 1) == '_') {
            // get person via _internal_id
            return Person::findOrFail(substr($id, 1));
        } else {
            // get person via GIS id
            return Person::where('id', $id)->firstOrFail();
        }
    }

    /**
     * view a person object
     *
     * @param $personId
     * @return array
     */
    public function view($personId) {
        // get person
        $person = $this->getPerson($personId);

        // check permissions
        $this->authorize($person);

        // return person
        return ['person' => $person];
    }

    /**
     * returns the not approved tasks a person
     *
     * @GetParam bool skip_done optional
     *
     * @param Request $request
     * @param $personId
     * @return array
     */
    public function listTasks(Request $request, $personId) {
        // get person
        $person = $this->getPerson($personId);

        // check permissions
        $this->authorize($person);

        // prepare tasks query
        $tasks = $person->tasks()->with('added_by')->where('approved', false)->orderBy('priority');

        // skip_done filter
        if($request->has('skip_done') && intval($request->input('skip_done')) === 1) {
            $tasks = $tasks->where('done', false);
        }

        // get and return tasks
        return ['tasks' => $tasks->get()];
    }

    /**
     * adds a tasks to this persons tasks list
     *
     * @GetParam string $name required
     * @GetParam time $estimated required
     * @GetParam int $priority optional
     * @GetParam date $due optional
     *
     * @param Request $request
     * @param $personId
     * @return array
     */
    public function addTask(Request $request, $personId) {
        // get person
        $person = $this->getPerson($personId);

        // check permissions
        $this->authorize($person);

        // check for required parameters
        if(!$request->has('name')) abort(400, "argument 'name' missing");
        if(!$request->has('estimated')) abort(400, "argument 'estimated' missing");

        // create Task
        $task = new Task();
        $task->name = $request->input('name');
        $task->estimated = $request->input('estimated');
        $task->added_by = Auth::user()->_internal_id;

        // check if priority is set
        if($request->has('priority')) {
            $task->priority = $request->input('priority');
        } else {
            $task->priority = $person->tasks()->where('approved', false)->max('priority') + 1;
        }

        // check if due date is set
        if($request->has('due')) {
            $task->due = Carbon::parse($request->input('due'));
        }

        // save Task
        $person->tasks()->save($task);

        // return all tasks of the person
        return ['tasks' => $person->tasks()->with('added_by')->where('done', false)->where('approved', false)->orderBy('priority')->get()];
    }

    /**
     * returns the positions lead by the specified person
     *
     * @GetParam bool $current
     *
     * @param Request $request
     * @param $personId
     * @return array
     */
    public function subPositions(Request $request, $personId) {
        // get person from database
        $person = $this->getPerson($personId);

        // check permissions
        $this->authorize($person);

        // build query
        $positions = $person->membersAsPosition()->with('person', 'team');

        // proceed current filter
        if($request->has('current') && intval($request->input('current')) === 1) {
            $positions = $positions->current();
        }

        // get and return
        return ['positions' => $positions->get()];
    }
}
