<?php
namespace App\Console\Commands;


use App\Entity;
use App\Team;
use App\Term;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class PullEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:pull:entities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull Sync Offices and Terms from the GIS';

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
        // get MC office from the GIS
        $res = $this->_gis->committees[env('GIS_MC_ID')]->get();

        // handle MC
        $this->handleEntity($res, true);

        foreach($res->suboffices as $o) {
            $subres = $this->_gis->committees[$o->id]->get();
            $this->handleEntity($subres);
        }
    }

    private function handleEntity($res, $national = false) {
        // search for entity in the national database
        $entity = Entity::where('id', $res->id)->first();

        // create it if the entity does not exists
        if($entity == null) {
            $entity = new Entity();
            $entity->id = $res->id;
        }

        // set values
        $entity->name = $res->name;
        $entity->full_name = $res->full_name;
        $entity->email = $res->email;

        // save to database
        $entity->save();

        // iterate through terms
        foreach($res->terms as $t) {
            // search for term in the national database
            $term = Term::where('id', $t->id)->first();
            
            //create it if it does not exist
            if($term == null) {
                $term = new Term();
                $term->id = $t->id;
            }
            
            // set values
            $term->short_name = $t->short_name;
            $term->start_date = $t->start_date;
            $term->end_date = $t->end_date;
            $term->term_type = ($national) ? 'national' : 'local';
            $term->entity_id = $entity->_internal_id;

            // save to database
            $term->save();

            // handle teams
            $this->handleTeams($entity->id, $term->id, $term->_internal_id);
        }
    }

    private function handleTeams($eid, $tid, $itid) {
        $res = $this->_gis->committees[$eid]->terms[$tid]->get();

        foreach($res->teams as $t) {
            // search team in national database
            $team = Team::where('id', $t->id)->first();

            // create it if not found
            if($team == null) {
                $team = new Team();
                $team->id = $t->id;
            }

            // update values
            $team->title = $t->title;
            $team->team_type = $t->team_type;
            $team->subtitle = $t->subtitle;
            $team->term_id = $itid;

            // save to database
            $team->save();
        }
    }
}