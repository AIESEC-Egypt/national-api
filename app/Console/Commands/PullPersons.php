<?php
namespace App\Console\Commands;

use App\Person;
use App\Programme;
use App\Entity;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class PullPersons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:pull:persons {per_page?} {registered_from?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull new persons from the GIS (only those who are visible to the GIS user)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // get a GIS Wrapper instance
        $gis = App::make('GIS')->getInstance();

        // proceed per_page argument
        if($this->argument('per_page') !== null) $gis->people->setPerPage($this->argument('per_page'));

        // proceed registered_from argument
        if($this->argument('registered_from') !== null) {
            $gis->people->filters->registered->from = new \DateTime($this->argument('registered_from'));
        }

        // iterate through all persons visible to the GIS user
        foreach($gis->people as $remote) {
            // search for person in the national database
            $national = Person::where('id', $remote->id)->with(['programmes', 'managers'])->first();

            // create it if it does not exist, else skip it
            if($national == null) {
                $national = new Person();
                $national->id = $remote->id;
                $national->updateFromGIS($remote);
            }

        }
    }
}