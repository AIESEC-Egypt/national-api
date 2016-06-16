<?php

namespace App\Http\Controllers;

use App\Person;
use App\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        // check for current person
        if($id == "current") {  // alias for current user
            return Auth::user();
        } elseif(substr($id, 0, 1) == '_') {  // when the request uses the internal id the given id starts with an underscore
            // get person via _internal_id
            return Person::findOrFail(substr($id, 1));
        } else {    // get user via GIS id
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
     * returns the not approved tasks of a person
     *
     * @GetParam bool $skip_done optional
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
     * returns the positions of a person
     *
     * @GetParam bool $only_current optional
     * @GetParam bool $only_leader optional
     * @GetParam bool $skip_leader optional
     * @GetParam bool $skip_teamleader optional
     * @GetParam bool $skip_subteamleader optional
     * @GetParam string $teamtype optional normal|eb
     *
     * @param Request $request
     * @param $personId
     * @return array
     */
    public function positions(Request $request, $personId) {
        // get person from database
        $person = $this->getPerson($personId);

        // check Permissions
        $this->authorize($person);

        // build query
        $positions = $person->positions();

        if(Gate::allows('positions_childs', $person)) {
            $positions = $positions->with('childs', 'childs.person');
        }

        if(Gate::allows('positions_kpis', $person)) {
            $positions = $positions->with('childs.person.kpis', 'childs.person.kpis.latestValue');
        }

        // filter only_current
        if(intval($request->input('only_current', 0)) === 1) {
            $positions = $positions->current();
            
            if(Gate::allows('positions_kpis_current', $person)) {
                $positions = $positions->with('childs.person.kpis', 'childs.person.kpis.latestValue');
            }
        }

        // filter only_leader
        if(intval($request->input('only_leader', 0)) === 1) {
            $positions = $positions->leader();
        }

        // filter skip_leader
        if(intval($request->input('only_leader', 0)) === 1) {
            $positions = $positions->nonLeader();
        }

        // filter skip_teamleader
        if(intval($request->input('skip_teamleder', 0)) === 1) {
            $positions = $positions->nonTeamLeader();
        }

        // filter skip_subteamleader
        if(intval($request->input('skip_subteamleader', 0)) === 1) {
            $positions = $positions->nonSubTeamLeader();
        }

        // filter teamtype
        if($request->has('teamtype')) {
            $positions = $positions->teamType($request->input('teamtype'));
        }

        // return results
        return ['positions' => $positions->get()];
    }

    /**
     * Returns the KPIs of the person and their latest value
     *
     * @param $personId
     * @return array
     */
    public function kpis($personId) {
        // get person
        $person = $this->getPerson($personId);

        // check permissions
        $this->authorize($person);

        // return KPIs
        return ['kpis' => $person->KPIs()->with('latestValue')->get()];
    }

    /**
     * Returns the persons managed by the given person
     *
     * @param $personId
     * @return mixed
     */
    public function managing($personId) {
        // get person
        $person = $this->getPerson($personId);

        // check permissions
        $this->authorize($person);

        // return KPIs
        return $person->managing()->paginate();
    }
}
