<?php

namespace App\Http\Controllers;

use App\Person;
use App\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
     * returns the not done tasks a person
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

        // get and return tasks
        return ['tasks' => $person->tasks()->with('added_by', 'approved_by')->where('done', false)->orderBy('priority')->get()];
    }

    
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
            $task->priority = $person->tasks()->where('done', false)->max('priority') + 1;
        }

        // save Task
        $person->tasks()->save($task);

        // return all tasks of the person
        return ['tasks' => $person->tasks()->where('done', false)->orderBy('priority')->get()];
    }
}
