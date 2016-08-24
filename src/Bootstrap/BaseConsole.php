<?php

namespace Medoo\Bootstrap;
use Symfony\Component\Console\Application;

/**
 * @author Alireza Josheghani <josheghani.dev@gmail.com>
 * @version 1.0
 * @package Medoo Bootstrap | BaseConsole
 * */

class BaseConsole {
    protected $app;
    public function __construct()
    {
        $this->app = new Application('Medoo console component',0.1);
    }
    public function commands(array $commands)
    {
        $i = 0;
        while($i <= count($commands) - 1){
            $this->app->add(new $commands[$i++]());
        }
    }
    public function run()
    {
        $this->app->run();
    }
}