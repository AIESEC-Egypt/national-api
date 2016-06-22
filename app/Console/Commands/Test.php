<?php
namespace App\Console\Commands;

use App\Entity;
use App\KPIvalue;
use App\KPIvalueDate;
use App\Term;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Just a command to test things';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //DB::enableQueryLog();
        $this->calcTeamKPIs(100);
        print_r(DB::getQueryLog());
    }

    private function seedKPIvalue($id, $count) {
        $date = Carbon::yesterday()->subWeeks($count);
        for($i = 0; $i < $count; $i++) {
            $d = KPIvalueDate::create(['date' => $date]);
            KPIvalue::create(['value' => rand(0, 100), 'calculated_at' => Carbon::now(), 'date_id' => $d->id, 'kpi_id' => $id]);
            $date->addWeek();
        }
    }
    
    private function calcEntityKPIs($count) {
        $from = Carbon::yesterday()->subWeek($count + 1);
        $to = Carbon::yesterday()->subWeek($count);
        
        for($i = 0; $i < $count; $i++) {
            $date = KPIvalueDate::where('date', '=', $to)->first();
            if(is_null($date)) {
                $date = KPIvalueDate::create(['date' => $to]);
            }

            $mc = Entity::where('id', '=', env('GIS_MC_ID'))->with('childs')->first();
            if(!is_null($mc)) {
                Queue::push(new \App\Jobs\KPIsEntities($mc, $from, $to, $date));
                foreach($mc->childs as $lc) {
                    Queue::push(new \App\Jobs\KPIsEntities($lc, $from, $to, $date));
                }
            }
            $from->addWeek();
            $to->addWeek();
        }
    }

    private function calcTeamKPIs($count) {
        $from = Carbon::yesterday()->subWeek($count + 1);
        $to = Carbon::yesterday()->subWeek($count);

        for($i = 0; $i < $count; $i++) {
            $date = KPIvalueDate::where('date', '=', $to)->first();
            if (is_null($date)) {
                $date = KPIvalueDate::create(['date' => $to]);
            }
            Term::timeframe($from, $to)->chunk(10, function ($terms) use ($from, $to, $date) {
                foreach ($terms as $term) {
                    $term->teams()->chunk(20, function ($teams) use ($from, $to, $date) {
                        foreach ($teams as $team) {
                            Queue::push(new \App\Jobs\KPIsTeams($team, $from, $to, $date));
                        }
                    });
                }
            });
            $from->addWeek();
            $to->addWeek();
        }
    }
}