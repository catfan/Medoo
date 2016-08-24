<?php

namespace Medoo\Foundation;
use medoo;

/**
 * @author Alireza Josheghani <josheghani.dev@gmail.com>
 * @version 1.0
 * @package Medoo Foundation | Database
 * */

class DataBase extends medoo {
    public static $where;
    public static $orWhere;
    private static $table;
    private static $table_name;
    private static $count;
    private static $limit;
    private static $orderBy;
    protected $connect;

    /**
     * DB constructor. Connect to the database
     * ------------------------------------------------------
     * please confing the env.yml file in project directory
     */
    public function __construct(){
        $this->connect = parent::connect([
            'database_type' => config('type','mysql'),
            'database_name' => config('name','medoo'),
            'server' => config('host','localhost'),
            'username' => config('user','rookt'),
            'password' => config('pass',''),
            'charset' => config('charset','utf8')
        ]);
    }

    /**
     * define table name
     * @param $table_name
     * @return DataBase
     */
    public static function table($table_name)
    {
        self::$table_name = $table_name;
        self::$table = new self;
        return self::$table;
    }

    /**
     * where clusure of table
     * @param null $where
     * @return DataBase
     * @throws \Exception
     */
    public function where($where = null) {
        if(!empty($where)){
            self::$where = $where;
            self::$table = new self;
            return self::$table;
        } else {
            throw new \Exception('The where array is empty !');
        }
    }

    /**
     * orwhere clusure of table
     * define when use of where multiple
     * @param null $where
     * @return DataBase
     * @throws \Exception
     */
    public function orWhere($where = null) {
        if(!empty($where)){
            self::$orWhere = $where;
            self::$table = new self;
            return self::$table;
        } else {
            throw new \Exception('The where array is empty !');
        }
    }

    /**
     * count of select records from databse
     * and set in the return array
     */
    public function count() {
        self::$count = true;
        self::$table = new self;
        return self::$table;
    }

    /**
     * define limit of records
     * @param $limit
     */
    public function limit($limit) {
        self::$limit = $limit;
        self::$table = new self;
        return self::$table;
    }

    /**
     * orderBy records from table
     * @param $order
     */
    public function orderBy($order) {
        self::$orderBy = $order;
        self::$table = new self;
        return self::$table;
    }

    /**
     * Get one column from table
     * @param string $column
     * @return array
     */
    public function get($column = '*') {
        $method = 'select';
        $where = [];
        if(!empty(self::$where)){
            array_push($where,[
                "AND" => self::$where
            ]);
        }
        if(!empty(self::$orWhere)){
            array_push($where,[
                "OR" => self::$orWhere
            ]);
        }
        if(!empty(self::$limit)) {
            array_push($where,[
                "LIMIT" => self::$limit
            ]);
        }
        if(!empty(self::$orderBy)) {
            array_push($where,[
                "ORDER" => self::$orderBy
            ]);
        }
        if(!empty($where)){
            $where = call_user_func_array('array_merge',$where);
        }
        $count = [
            'count' => parent::count(self::$table_name,$where)
        ];
        if(self::$count){
            return array_merge(
                parent::$method(self::$table_name,$column,$where),
                $count
            );
        } else {
            return parent::$method(self::$table_name,$column,$where);
        }
    }

    /**
     * get first record from table
     * @param string $column
     * @return mixed
     */
    public function first($column = '*') {
        return $this->connect->get(self::$table_name,$column,[
            'ORDER' => [
                "id" => "ASC",
            ]
        ]);
    }

    /**
     * get last record from table
     * @param string $column
     * @return mixed
     */
    public function last($column = '*') {
        return $this->connect->get(self::$table_name,$column,[
            'ORDER' => [
                "id" => "DESC",
            ]
        ]);
    }

    /**
     * get records with json
     * @param string $column
     * @return string
     */
    public function json($column = '*') {
        header('Content-Type: application/json;charset="UTF-8";');
        return json_encode($this->get($column));
    }
}
