<?php

namespace App\Console;

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
        'App\Console\Commands\Test'
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('kpis:tasks')->sundays()->daily();
    }
}
