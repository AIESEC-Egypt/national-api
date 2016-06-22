<?php

namespace App\Jobs;

use App\KPIvalueDate;
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
     * @var KPIvalueDate
     */
    protected $_date;

    /**
     * Create a new job instance.
     * @param Person $person
     * @param Carbon $from
     * @param Carbon $to
     * @param KPIvalueDate $date
     */
    public function __construct(Person $person, Carbon $from, Carbon $to, KPIvalueDate $date)
    {
        $this->_person = $person;
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
        /*
         * get data
         */
        // total tasks which were due during the period
        $total = $this->_person->tasks()->withDue($this->_from, $this->_to)->count();
        $totalCalc = Carbon::now();

        // number of missed due dates during this period
        $missed = $this->_person->tasks()->withDue($this->_from, $this->_to)->missedDue()->count();
        $missedCalc = Carbon::now();

        // get time of tasks which were marked done during the period
        $done = $this->_person->tasks()->done($this->_from, $this->_to)->select([DB::raw('SUM(`estimated`) as estimated'), DB::raw('SUM(`needed`) as needed')])->groupBy('person_id')->first();
        $doneCalc = Carbon::now();
        
        // get time of tasks which were marked done and approved during the period
        $approved = $this->_person->tasks()->done($this->_from, $this->_to)->approved($this->_from, $this->_to)->select([DB::raw('SUM(`estimated`) as estimated'), DB::raw('SUM(`needed`) as needed')])->groupBy('person_id')->first();
        $approvedCalc = Carbon::now();

        /*
         * calculate and save KPI values
         */
        // total number of due dates this period
        $this->singleKPI('due_total', $total, $totalCalc, 'number');
        
        // total number of missed due dates this period
        $this->singleKPI('due_missed_absolute', $missed, $missedCalc, 'number');
        
        // relative number of missed due dates this period
        $this->singleKPI('due_missed_relative', ($total > 0) ? ($missed / $total) * 100 : 0, $totalCalc, 'percentage');

        // save total estimated time
        $this->singleKPI('estimated_time_total', (isset($done->estimated) && !is_null($done->estimated)) ? $done->estimated : 0, $doneCalc, 'minutes');

        // save total needed time
        $this->singleKPI('needed_time_total', (isset($done->needed) && !is_null($done->needed)) ? $done->needed : 0, $doneCalc, 'minutes');

        // save approved estimated time
        $this->singleKPI('estimated_time_approved', (isset($approved->estimated) && !is_null($approved->estimated)) ? $approved->estimated : 0, $approvedCalc, 'minutes');

        // save approved estimated time
        $this->singleKPI('needed_time_approved', (isset($approved->needed) && !is_null($approved->needed)) ? $approved->needed : 0, $approvedCalc, 'minutes');
    }

    private function singleKPI($subtype, $value, $calc, $unit = 'number') {
        $kpi = $this->_person->KPIs()->where('type', 'tasks')->where('subtype', $subtype)->first();
        if(is_null($kpi)) {
            $kpi = $this->_person->KPIs()->create(['type' => 'tasks', 'subtype' => $subtype, 'unit' => $unit]);
        }
        $kpi->values()->create([
            'value' => $value,
            'calculated_at' => $calc,
            'date_id' => $this->_date->id
        ]);
    }
}
