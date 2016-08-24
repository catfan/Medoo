<?php

namespace Medoo\Foundation;
use Closure;

/**
 * @author Alireza Josheghani <josheghani.dev@gmail.com>
 * @version 1.0
 * @package Medoo Foundation | Table
 * */

class Table {

    /**
     * @param $table
     * @param Closure $callback
     * @return void
     */
    static public function create($table,Closure $callback)
    {
        $TablePrint = new TablePrint($table);
        $callback($TablePrint);
        $TablePrint->create();
    }

    /**
     * @param $tableName
     * @return void
     */
    static function drop($tableName)
    {
        $table = new TablePrint($tableName);
        $table->drop($tableName);
    }

}
