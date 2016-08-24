<?php

namespace Medoo\Foundation;

/**
 * @author Alireza Josheghani <josheghani.dev@gmail.com>
 * @version 1.0
 * @package Medoo Foundation | Seeder
 * */

class Seeder {

    /**
     * Run the database seeder
     * @param $class
     */
    public function call($class)
    {
        $instance = new $class;
        return $instance->run();
    }

    /**
     * define the database seeder
     * @param $table
     * @param $limit
     * @param \Closure $callback
     * @return void
     */
    public function define($table, $limit, \Closure $callback)
    {
        Factory::define($table,$limit,$callback);
    }

}
