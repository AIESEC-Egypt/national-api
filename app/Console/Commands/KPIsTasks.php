<?php
namespace App\Console\Commands;


use App\Entity;
use App\Person;
use App\Team;
use App\Term;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;

class KPIsTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kpis:tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch the calculation jobs for tasks related KPIs';

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
        Person::has('tasks')->chunk(200, function($persons) use ($from, $to) {
            foreach($persons as $person) {
                // check if there were any activity in the last two weeks, either through an update or an due date, or if there is any due date in the future
                if($person->tasks()->where('updated_at', '>=', Carbon::yesterday()->subWeeks(2))->orWhere('due', '>=', Carbon::yesterday()->subWeeks(2))->count() > 0) {
                    Queue::push(new \App\Jobs\KPIsTasks($person, $from, $to));
                }
            }
        });
    }
}