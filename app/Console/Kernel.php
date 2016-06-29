<?php

namespace App\Console;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\PullPersons',
        'App\Console\Commands\PullPersonsData',
        'App\Console\Commands\PullEntities',
        'App\Console\Commands\PullProgrammes',
        'App\Console\Commands\PullPositions',
        'App\Console\Commands\KPIsTasks',
        'App\Console\Commands\KPIsTeams',
        'App\Console\Commands\KPIsEntities'
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // KPIs
        $schedule->command('kpis:tasks')->weekly()->sundays();
        $schedule->command('kpis:teams')->weekly()->sundays();
        $schedule->command('kpis:entities')->weekly()->sundays();

        // persons pull
        $schedule->command('sync:pull:persons')->daily();
        $schedule->command('sync:pull:persons 50 \'' . Carbon::now()->subMinutes(5)->toDateTimeString() . '\'')->everyFiveMinutes();
        for($i = 0; $i < env('PULL_PERSONSDATA_WORKERS'); $i++) {
            $schedule->command('sync:pull:personsData ' . $i . ' ' . env('PULL_PERSONSDATA_WORKERS'))->everyMinute()->withoutOverlapping();
        }

        // pull programmes
        $schedule->command('sync:pull:programmes')->daily();

        // pull entities
        $schedule->command('sync:pull:entities')->everyTenMinutes();

        // pull positions
        $schedule->command('sync:pull:positions')->everyTenMinutes();
    }
}
