<?php

namespace App\Jobs;

use App\KPIvalueDate;
use App\Task;
use App\Team;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class KPIsTeams extends Job
{
    /**
     * @var Team
     */
    protected $_team;

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
     * @param Team $team
     * @param Carbon $from
     * @param Carbon $to
     * @param KPIvalueDate $date
     */
    public function __construct(Team $team, Carbon $from, Carbon $to, KPIvalueDate $date)
    {
        $this->_team = $team;
        $this->_from = $from;
        $this->_date = $date;
        $this->_to = $to;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /*
         * get raw data
         */
        // get number of current positions in the team
        $totalPositions = $this->_team->allPositions()->timeframe($this->_from, $this->_to)->count();
        $totalPositionsCalc = Carbon::now();

        // get number of currently matched positions in the team (this data is not totally accurate, because we don't know when the person was matched)
        $matchedPositions = $this->_team->allPositions()->timeframe($this->_from, $this->_to)->matched()->count();
        $matchedPositionsCalc = Carbon::now();

        // get number of currently matched positions in the team (this data is not totally accurate, because we don't know when the person was matched)
        $personsTotal = $this->_team->allPositions()->timeframe($this->_from, $this->_to)->matched()->count(DB::raw('DISTINCT `positions`.`person_id`'));
        $personsTotalCalc = Carbon::now();

        // get number of positions which finished tasks in the timeframe
        $personsWithActivity = $this->_team->allPositions()->timeframe($this->_from, $this->_to)->withActivity($this->_from, $this->_to)->count(DB::raw('DISTINCT `positions`.`person_id`'));
        $personsWithActivityCalc = Carbon::now();

        // get number of positions which finished tasks in the timeframe which were approved during the timeframe
        $personsWithApprovedActivity = $this->_team->allPositions()->timeframe($this->_from, $this->_to)->withApprovedActivity($this->_from, $this->_to)->count(DB::raw('DISTINCT `positions`.`person_id`'));
        $personsWithApprovedActivityCalc = Carbon::now();

        /*
         * calculate KPIs and save values
         */
        // total positions
        $this->singleKPI('positions', 'total', $totalPositions, $totalPositionsCalc);

        // absolute number of matched Positions and in relation to total number of Positions
        $this->doubleKPI('positions', 'matched', $matchedPositions, $totalPositions, $matchedPositionsCalc);

        // absolute number of distinct persons matched to positions in this team
        $this->singleKPI('persons', 'total', $personsTotal, $personsTotalCalc);

        // absolute number of persons with finished tasks in this period and in relations to total number of persons
        $this->doubleKPI('persons', 'active', $personsWithActivity, $personsTotal, $personsWithActivityCalc);

        // absolute number of persons with finished tasks in this period which were also approved in this period and in relation to total number of persons
        $this->doubleKPI('persons', 'active_approved', $personsWithApprovedActivity, $personsTotal, $personsWithApprovedActivityCalc);
    }

    private function singleKPI($type, $subtype, $value, $calc, $unit = 'number') {
        $kpi = $this->_team->KPIs()->where('type', $type)->where('subtype', $subtype)->first();
        if(is_null($kpi)) {
            $kpi = $this->_team->KPIs()->create(['type' => $type, 'subtype' => $subtype, 'unit' => $unit]);
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
