<?php

namespace Medoo\Db;

class Database
{
    protected $connection;
    protected static $instances;

    public function __construct($group)
    {
        $this->connection = new Connection($group);
    }

    public function connect($shardKey = null, $isWriter = null)
    {
        return $this->connection->connect($shardKey, $isWriter);
    }

    public function getShard($shardKey = null)
    {
        return $this->connection->getShard($shardKey);
    }

    public function getTable($table, $shardKey = null)
    {
        return $this->connection->getTable($table, $shardKey);
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public static function getInstance($group)
    {
        if (!isset(self::$instances[$group])) {
            self::$instances[$group] = new self($group); 
        }
        return self::$instances[$group];
    }
}
