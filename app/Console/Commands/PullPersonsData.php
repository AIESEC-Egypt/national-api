<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 22.05.16
 * Time: 21:06
 */

namespace App\Console\Commands;


use Illuminate\Console\Command;

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
        //
    }
}