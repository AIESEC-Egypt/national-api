<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 22.05.16
 * Time: 21:06
 */

namespace App\Console\Commands;


use App\Person;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PullPersonsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:pull:personsData';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull Sync the data of all Persons in the national database from the GIS';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @param int $id worker id
     * @param int $total number of workers
     */
    public function handle(integer $id = null, integer $total = null)
    {
        // prepare arguments
        if(is_null($id)) $id = 0;
        if(is_null($total)) $total = 1;
        
        // get GIS instance
        $gis = App::make('gis')->getInstance();

        // get the persons of this worker in chunks
        Person::where(DB::raw('MOD(`id`, ' . intval($total) . ')'), '=', $id)->chunk(50, function($persons) use ($gis) {
            // iterate through persons of this chunk
            foreach($persons as $person) {
                // get data from GIS
                $res = $gis->people[$person->id]->get();

                // update person
                $person->updateFromGIS($res);
            }
        });
    }
}