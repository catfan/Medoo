<?php

namespace Medoo\Db;

class Table
{
    protected $database;
    protected $table;
    protected $primary;

    protected $instance;
    protected $lastConnection;

    public function __construct($table = null, $database = null, $primary = null)
    {
        $this->table = $table ?? $this->table;
        $this->database = $database ?? $this->database;
        $this->primary = $primary ?? $this->primary;
        if ($this->database == null || $this->table == null) {
            throw new \Exception("Please first set database and table for " . static::class); 
        }
        if ($this->primary == null) {
            throw new \Exception("Please first set primary key for " . static::class); 
        }
        $this->primary = is_array($this->primary) ? $this->primary : [$this->primary];

        $this->instance = Database::getInstance($this->database);
    }

    public function find($id, $where = [])
    {
        $id = is_array($id) ? $id : [$id]; 
        if (count($id) != count($this->primary)) {
            throw new \Exception("id count is not same as primary key count");
        }
        $where += [
            'AND' => array_combine($this->primary, $id)
        ];
        return $this->get('*', $where);
    }

    public function findForUpdate($id)
    {
        return $this->find($id, ['LOCK' => 'UPDATE']); 
    }

    public function getTable($shardKey = null)
    {
        return $this->instance->getTable($this->table, $shardKey);
    }

    public function connect($shardKey = null, $isWriter = true)
    {
        $this->lastConnection =  $this->instance->connect($shardKey, $isWriter);
        return $this->lastConnection;
    }

    public function query($query, $map = [], $shardKey = null, $isWriter = null)
    {
        if ($isWriter === null) {
            $isWriter = strncasecmp(ltrim($query), 'select', 6) !== 0;
        }
        return $this->connect($shardKey, $isWriter)->query($query, $map); 
    }

    public function exec($query, $map = [], $shardKey = null, $isWriter = null)
    {
        if ($isWriter === null) {
            $isWriter = strncasecmp(ltrim($query), 'select', 6) !== 0;
        }
        return $this->connect($shardKey, $isWriter)->exec($query, $map); 
    }

    public function quote($string, $shardKey = null, $isWriter = null)
    {
        return $this->connect($shardKey, $isWriter)->quote($string);
    }

    public function action($actions, $shardKey = null)
    {
        return $this->connect($shardKey, $isWriter = true)->action($actions);
    }
    
    public function __call($method, $args)
    {
        $tableMethods = [
            'select' => 4,
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
            $isWriter = false;
            if (count($args) === $count + 1) {
                $isWriter = array_pop($args); 
            }
            $shardKey = count($args) === $count ? array_pop($args) : null;
            $connect = $this->connect($shardKey, $isWriter);
            array_unshift($args, $this->getTable($shardKey));
            return call_user_func_array([$connect, $method], $args);
        }

        $tableWriterMethods = [
            'insert' => 2,
            'update' => 3,
            'delete' => 2,
            'replace' => 3,
        ];
        if (isset($tableWriterMethods[$method])) {
            $count = $tableWriterMethods[$method];
            $isWriter = true;
            if (count($args) === $count + 1) {
                $isWriter = array_pop($args);
            }
            $shardKey = count($args) === $count ? array_pop($args) : null;
            $connect = $this->connect($shardKey, $isWriter);
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
            $isWriter = true;
            if (count($args) === $count + 1) {
                $isWriter = array_pop($args);
            }
            $shardKey = count($args) === $count ? array_pop($args) : null;
            $connect = $this->lastConnection ?? $this->connect($shardKey, $isWriter);
            return call_user_func_array([$connect, $method], $args);
        }

        throw new \BadMethodCallException("Not implement method " . static::class . "::$method()");
    }
}
