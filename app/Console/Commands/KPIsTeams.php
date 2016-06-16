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

class KPIsTeams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kpis:teams';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch the calculation jobs for team related KPIs';

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
        $from = Carbon::yesterday()->subWeek();
        $to = Carbon::yesterday();

        $date = KPIvalueDate::where('date', '=', $to)->first();
        if(is_null($date)) {
            $date = KPIvalueDate::create(['date' => $to]);
        }

        Term::current()->chunk(10, function($terms) use ($from, $to, $date) {
            foreach($terms as $term) {
                $term->teams()->chunk(20, function($teams) use ($from, $to, $date) {
                    foreach($teams as $team) {
                        Queue::push(new \App\Jobs\KPIsTeams($team, $from, $to, $date));
                    }
                });
            }
        });
    }
}