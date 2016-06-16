<?php
namespace App\Console\Commands;

use App\Entity;
use App\Person;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        DB::enableQueryLog();
        $person = Person::has('positions.parent.person')->get();
        for($i = 0; $i < 5 && $i < count($person); $i++) {
            print_r($person[$i]->parentPositionsRecursiveAsFlatCollection(true)->toArray());
        }
        print_r(DB::getQueryLog());
    }
}