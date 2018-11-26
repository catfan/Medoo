<?php

namespace Medoo\Db;


class Config
{
    const DEFAULT_CHARSET = 'utf8';
    const DEFAULT_COLLATION = 'utf8_general_ci';
    const DEFAULT_PORT = 3306;

    protected static $defaultConfig = [
        'database_name_group' => [
            'shards_count' => 100,
            'shards_type' => 'range', // <database|table>.<range|hash>
            'database_name' => 'database_name',
            'database_name_format' => '%s',
            'table_name_format' => '%s',
            'username' => 'username',
            'password' => 'password',
            'charset' => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix' => '',
            'option' => [],
            'servers' => [
                ['range' => [0, 29], 'server' => '127.0.0.1', 'port' => 3306],
                ['range' => [30,59], 'server' => '127.0.0.1', 'port' => 3306],
                ['range' => [60,99], 'server' => '127.0.0.1', 'port' => 3306],
            ],
        ],
    ];

    protected $groupConfigs = [];
    protected static $config;
    protected static $instance;

    protected function __construct(array $config)
    {
        $groupConfigs = [];
        foreach($config as $group => $groupConfig) {
            $groupConfigs[$group] = $this->parseGroupConfig($group, $groupConfig); 
        }
        $this->groupConfigs = $groupConfigs;
    }

    public static function initConfig(array $config)
    {
        self::$config = $config; 
        self::getInstance();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            if (self::$config === null) {
                throw new \Exception("Please first call " . __CLASS__ . "::initConfig() method");
            }
            self::$instance = new self(self::$config);
        } 
        return self::$instance;
    }

    public function getGroupConfig($group)
    {
        if (!isset($this->groupConfigs[$group])) {
            throw new \InvalidArgumentException("Can not find group config $group");
        }

        /*
        $config = $this->groupConfigs[$group];

        $servers = $config['servers'];
        $shardsCount = $config['shards_count'];
        $serverCount = $config['server_count'];
        $groupServers = $config['group_servers'];

        $shard = is_null($shard) ? 0 : (is_numeric($shard) ? $shard : crc32($shard));
        $shardIndex = $shard % $shardsCount;
        $serverIndex = $groupServers[$shardIndex];
        $serverConfig = array_merge($config, $servers[$serverIndex]);

        $serverConfig['database_name'] = sprintf($serverConfig['database_name'], $shardIndex + 1);
        $serverConfig['shard_index'] = $shardIndex;
        $serverConfig['server_index'] = $serverIndex;
        */

        return $this->groupConfigs[$group];
    }

    protected function parseGroupConfig($group, array $groupConfig)
    {
        $shardsCount = $groupConfig['shards_count'] ?? 1;
        $shardsType = $groupConfig['shards_type'] ?? '';
        $options = [
            'shards_count' => $shardsCount,
            'database_name' => $groupConfig['database_name'] ?? $group,
            'table_name' => $groupConfig['table_name'] ?? '%s',
            'username' => $groupConfig['username'],
            'password' => $groupConfig['password'],
            'charset' => $groupConfig['charset'] ?? self::DEFAULT_CHARSET,
            'collation' => $groupConfig['collation'] ?? self::DEFAULT_COLLATION,
            'prefix' => $groupConfig['prefix'] ?? '',
            'option' => $groupConfig['option'] ?? [],
            'table_name_format' => $groupConfig['table_name_format'] ?? '',
            'database_name_format' => $groupConfig['database_name_format'] ?? '',
        ];

        $servers = [];
        $groupServers = [];
        $isRangeShardsType = $shardsType === 'range';
        foreach($groupConfig['servers'] as $key => $server) {
            $servers[$key] = [
                'server' => $server['server'],
                'port' => $server['port'] ?? self::DEFAULT_PORT,
            ];
            if ($isRangeShardsType) {
                $range = $server['range'];
                for($i = $range[0]; $i <= $range[1]; $i ++) {
                    $groupServers[$i] = $key; 
                }
            }
        }
        $serverCount = count($servers);
        if ($shardsCount > 1 && !$isRangeShardsType) {
            for($i = 0; $i < $shardsCount; $i ++) {
                $groupServers[$i] = $i % $serverCount;
            }
        }
        $options['servers'] = $servers;
        $options['server_count'] = $serverCount;
        $options['group_servers'] = $groupServers;

        return $options;
    }
}
