<?php
namespace App\Console\Commands;


use App\Entity;
use App\KPIvalueDate;
use App\Person;
use App\Team;
use App\Term;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;

class KPIsEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kpis:entities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch the calculation jobs for entity related KPIs';

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
        $from = Carbon::today()->subWeek();
        $to = Carbon::today();

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
    }
}