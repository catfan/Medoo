<?php

namespace Medoo\Db;

use Medoo\MysqlMedoo;

class Connection
{
    protected $config;

    protected $groupConfig;
    protected $servers;
    protected $groupServers;

    protected $readerServers;
    protected $readerGroupServers;

    protected $instances;

    public function __construct($group)
    {
        $this->config = Config::getInstance();

        $this->groupConfig = $this->config->getGroupConfig($group);
        $this->servers = $this->groupConfig['servers'];
        $this->groupServers = $this->groupConfig['group_servers'];

        $this->readerServers = $this->groupConfig['reader_servers'] ?? null;
        $this->readerGroupServers = $this->groupConfig['reader_group_servers'] ?? null;
    }

    public function getShard($shardKey = null)
    {
        if ($shardKey === null) {
            return 0;
        }
        $shardKey = abs(is_numeric($shardKey) ? $shardKey : crc32($shardKey));
        return ($shardKey % $this->groupConfig['shards_count']);
    }

    public function getTable($table, $shardKey = null)
    {
        $tableNameFormat = $this->groupConfig['table_name_format'] ?? '';
        if ($tableNameFormat !== '') {
            $shard = $this->getShard($shardKey); 
            $table = sprintf($tableNameFormat, $table, $shard + 1);
        }
        return $table;
    }

    public function connect($shardKey = null, $isWriter = null)
    {
        $shardIndex = $this->getShard($shardKey);
        $serverIndex = $isWriter === false && $this->readerGroupServers ? 
            $this->readerGroupServers[$shardIndex] : $this->groupServers[$shardIndex];
        $serverConfig = array_merge($this->groupConfig, 
            $isWriter === false && $this->readerServers ? 
                $this->readerServers[$serverIndex] : $this->servers[$serverIndex]);

        $databaseName = $serverConfig['database_name'];
        $databaseNameFormat = $serverConfig['database_name_format'] ?? '';
        if ($databaseNameFormat !== '') {
            $databaseName = sprintf($databaseNameFormat, $databaseName, $shardIndex + 1); 
        }
        $key = $serverConfig['server'] . ':' . $serverConfig['port'] . ':' . $databaseName;

        if (!isset($this->instances[$key])) {
            $this->instances[$key] = new MysqlMedoo($serverConfig);
        }

        return $this->instances[$key];
    }
}
