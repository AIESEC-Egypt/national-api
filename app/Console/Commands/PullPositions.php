<?php
namespace App\Console\Commands;


use App\Position;
use App\Team;
use App\Person;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class PullPositions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:pull:positions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull Sync Positions from the GIS';

    /**
     * @var \GISwrapper\GIS
     */
    private $_gis;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_gis = App::make('GIS')->getInstance();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // iterate through all teams in chunks of 50 teams
        Team::chunk(50, function ($teams) {
            foreach($teams as $team) {
                // get positions from GIS (all by one request, because it's not an paged endpoint. We have to iterate through the result)
                $positions = $this->_gis->teams[$team->id]->positions->get();

                // iterate through the result
                foreach($positions->data as $remote) {
                    // try to find position in national database
                    $national = Position::where('id', $remote->id)->first();

                    // create if not exists
                    if($national == null) {
                        $national = new Position();
                        $national->id = $remote->id;
                    }

                    // set / update scalar  values
                    $national->position_name = $remote->position_name;
                    $national->position_short_name = $remote->position_short_name;
                    $national->start_date = Carbon::parse($remote->start_date);
                    $national->end_date = Carbon::parse($remote->end_date);
                    $national->team_id = $team->_internal_id;

                    // set person
                    if(isset($remote->person->id)) {
                        $person = Person::where('id', $remote->person->id)->first();
                        if($person != null) {
                            $national->person_id = $person->_internal_id;
                        }
                    }

                    // set parent
                    if(isset($remote->parent->id)) {
                        $parent = Position::where('id', $remote->parent->id)->first();
                        if($parent != null) {
                            $national->parent_id = $parent->_internal_id;
                        }
                    }

                    // save national object
                    $national->save();
                }
            }
        });
    }
}