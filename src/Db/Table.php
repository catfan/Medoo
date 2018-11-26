<?php

namespace Medoo\Db;

class Table
{
    protected $database;
    protected $table;

    protected $instance;

    public function __construct($table = null, $database = null)
    {
        $this->table = $table ?? $this->table;
        $this->database = $database ?? $this->database;
        if ($this->database == null || $this->table == null) {
            throw new \Exception("Please first set database and table for " . static::class); 
        }

        $this->instance = Database::getInstance($this->database);
    }

    public function getTable($shardKey = null)
    {
        return $this->instance->getTable($this->table, $shardKey);
    }

    public function connect($shardKey = null)
    {
        return $this->instance->connect($shardKey);
    }

    public function query($query, $map = [], $shardKey = null)
    {
        return $this->connect($shardKey)->query($query, $map); 
    }

    public function exec($query, $map = [], $shardKey = null)
    {
        return $this->connect($shardKey)->exec($query, $map); 
    }

    public function quote($string, $shardKey = null)
    {
        return $this->connect($shardKey)->quote($string);
    }

    public function action($actions, $shardKey = null)
    {
        return $this->connect($shardKey)->action($actions);
    }
    
    public function __call($method, $args)
    {
        $tableMethods = [
            'select' => 4,
            'insert' => 2,
            'update' => 3,
            'delete' => 2,
            'replace' => 3,
            'get' => 4,
            'has' => 3, 
            'rand' => 4, 
            'count' => 4, 
            'avg' => 4, 
            'max' => 4, 
            'min' => 4, 
            'sum' => 4,
        ];
        if (isset($tableMethods[$method])) {
            $count = $tableMethods[$method];
            $shardKey = count($args) === $count ? array_pop($args) : null;
            $connect = $this->connect($shardKey);
            array_unshift($args, $this->getTable($shardKey));
            return call_user_func_array([$connect, $method], $args);
        }

        $normalMethods = [
            'id' => 1,
            'debug' => 1,
            'error' => 1,
            'last' => 1,
            'log' => 1,
            'info' => 1,
        ];
        if (isset($normalMethods[$method])) {
            $count = $normalMethods[$method];
            $shardKey = count($args) === $count ? array_pop($args) : null;
            $connect = $this->connect($shardKey);
            return call_user_func_array([$connect, $method], $args);
        }

        throw new \BadMethodCallException("Not implement method " . static::class . "::$method()");
    }
}
