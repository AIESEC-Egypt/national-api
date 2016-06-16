<?php

namespace App\Jobs;

use App\Entity;
use App\KPIvalueDate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KPIsEntities extends Job
{
    /**
     * @var Entity
     */
    protected $_entity;

    /**
     * @var Carbon
     */
    protected $_from;

    /**
     * @var Carbon
     */
    protected $_to;

    /**
     * @var KPIvalueDate
     */
    protected $_date;

    /**
     * Create a new job instance.
     * @param Entity $entity
     * @param Carbon $from
     * @param Carbon $to
     * @param KPIvalueDate $date
     * @internal param Entity $team
     */
    public function __construct(Entity $entity, Carbon $from, Carbon $to, KPIvalueDate $date)
    {
        $this->_entity = $entity;
        $this->_from = $from;
        $this->_to = $to;
        $this->_date = $date;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // get current term of entity
        $current_term = $this->_entity->terms()->current()->first();

        // check if there is a current term
        if(!is_null($current_term)) {
            /*
             * get raw data
             */
            // get number of teams
            $teamsTotal = $current_term->teams()->count();
            $teamsTotalCalc = Carbon::now();

            // get number of positions
            $positionsTotal = $current_term->allPositions()->timeframe($this->_from, $this->_to)->count();
            $positionsTotalCalc = Carbon::now();

            // get number of matched positions
            $positionsMatched = $current_term->allPositions()->timeframe($this->_from, $this->_to)->matched()->count();
            $positionsMatchedCalc = Carbon::now();

            // get number of leader positions
            $positionsLeader = $current_term->allPositions()->timeframe($this->_from, $this->_to)->leader()->count();
            $positionsLeaderCalc = Carbon::now();

            // get number of matched leader positions
            $positionsLeaderMatched = $current_term->allPositions()->timeframe($this->_from, $this->_to)->leader()->matched()->count();
            $positionsLeaderMatchedCalc = Carbon::now();

            // get number of subteam leader positions
            $positionsSubTeamLeader = $current_term->allPositions()->timeframe($this->_from, $this->_to)->leader()->nonTeamLeader()->count();
            $positionsSubTeamLeaderCalc = Carbon::now();

            // get number of matched subteam leader positions
            $positionsSubTeamLeaderMatched = $current_term->allPositions()->timeframe($this->_from, $this->_to)->leader()->nonTeamLeader()->matched()->count();
            $positionsSubTeamLeaderMatchedCalc = Carbon::now();

            // get number of team leader positions
            $positionsTeamLeader = $current_term->allPositions()->timeframe($this->_from, $this->_to)->leader()->nonSubTeamLeader()->count();
            $positionsTeamLeaderCalc = Carbon::now();

            // get number of matched team leader positions
            $positionsTeamLeaderMatched = $current_term->allPositions()->timeframe($this->_from, $this->_to)->leader()->nonSubTeamLeader()->matched()->count();
            $positionsTeamLeaderMatchedCalc = Carbon::now();

            // get number of member positions
            $positionsMember = $current_term->allPositions()->timeframe($this->_from, $this->_to)->teamType('normal')->nonLeader()->count();
            $positionsMemberCalc = Carbon::now();

            // get number of matched member positions
            $positionsMemberMatched = $current_term->allPositions()->timeframe($this->_from, $this->_to)->teamType('normal')->nonLeader()->matched()->count();
            $positionsMemberMatchedCalc = Carbon::now();

            // get current number of persons
            $personsTotal = $current_term->allPositions()->timeframe($this->_from, $this->_to)->whereNotNull('person_id')->count(DB::raw('DISTINCT `positions`.`person_id`'));
            $personsTotalCalc = Carbon::now();

            // get current number of active persons
            $personsActive = $current_term->allPositions()->timeframe($this->_from, $this->_to)->withActivity($this->_from, $this->_to)->count(DB::raw('DISTINCT `positions`.`person_id`'));
            $personsActiveCalc = Carbon::now();

            // get current number of active persons which have approved activity
            $personsActiveApproved = $current_term->allPositions()->timeframe($this->_from, $this->_to)->withApprovedActivity($this->_from, $this->_to)->count(DB::raw('DISTINCT `positions`.`person_id`'));
            $personsActiveApprovedCalc = Carbon::now();


            /*
             * calculate and save KPI values
             */
            // total number of teams
            $this->singleKPI('teams', 'total', $teamsTotal, $teamsTotalCalc);

            // total number of positions
            $this->singleKPI('positions', 'total', $positionsTotal, $positionsTotalCalc);

            // absolute number of matched positions and in relation to total number of positions
            $this->doubleKPI('positions', 'matched', $positionsMatched, $positionsTotal, $positionsMatchedCalc);

            // absolute number of leader positions (team leader and subteam leader) and in relation to total number of positions
            $this->doubleKPI('positions', 'leader', $positionsLeader, $positionsTotal, $positionsLeaderCalc);

            // percentage of how many leader positions are subteam leaders
            $this->singleKPI('positions', 'leader_subteamleader', ($positionsLeader > 0) ? ($positionsSubTeamLeader / $positionsLeader) * 100 : 0, $positionsSubTeamLeaderCalc, 'percentage');

            // percentage of how many leader positions are team leaders
            $this->singleKPI('positions', 'leader_teamleaders', ($positionsLeader > 0) ? ($positionsTeamLeader / $positionsLeader) * 100 : 0, $positionsTeamLeaderCalc, 'percentage');

            // absolute number of matched leader positions (team leader and subteam leader) and in relation to total number of leader positions
            $this->doubleKPI('positions', 'leader_matched', $positionsLeaderMatched, $positionsLeader, $positionsLeaderMatchedCalc);

            // absolute number of subteam leader positions and in relation to total number of positions
            $this->doubleKPI('positions', 'subteamleader', $positionsSubTeamLeader, $positionsTotal, $positionsSubTeamLeaderCalc);

            // absolute number of matched subteam leader positions and in relation to total number of subteam leader positions
            $this->doubleKPI('positions', 'subteamleader_matched', $positionsSubTeamLeaderMatched, $positionsSubTeamLeader, $positionsSubTeamLeaderMatchedCalc);

            // absolute number of team leader positions and in relation to total number of positions
            $this->doubleKPI('positions', 'teamleader', $positionsTeamLeader, $positionsTotal, $positionsTeamLeaderCalc);

            // absolute number of matched team leader positions and in relation to total number of team leader positions
            $this->doubleKPI('positions', 'teamleader_matched', $positionsTeamLeaderMatched, $positionsTeamLeader, $positionsTeamLeaderMatchedCalc);

            // absolute number of member positions and in relation to total number of positions
            $this->doubleKPI('positions', 'member', $positionsMember, $positionsTotal, $positionsMemberCalc);

            // absolute number of matched member positions and in relation to total number of member positions
            $this->doubleKPI('positions', 'member_matched', $positionsMemberMatched, $positionsMember, $positionsMemberMatchedCalc);

            // total number of individual persons
            $this->singleKPI('persons', 'total', $personsTotal, $personsTotalCalc);

            // absolute number of individual persons which finished tasks in this period and in relation to total number of individual persons
            $this->doubleKPI('persons', 'active', $personsActive, $personsTotal, $personsActiveCalc);

            // absolute number of individual persons which finished tasks in this period which were also approved in this period and in relation to total number of individual persons
            $this->doubleKPI('persons', 'active_approved', $personsActiveApproved, $personsTotal, $personsActiveApprovedCalc);
        }
    }

    private function singleKPI($type, $subtype, $value, $calc, $unit = 'number') {
        $kpi = $this->_entity->KPIs()->where('type', $type)->where('subtype', $subtype)->first();
        if(is_null($kpi)) {
            $kpi = $this->_entity->KPIs()->create(['type' => $type, 'subtype' => $subtype, 'unit' => $unit]);
        }
        $kpi->values()->create([
            'value' => $value,
            'calculated_at' => $calc,
            'date_id' => $this->_date->id
        ]);
    }

    private function doubleKPI($type, $name, $value, $base, $calc) {
        // add absolute value
        $this->singleKPI($type, $name . '_absolute', $value, $calc, 'number');

        // add relative value
        $this->singleKPI($type, $name . '_relative', ($base > 0) ? ($value / $base) * 100 : 0, $calc, 'percentage');
    }
}
