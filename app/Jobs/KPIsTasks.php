<?php

namespace App\Jobs;

use App\Person;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KPIsTasks extends Job
{
    /**
     * @var Person
     */
    protected $_person;

    /**
     * @var Carbon
     */
    protected $_from;

    /**
     * @var Carbon
     */
    protected $_to;

    /**
     * Create a new job instance.
     * @param Person $person
     * @param Carbon $from
     * @param Carbon $to
     */
    public function __construct(Person $person, Carbon $from, Carbon $to)
    {
        $this->_person = $person;
        $this->_from = $from;
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
         * calculate missed deadlines
         */
        // prepare base query
        $baseQuery = $this->_person->tasks()->where('due', '>=', $this->_from)->where('due', '<=', $this->_to);
        // get total numbers of deadlines
        $total = $baseQuery->count();
        // get number of missed deadlines
        $missed = $baseQuery->where('done', false)->orWhereColumn('done_at', '>', 'due')->count();
        // calculate relative value
        $relative = ($total > 0) ? ($missed / $total) * 100 : 0;
        // save current time
        $calculated = Carbon::now();
        // save relative value to database
        $this->getKPI('missed_deadlines_relative')->values()->create([
            'value' => $relative,
            'calculated_at' => $calculated,
            'from' => $this->_from,
            'to' => $this->_to
        ]);
        // save absolute value to database
        $this->getKPI('missed_deadlines_absolute')->values()->create([
            'value' => $missed,
            'calculated_at' => $calculated,
            'from' => $this->_from,
            'to' => $this->_to
        ]);

        /*
         * calculate time KPIs
         */
        // prepare base query
        $baseQuery = $this->_person->tasks()->select([DB::raw('SUM(`estimated`) as estimated'), DB::raw('SUM(`needed`) as needed')])->where('done_at', '>=', $this->_from)->where('done_at', '<=', $this->_to)->groupBy('person_id');

        // get both times for all tasks of this period
        $all = $baseQuery->first();
        // save calculation date
        $all_calculated = Carbon::now();

        // get both times for approved tasks of this period (approved during the period)
        $approved = $baseQuery->where('approved', true)->where('approved_at', '>=', $this->_from)->where('approved_at', '<=', $this->_to)->first();
        // save calculation date
        $approved_calculated = Carbon::now();
        
        // save total estimated time
        $this->getKPI('estimated_time_total')->values()->create([
            'value' => (isset($all->estimated) && !is_null($all->estimated)) ? $all->estimated : 0,
            'calculated_at' => $all_calculated,
            'from' => $this->_from,
            'to' => $this->_to
        ]);

        // save total needed time
        $this->getKPI('needed_time_total')->values()->create([
            'value' => (isset($all->needed) && !is_null($all->needed)) ? $all->needed : 0,
            'calculated_at' => $all_calculated,
            'from' => $this->_from,
            'to' => $this->_to
        ]);

        // save approved estimated time
        $this->getKPI('estimated_time_approved')->values()->create([
            'value' => (isset($approved->estimated) && !is_null($approved->estimated)) ? $approved->estimated : 0,
            'calculated_at' => $approved_calculated,
            'from' => $this->_from,
            'to' => $this->_to
        ]);

        // save approved estimated time
        $this->getKPI('needed_time_approved')->values()->create([
            'value' => (isset($approved->needed) && !is_null($approved->needed)) ? $approved->needed : 0,
            'calculated_at' => $approved_calculated,
            'from' => $this->_from,
            'to' => $this->_to
        ]);
    }

    private function getKPI($subtype) {
        $kpi = $this->_person->KPIs()->where('type', 'tasks')->where('subtype', $subtype)->first();
        if(is_null($kpi)) {
            $kpi = $this->_person->KPIs()->create(['type' => 'tasks', 'subtype' => $subtype]);
        }
        return $kpi;
    }
}
