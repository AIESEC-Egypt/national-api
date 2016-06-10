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
    protected $signature = 'sync:pull:persons';

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
     *
     * @param int $perPage
     * @param string|null $registeredFrom
     * @return mixed
     */
    public function handle($perPage = 200, $registeredFrom = null)
    {
        // get a GIS Wrapper instance
        $gis = App::make('GIS')->getInstance();
        
        // increase number of people per page
        $gis->people->setPerPage($perPage);

        // set registered_from filter if isset
        if($registeredFrom !== null) {
            $gis->people->filters->registered->from = new \DateTime($registeredFrom);
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