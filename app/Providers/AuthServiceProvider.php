<?php

namespace App\Providers;

use App\Person;
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
        $this->app['auth']->viaRequest('api', function ($request) {
            if ($request->input('access_token')) {
                $id = Cache::get('access_token:' . $request->input('access_token'));
                if($id !== null) {
                    return Person::find($id);
                } else {
                    $person = json_decode(file_get_contents(env('CURRENT_PERSON_URL') . '?access_token=' . $request->input('access_token')));
                    if($person != null) {
                        Cache::put('access_token:' . $request->input('access_token'), intval($person->person->id), Carbon::parse($person->expires_at));
                        return Person::find(intval($person->person->id));
                    } else {
                        return null;
                    }
                }
            } else {
                return null;
            }
        });
    }
}
