<?php

namespace Medoo\Foundation;
use Medoo\Foundation\DataBase as DB;

/**
 * @author Alireza Josheghani <josheghani.dev@gmail.com>
 * @version 1.0
 * @package Medoo Foundation | Factory
 * */

class Factory {

    /**
     * @param $table
     * @param $limit
     * @param \Closure $callback
     */
    static public function define($table, $limit, \Closure $callback)
    {
        $i = 1;
        $results = [
            $table => []
        ];
        while ($i <= $limit){
            $facker = new Faker($limit);
            $FackerArray = $callback($facker);
            array_push($results[$table],$FackerArray);
            $i++;
        }
        $b = 0;
        $db = new DB();
        while($b <= $limit - 1){
            if($db->insert($table,[$results[$table][$b++]])){
                if($b == $limit - 1){
                    echo "\033[32mFactored " . $table . " !\033[30m\n";
                }
            } else {
                echo "\033[31mdo nothing " . $table . " !\033[30m\n";
            }
        }
    }
}