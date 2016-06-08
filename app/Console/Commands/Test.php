<?php
namespace App\Console\Commands;

use App\Person;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
        $person = Person::find(43133);
        DB::connection()->enableQueryLog();
        echo "positions";
        print_r($person->positions->toArray());
        echo "postionsMember";
        print_r($person->positionsMember->toArray());
        echo "positionsLeader";
        print_r($person->positionsLeader->toArray());
        echo "positionsTeamLeader";
        print_r($person->positionsTeamLeader->toArray());
        echo "positionsLeaderNonEB";
        print_r($person->positionsLeaderNonEB->toArray());
        print_r(DB::getQueryLog());
    }
}