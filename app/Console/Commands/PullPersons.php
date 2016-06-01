<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 22.05.16
 * Time: 21:06
 */

namespace App\Console\Commands;


use Illuminate\Console\Command;

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
    protected $description = 'Pulling Persons from the GIS';

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