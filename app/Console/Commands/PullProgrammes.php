<?php
namespace App\Console\Commands;


use App\Person;
use App\Programme;
use App\Entity;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class PullProgrammes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:pull:programmes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull Sync Programmes from the GIS';

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
     * @return mixed
     */
    public function handle()
    {
        // get a GIS Wrapper instance
        $gis = App::make('GIS')->getInstance();

        // unpaged endpoint, thereby iterating through the GET response arrray
        foreach($gis->programmes->get() as $p) {
            // search for programme in the national database
            $programme = Programme::find($p->id);   // programmes don't have an _internal_id

            // create it if it does not exist
            if($programme == null) {
                $programme = new Programme();
                $programme->id = $p->id;
            }

            // set values
            $programme->short_name = $p->short_name;
            $programme->consumer_name = $p->consumer_name;
            $programme->description = $p->description;
            $programme->color = $p->color;

            // save to national database
            $programme->save();
        }
    }
}