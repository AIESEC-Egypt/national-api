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
    protected $description = 'Pull Sync Persons from the GIS (only those who are visible to the GIS user)';

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
        
        // increase number of people per page
        $gis->people->setPerPage(200);

        // iterate through all persons visible to the GIS user
        foreach($gis->people as $remote) {
            // search for person in the national database
            $national = Person::where('id', $remote->id)->with(['programmes', 'managers'])->first();

            // create it if it does not exist
            if($national == null) {
                $national = new Person();
                $national->id = $remote->id;
            }

            // set / update scalar  values
            $scalarFields = ['email', 'first_name', 'middle_name', 'last_name', 'dob', 'interviewed', 'status', 'phone', 'location', 'nps_score'];
            foreach($scalarFields as $field) {
                if(isset($remote->$field)) {
                    $national->$field = $remote->$field;
                }
            }

            // set / update special field
            $national->is_employee = ($remote->is_employee) ? true : false;
            $national->profile_picture_url = $remote->profile_photo_url;
            $national->contacted_at = Carbon::parse($remote->contacted_at);
            if(isset($remote->cv_url->url)) $national->cv_url = $remote->cv_url->url;

            // update Object fields
            if(isset($remote->home_lc->id)) {
                $lc = Entity::where('id', $remote->home_lc->id)->first();
                if($lc != null) {
                    $national->home_entity = $lc->_internal_id;
                }
            }
            if(isset($remote->contacted_by->id)) {
                $contactPerson = Person::where('id', $remote->contacted_by->id)->first();
                if($contactPerson != null) {
                    $national->contacted_by = $contactPerson->_internal_id;
                }
            }

            // save to database
            $national->save();

            // sync programmes (programmes have no internal id, relationships are directly mapped via the gis id)
            $programmeIds = [];
            foreach($remote->programmes as $programme) {
                $programmeNational = Programme::find($programme->id);
                if($programmeNational != null) {
                    $programmeIds[] = $programme->id;
                }
            }
            $national->programmes()->sync($programmeIds);

            // sync managers
            $managerIds = [];
            foreach($remote->managers as $manager) {
                $managerNational = Person::where('id', $manager->id)->first();
                if($managerNational != null) {
                    $managerIds[] = $managerNational->_internal_id;
                }
            }
            $national->managers()->sync($managerIds);
        }
    }
}