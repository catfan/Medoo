<?php

namespace Medoo\Bootstrap;

/**
 * @author Alireza Josheghani <josheghani.dev@gmail.com>
 * @version 1.0
 * @package Medoo Bootstrap | Console
 * */

class Console extends BaseConsole {

    /**
     * @return void
     * Define all medoo commands
     */
    public function defineCommands()
    {
        $this->commands([
            \Medoo\Console\RunMigrations::class,
            \Medoo\Console\RunSeeds::class,
            \Medoo\Console\Migration::class,
            \Medoo\Console\Seeder::class,
            \Medoo\Console\TestConnection::class,
            \Medoo\Console\ResetMigrations::class,
            \Medoo\Console\RollBackMigrations::class,
        ]);
    }

    /**
     * @return void
     * Register all commands
     */
    public function fire()
    {
        $this->defineCommands();
        $this->run();
    }
}