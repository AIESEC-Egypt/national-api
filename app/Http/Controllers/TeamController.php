<?php

namespace App\Http\Controllers;

use App\Team;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * determines which kind of id is given and returns the corresponding team
     *
     * @param $id
     * @return Team
     */
    private function getTeam($id) {
        // when the request uses the internal id the given id starts with an underscore
        if(substr($id, 0, 1) == '_') {
            // get team via _internal_id
            return Team::findOrFail(substr($id, 1));
        } else {
            // get team via GIS id
            return Team::where('id', $id)->firstOrFail();
        }
    }

    /**
     * teams autocomplete
     * 
     * @GetParam string $q optional
     *
     * @param Request $request
     * @return array
     */
    public function autocomplete(Request $request) {
        // check permissions
        $this->authorize(Auth::user());

        // check parameter
        if($request->has('q') && strlen($request->input('q')) > 1) {
            // prepare query
            $teams = Team::query()->search($request->input('q'))->with('term', 'term.entity')->limit(15);

            // return data
            return ['teams' => $teams->get()];
        } else {
            // return no persons if parameter is too short or not set
            return ['teams' => []];
        }
    }
    
    /**
     * view a Team
     *
     * @param $teamId
     * @return array
     */
    public function view($teamId) {
        // get team
        $team = $this->getTeam($teamId);

        // check permissions
        $this->authorize($team);

        $team->load('term', 'term.entity');

        if(Gate::allows('kpis', $team)) {
            $team->load('kpis', 'kpis.latestValue');
        }

        // return data
        return ['team' => $team];
    }

    /**
     * get the KPIs of a team
     *
     * @param $teamId
     * @return array
     */
    public function kpis($teamId) {
        // get team
        $team = $this->getTeam($teamId);

        // check permissions
        $this->authorize($team);

        // return positions
        return ['kpis' => $team->KPIs()->with('latestValue')->get()];
    }

    /**
     * get the position tree of a team
     *
     * @param $teamId
     * @return array
     */
    public function positions($teamId) {
        // get team
        $team = $this->getTeam($teamId);

        // check permissions
        $this->authorize($team);

        // return positions
        return ['positions' => $team->positions()->get()];
    }
}