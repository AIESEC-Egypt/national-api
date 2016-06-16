<?php

namespace App\Providers;

use App\Entity;
use App\KPI;
use App\Person;
use App\Policies\EntityPolicy;
use App\Policies\KPIPolicy;
use App\Policies\PersonPolicy;
use App\Policies\PositionPolicy;
use App\Policies\TeamPolicy;
use App\Position;
use App\Task;
use App\Policies\TaskPolicy;
use App\Team;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // register oAuth middleware
        $this->app['auth']->viaRequest('api', function ($request) {
            // check if we got an access token
            if ($request->input('access_token')) {
                
                // try to get the user id from cache
                $pid = Cache::get('access_token:' . $request->input('access_token'));

                // try to get the person
                $p = null;
                if($pid !== null) {
                    $p = Person::where('id', $pid)->first();
                }

                // if we don't have an id or an person
                if($pid === null || $p === null) {
                    // retrieve current person from OAuth Service
                    if (strpos(env('CURRENT_PERSON_URL'), '?') > 0) {
                        $person = json_decode(@file_get_contents(env('CURRENT_PERSON_URL') . '&access_token=' . $request->input('access_token')));
                    } else {
                        $person = json_decode(@file_get_contents(env('CURRENT_PERSON_URL') . '?access_token=' . $request->input('access_token')));
                    }

                    // if we didn't got it return null
                    if ($person === null) return null;

                    // if we didn't got the id from cache and try to get the person
                    if($pid === null) {
                        // put the id in the cache as long as token is valid
                        Cache::put('access_token:' . $request->input('access_token'), intval($person->person->id), Carbon::parse($person->expires_at));

                        // try to get the person
                        $p = Person::where('id', intval($person->person->id))->first();
                    }

                    // create person if it does not exists
                    if(is_null($p)) {
                        $p = new Person();
                        $p->id = intval($person->person->id);
                        $p->email = $person->person->email;
                        $p->first_name = $person->person->first_name;
                        $p->middle_name = ((isset($person->person->middle_name)) ? $person->person->middle_name : null);
                        $p->last_name = $person->person->last_name;
                        $p->dob =$person->person->dob ;
                        $p->interviewed = $person->person->interviewed;
                        $p->profile_picture_url = ((isset($person->person->profile_photo_url)) ? $person->person->profile_photo_url : null);
                        $p->save();
                    }
                }
                return $p;
            } else {
                return null;
            }
        });

        // defining Gates
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(Person::class, PersonPolicy::class);
        Gate::policy(KPI::class, KPIPolicy::class);
        Gate::policy(Entity::class, EntityPolicy::class);
        Gate::policy(Position::class, PositionPolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
    }
}
