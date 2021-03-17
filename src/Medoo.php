<?php
/**
 * Medoo database framework
 * 
 * Released under the MIT license
 * 
 * @version 1.7.10
 * @author Angel Lai
 * @package Medoo
 * @copyright Copyright 2021 Medoo Project, Angel Lai
 * @license https://opensource.org/licenses/MIT
 * @link https://medoo.in
 */
declare(strict_types=1);
namespace Medoo;

use PDO;
use Exception;
use PDOException;
use InvalidArgumentException;
use PDOStatement;

/**
 * The Medoo raw object
 */
class Raw
{
    /**
     * The array of mapping data for the raw string.
     *
     * @var array
     */
    public $map;

    /**
     * The raw string.
     *
     * @var string
     */
    public $value;
}

/**
 * @method array select(string $table, array $columns, array $where)
 * @method mixed get(string $table, array|string $columns, array $where)
 * @method bool has(string $table, array $where)
 * @method mixed rand(string $table, array|string $column, array $where)
 * @method int count(string $table, array $where)
 * @method int max(string $table, string $column)
 * @method int min(string $table, string $column)
 * @method int avg(string $table, string $column)
 * @method int sum(string $table, string $column)
 * @method int max(string $table, string $column, array $where)
 * @method int min(string $table, string $column, array $where)
 * @method int avg(string $table, string $column, array $where)
 * @method int sum(string $table, string $column, array $where)
 */
class Medoo
{
    /**
     * The PDO object.
     *
     * @var \PDO
     */
    public $pdo;

    /**
     * The type of database.
     *
     * @var string
     */
    protected $type;

    /**
     * Table prefix
     *
     * @var string
     */
    protected $prefix;

    /**
     * The PDO statement object.
     *
     * @var \PDOStatement
     */
    protected $statement;

    /**
     * The DSN connection string.
     *
     * @var string
     */
    protected $dsn;

    /**
     * The array of logs.
     *
     * @var array
     */
    protected $logs = [];

    /**
     * Determine should log or not.
     *
     * @var bool
     */
    protected $logging = false;

    /**
     * Determine is in debug mode.
     *
     * @var bool
     */
    protected $debugMode = false;

    /**
     * Determine should saving debug logging.
     *
     * @var bool
     */
    protected $debugLogging = false;

    /**
     * The array of logs for debug.
     *
     * @var array
     */
    protected $debugLogs = [];

    /**
     * The global unique id.
     *
     * @var integer
     */
    protected $guid = 0;

    /**
     * Error Message
     *
     * @var string|null
     */
    public $error = null;

    /**
     * The array of error information.
     *
     * @var array|null
     */
    public $errorInfo = null;

    /**
     * Connect database.
     * 
     * ```
     * $database = new Medoo([
     *      // required
     *      'database_type' => 'mysql',
     *      'database_name' => 'name',
     *      'server' => 'localhost',
     *      'username' => 'your_username',
     *      'password' => 'your_password',
     * 
     *      // [optional]
     *      'charset' => 'utf8mb4',
     *      'port' => 3306,
     * 
     *      // [optional] Table prefix
     *      'prefix' => 'PREFIX_'
     * ]);
     * ```
     * 
     * @param array $options Connection options
     * @return Medoo
     * @throws PDOException
     * @link https://medoo.in/api/new
     */

    public function __construct(array $options)
    {
        if (isset($options['database_type'])) {
            $this->type = strtolower($options['database_type']);

            if ($this->type === 'mariadb') {
                $this->type = 'mysql';
            }
        }

        if (isset($options['prefix'])) {
            $this->prefix = $options['prefix'];
        }

        if (isset($options['logging']) && is_bool($options['logging'])) {
            $this->logging = $options['logging'];
        }

        $option = $options['option'] ?? [];
        $commands = (isset($options['command']) && is_array($options['command'])) ? $options['command'] : [];

        switch ($this->type) {
            case 'mysql':
                // Make MySQL using standard quoted identifier
                $commands[] = 'SET SQL_MODE=ANSI_QUOTES';

                break;

            case 'mssql':
                // Keep MSSQL QUOTED_IDENTIFIER is ON for standard quoting
                $commands[] = 'SET QUOTED_IDENTIFIER ON';

                // Make ANSI_NULLS is ON for NULL value
                $commands[] = 'SET ANSI_NULLS ON';

                break;
        }

        if (isset($options['pdo'])) {
            if (!$options['pdo'] instanceof PDO) {
                throw new InvalidArgumentException('Invalid PDO object supplied.');
            }

            $this->pdo = $options['pdo'];

            foreach ($commands as $value) {
                $this->pdo->exec($value);
            }

            return;
        }

        if (isset($options['dsn'])) {
            if (is_array($options['dsn']) && isset($options['dsn']['driver'])) {
                $attr = $options['dsn'];
            } else {
                throw new InvalidArgumentException('Invalid DSN option supplied.');
            }
        } else {
            if (
                isset($options['port']) &&
                is_int($options['port'] * 1)
            ) {
                $port = $options['port'];
            }

            $isPort = isset($port);

            switch ($this->type) {
                case 'mysql':
                    $attr = [
                        'driver' => 'mysql',
                        'dbname' => $options['database_name']
                    ];

                    if (isset($options['socket'])) {
                        $attr['unix_socket'] = $options['socket'];
                    } else {
                        $attr['host'] = $options['server'];

                        if ($isPort) {
                            $attr['port'] = $port;
                        }
                    }

                    break;

                case 'pgsql':
                    $attr = [
                        'driver' => 'pgsql',
                        'host' => $options['server'],
                        'dbname' => $options['database_name']
                    ];

                    if ($isPort) {
                        $attr['port'] = $port;
                    }

                    break;

                case 'sybase':
                    $attr = [
                        'driver' => 'dblib',
                        'host' => $options['server'],
                        'dbname' => $options['database_name']
                    ];

                    if ($isPort) {
                        $attr['port'] = $port;
                    }

                    break;

                case 'oracle':
                    $attr = [
                        'driver' => 'oci',
                        'dbname' => $options['server'] ?
                            '//' . $options['server'] . ($isPort ? ':' . $port : ':1521') . '/' . $options['database_name'] :
                            $options['database_name']
                    ];

                    if (isset($options['charset'])) {
                        $attr['charset'] = $options['charset'];
                    }

                    break;

                case 'mssql':
                    if (isset($options['driver']) && $options['driver'] === 'dblib') {
                        $attr = [
                            'driver' => 'dblib',
                            'host' => $options['server'] . ($isPort ? ':' . $port : ''),
                            'dbname' => $options['database_name']
                        ];

                        if (isset($options['appname'])) {
                            $attr['appname'] = $options['appname'];
                        }

                        if (isset($options['charset'])) {
                            $attr['charset'] = $options['charset'];
                        }
                    } else {
                        $attr = [
                            'driver' => 'sqlsrv',
                            'Server' => $options['server'] . ($isPort ? ',' . $port : ''),
                            'Database' => $options['database_name']
                        ];

                        if (isset($options['appname'])) {
                            $attr['APP'] = $options['appname'];
                        }

                        $config = [
                            'ApplicationIntent',
                            'AttachDBFileName',
                            'Authentication',
                            'ColumnEncryption',
                            'ConnectionPooling',
                            'Encrypt',
                            'Failover_Partner',
                            'KeyStoreAuthentication',
                            'KeyStorePrincipalId',
                            'KeyStoreSecret',
                            'LoginTimeout',
                            'MultipleActiveResultSets',
                            'MultiSubnetFailover',
                            'Scrollable',
                            'TraceFile',
                            'TraceOn',
                            'TransactionIsolation',
                            'TransparentNetworkIPResolution',
                            'TrustServerCertificate',
                            'WSID',
                        ];

                        foreach ($config as $value) {
                            $keyname = strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $value));

                            if (isset($options[$keyname])) {
                                $attr[$value] = $options[$keyname];
                            }
                        }
                    }

                    break;

                case 'sqlite':
                    $attr = [
                        'driver' => 'sqlite',
                        $options['database_file']
                    ];

                    break;
            }
        }

        if (!isset($attr)) {
            throw new InvalidArgumentException('Incorrect connection options.');
        }

        $driver = $attr['driver'];

        if (!in_array($driver, PDO::getAvailableDrivers())) {
            throw new InvalidArgumentException("Unsupported PDO driver: {$driver}.");
        }

        unset($attr['driver']);

        $stack = [];

        foreach ($attr as $key => $value) {
            $stack[] = is_int($key) ? $value : $key . '=' . $value;
        }

        $dsn = $driver . ':' . implode(';', $stack);

        if (
            in_array($this->type, ['mysql', 'pgsql', 'sybase', 'mssql']) &&
            isset($options['charset'])
        ) {
            $commands[] = "SET NAMES '{$options['charset']}'" . (
                $this->type === 'mysql' && isset($options['collation']) ?
                " COLLATE '{$options['collation']}'" : ''
            );
        }

        $this->dsn = $dsn;

        try {
            $this->pdo = new PDO(
                $dsn,
                $options['username'] ?? null,
                $options['password'] ?? null,
                $option
            );

            $modeMap = [
                PDO::ERRMODE_SILENT,
                PDO::ERRMODE_WARNING,
                PDO::ERRMODE_EXCEPTION
            ];

            $errorMode = in_array($options['error'], $modeMap) ?
                $options['error'] :
                PDO::ERRMODE_SILENT;

            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, $errorMode);

            foreach ($commands as $value) {
                $this->pdo->exec($value);
            }
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    /**
     * Generate a new map key for placeholder.
     *
     * @return string
     */
    protected function mapKey() : string
    {
        return ':MeDoO_' . $this->guid++ . '_mEdOo';
    }

    /**
     * Execute customized raw statement.
     *
     * @param string $statement The raw SQL statement.
     * @param array $map The array of input parameters value for prepared statement.
     * @return \PDOStatement|null
     */
    public function query(string $statement, array $map = []) : ?PDOStatement
    {
        $raw = $this->raw($statement, $map);

        $statement = $this->buildRaw($raw, $map);

        return $this->exec($statement, $map);
    }

    /**
     * Execute the raw statement.
     *
     * @param string $statement The SQL statement.
     * @param array $map The array of input parameters value for prepared statement.
     * @return \PDOStatement|null
     */
    public function exec(string $statement, array $map = []) : ?PDOStatement
    {
        $this->statement = null;
        $this->errorInfo = null;
        $this->error = null;

        if ($this->debugMode) {

            if ($this->debugLogging) {
                $this->debugLogs[] = $this->generate($statement, $map);
                return null;
            }

            echo $this->generate($statement, $map);

            $this->debugMode = false;

            return null;
        }

        if ($this->logging) {
            $this->logs[] = [$statement, $map];
        } else {
            $this->logs = [[$statement, $map]];
        }

        $statement = $this->pdo->prepare($statement);

        $errorInfo = $this->pdo->errorInfo();

        if ($errorInfo[0] !== '00000') {
            $this->errorInfo = $errorInfo;
            $this->error = $errorInfo[2];

            return null;
        }

        foreach ($map as $key => $value) {
            $statement->bindValue($key, $value[0], $value[1]);
        }

        $execute = $statement->execute();

        $errorInfo = $statement->errorInfo();

        if ($errorInfo[0] !== '00000') {
            $this->errorInfo = $errorInfo;
            $this->error = $errorInfo[2];

            return null;
        }

        if ($execute) {
            $this->statement = $statement;
        }

        return $statement;
    }

    /**
     * Generate readable statement.
     *
     * @param string $statement
     * @param array $map
     * @return string
     */
    protected function generate(string $statement, array $map) : string
    {
        $identifier = [
            'mysql' => '`$1`',
            'mssql' => '[$1]'
        ];

        $statement = preg_replace(
            '/"((?![_\d])[\p{N}\p{L}_]+)"/u',
            $identifier[$this->type] ?? '"$1"',
            $statement
        );

        foreach ($map as $key => $value) {
            if ($value[1] === PDO::PARAM_STR) {
                $replace = $this->quote($value[0]);
            } elseif ($value[1] === PDO::PARAM_NULL) {
                $replace = 'NULL';
            } elseif ($value[1] === PDO::PARAM_LOB) {
                $replace = '{LOB_DATA}';
            } else {
                $replace = $value[0];
            }

            $statement = str_replace($key, $replace, $statement);
        }

        return $statement;
    }

    /**
     * Build a raw object.
     *
     * @param string $string The raw string.
     * @param array $map The array of mapping data for the raw string.
     * @return Medoo::raw
     */
    public static function raw(string $string, array $map = []) : Raw
    {
        $raw = new Raw();

        $raw->map = $map;
        $raw->value = $string;

        return $raw;
    }

    /**
     * Finds whether the object is raw.
     *
     * @param object $object
     * @return bool
     */
    protected function isRaw($object) : bool
    {
        return $object instanceof Raw;
    }

    /**
     * Generate the actual query from raw object.
     *
     * @param mixed $raw
     * @param array $map
     * @return string|null
     */
    protected function buildRaw($raw, array &$map) : ?string
    {
        if (!$this->isRaw($raw)) {
            return null;
        }

        $query = preg_replace_callback(
            '/(([`\']).*?)?((FROM|TABLE|INTO|UPDATE|JOIN)\s*)?\<(((?![_\d])[\p{N}\p{L}_]+)(\.(?![_\d])[\p{N}\p{L}_]+)?)\>(.*?\2)?/u',
            function ($matches) {
                if (!empty($matches[2]) && isset($matches[8])) {
                    return $matches[0];
                }

                if (!empty($matches[4])) {
                    return $matches[1] . $matches[4] . ' ' . $this->tableQuote($matches[5]);
                }

                return $matches[1] . $this->columnQuote($matches[5]);
            },
            $raw->value
        );

        $rawMap = $raw->map;

        if (!empty($rawMap)) {
            foreach ($rawMap as $key => $value) {
                $map[$key] = $this->typeMap($value, gettype($value));
            }
        }

        return $query;
    }

    /**
     * Quote a string for use in a query.
     *
     * @param string $string
     * @return string
     */
    public function quote(string $string) : string
    {
        return $this->pdo->quote($string);
    }

    /**
     * Quote table name for use in a query.
     *
     * @param string $table
     * @return string
     */
    protected function tableQuote(string $table) : string
    {
        if (preg_match('/(?![_\d])[\p{N}\p{L}_]+/u', $table)) {
            return '"' . $this->prefix . $table . '"';
        }

        throw new InvalidArgumentException("Incorrect table name: {$table}.");
    }

    /**
     * Quote column name for use in a query.
     *
     * @param string $column
     * @return string
     */
    protected function columnQuote(string $column) : string
    {
        if (preg_match('/^(?![_\d])[\p{N}\p{L}_]+(\.?(?![_\d])[\p{N}\p{L}_]+)?$/u', $column)) {
            return strpos($column, '.') !== false ?
                '"' . $this->prefix . str_replace('.', '"."', $column) . '"' :
                '"' . $column . '"';
        }

        throw new InvalidArgumentException("Incorrect column name: {$column}.");
    }

    /**
     * Quote array for use in a query.
     *
     * @param array $array
     * @return string
     */
    protected function arrayQuote(array $array) : string
    {
        $stack = [];

        foreach ($array as $value) {
            $stack[] = is_int($value) ? $value : $this->pdo->quote($value);
        }

        return implode(',', $stack);
    }

    /**
     * Mapping the type name as PDO data type.
     *
     * @param mixed $value
     * @param string $type
     * @return array
     */
    protected function typeMap($value, string $type) : array
    {
        $map = [
            'NULL' => PDO::PARAM_NULL,
            'integer' => PDO::PARAM_INT,
            'double' => PDO::PARAM_STR,
            'boolean' => PDO::PARAM_BOOL,
            'string' => PDO::PARAM_STR,
            'object' => PDO::PARAM_STR,
            'resource' => PDO::PARAM_LOB
        ];

        if ($type === 'boolean') {
            $value = ($value ? '1' : '0');
        } elseif ($type === 'NULL') {
            $value = null;
        }

        return [$value, $map[$type]];
    }

    /**
     * Build the statement part for the column stack.
     *
     * @param array|string $columns
     * @param array $map
     * @param bool $root
     * @param bool $isJoin
     * @return string
     */
    protected function columnPush(&$columns, array &$map, bool $root, bool $isJoin = false) : string
    {
        if ($columns === '*') {
            return $columns;
        }

        $stack = [];

        if (is_string($columns)) {
            $columns = [$columns];
        }

        foreach ($columns as $key => $value) {
            if (!is_int($key) && is_array($value) && $root && count(array_keys($columns)) === 1) {
                $stack[] = $this->columnQuote($key);

                $stack[] = $this->columnPush($value, $map, false, $isJoin);
            } elseif (is_array($value)) {
                $stack[] = $this->columnPush($value, $map, false, $isJoin);
            } elseif (!is_int($key) && $raw = $this->buildRaw($value, $map)) {
                preg_match('/(?<column>[\p{N}\p{L}_\.]+)(\s*\[(?<type>(String|Bool|Int|Number))\])?/u', $key, $match);

                $stack[] = "{$raw} AS {$this->columnQuote($match['column'])}";
            } elseif (is_int($key) && is_string($value)) {
                if ($isJoin && strpos($value, '*') !== false) {
                    throw new InvalidArgumentException('Cannot use table.* to select all columns while joining table.');
                }

                preg_match('/(?<column>[^(]+)(?:\s*\((?<alias>.+)\))?(?:\s*\[(?<type>(?:String|Bool|Int|Number|Object|JSON))\])?/u', $value, $match);

                if (!empty($match['alias'])) {
                    $stack[] = "{$this->columnQuote($match['column'])} AS {$this->columnQuote($match['alias'])}";

                    $columns[$key] = $match['alias'];

                    if (!empty($match['type'])) {
                        $columns[$key] .= ' [' . $match['type'] . ']';
                    }
                } else {
                    $stack[] = $this->columnQuote($match['column']);
                }
            }
        }

        return implode(',', $stack);
    }

    /**
     * Conjunct the inner relationship.
     *
     * @param array $data
     * @param array $map
     * @param string $conjunctor
     * @param string $outerConjunctor
     * @return string
     */
    protected function innerConjunct(array $data, array $map, string $conjunctor, string $outerConjunctor) : string
    {
        $stack = [];

        foreach ($data as $value) {
            $stack[] = '(' . $this->dataImplode($value, $map, $conjunctor) . ')';
        }

        return implode($outerConjunctor . ' ', $stack);
    }

    /**
     * Implode where conditions.
     *
     * @param array $data
     * @param array $map
     * @param string $conjunctor
     * @return string
     */
    protected function dataImplode(array $data, array &$map, string $conjunctor) : string
    {
        $stack = [];

        foreach ($data as $key => $value) {
            $type = gettype($value);

            if (
                $type === 'array' &&
                preg_match("/^(AND|OR)(\s+#.*)?$/", $key, $relationMatch)
            ) {
                $relationship = $relationMatch[1];

                $stack[] = $value !== array_keys(array_keys($value)) ?
                    '(' . $this->dataImplode($value, $map, ' ' . $relationship) . ')' :
                    '(' . $this->innerConjunct($value, $map, ' ' . $relationship, $conjunctor) . ')';

                continue;
            }

            $mapKey = $this->mapKey();

            if (
                is_int($key) &&
                preg_match('/([\p{N}\p{L}_\.]+)\[(?<operator>\>\=?|\<\=?|\!?\=)\]([\p{N}\p{L}_\.]+)/u', $value, $match)
            ) {
                $stack[] = $this->columnQuote($match[1]) . ' ' . $match['operator'] . ' ' . $this->columnQuote($match[3]);
            } else {
                preg_match('/([\p{N}\p{L}_\.]+)(\[(?<operator>\>\=?|\<\=?|\!|\<\>|\>\<|\!?~|REGEXP)\])?/u', $key, $match);
                $column = $this->columnQuote($match[1]);

                if (isset($match['operator'])) {
                    $operator = $match['operator'];

                    if (in_array($operator, ['>', '>=', '<', '<='])) {
                        $condition = "{$column} {$operator} ";

                        if (is_numeric($value)) {
                            $condition .= $mapKey;
                            $map[$mapKey] = [$value, is_float($value) ? PDO::PARAM_STR : PDO::PARAM_INT];
                        } elseif ($raw = $this->buildRaw($value, $map)) {
                            $condition .= $raw;
                        } else {
                            $condition .= $mapKey;
                            $map[$mapKey] = [$value, PDO::PARAM_STR];
                        }

                        $stack[] = $condition;
                    } elseif ($operator === '!') {
                        switch ($type) {
                            case 'NULL':
                                $stack[] = $column . ' IS NOT NULL';
                                break;

                            case 'array':
                                $placeholders = [];

                                foreach ($value as $index => $item) {
                                    $stackKey = $mapKey . $index . '_i';

                                    $placeholders[] = $stackKey;
                                    $map[$stackKey] = $this->typeMap($item, gettype($item));
                                }

                                $stack[] = $column . ' NOT IN (' . implode(', ', $placeholders) . ')';
                                break;

                            case 'object':
                                if ($raw = $this->buildRaw($value, $map)) {
                                    $stack[] = "{$column} != {$raw}";
                                }
                                break;

                            case 'integer':
                            case 'double':
                            case 'boolean':
                            case 'string':
                                $stack[] = "{$column} != {$mapKey}";
                                $map[$mapKey] = $this->typeMap($value, $type);
                                break;
                        }
                    } elseif ($operator === '~' || $operator === '!~') {
                        if ($type !== 'array') {
                            $value = [$value];
                        }

                        $connector = ' OR ';
                        $data = array_values($value);

                        if (is_array($data[0])) {
                            if (isset($value['AND']) || isset($value['OR'])) {
                                $connector = ' ' . array_keys($value)[0] . ' ';
                                $value = $data[0];
                            }
                        }

                        $likeClauses = [];

                        foreach ($value as $index => $item) {
                            $item = strval($item);

                            if (!preg_match('/(\[.+\]|[\*\?\!\%\-#^_]|%.+|.+%)/', $item)) {
                                $item = '%' . $item . '%';
                            }

                            $likeClauses[] = $column . ($operator === '!~' ? ' NOT' : '') . " LIKE {$mapKey}L{$index}";
                            $map["{$mapKey}L{$index}"] = [$item, PDO::PARAM_STR];
                        }

                        $stack[] = '(' . implode($connector, $likeClauses) . ')';
                    } elseif ($operator === '<>' || $operator === '><') {
                        if ($type === 'array') {
                            if ($operator === '><') {
                                $column .= ' NOT';
                            }

                            $stack[] = "({$column} BETWEEN {$mapKey}a AND {$mapKey}b)";

                            $dataType = (is_numeric($value[0]) && is_numeric($value[1])) ? PDO::PARAM_INT : PDO::PARAM_STR;

                            $map[$mapKey . 'a'] = [$value[0], $dataType];
                            $map[$mapKey . 'b'] = [$value[1], $dataType];
                        }
                    } elseif ($operator === 'REGEXP') {
                        $stack[] = "{$column} REGEXP {$mapKey}";
                        $map[$mapKey] = [$value, PDO::PARAM_STR];
                    }
                } else {
                    switch ($type) {
                        case 'NULL':
                            $stack[] = $column . ' IS NULL';
                            break;

                        case 'array':
                            $placeholders = [];

                            foreach ($value as $index => $item) {
                                $stackKey = $mapKey . $index . '_i';

                                $placeholders[] = $stackKey;
                                $map[$stackKey] = $this->typeMap($item, gettype($item));
                            }

                            $stack[] = $column . ' IN (' . implode(', ', $placeholders) . ')';
                            break;

                        case 'object':
                            if ($raw = $this->buildRaw($value, $map)) {
                                $stack[] = "{$column} = {$raw}";
                            }
                            break;

                        case 'integer':
                        case 'double':
                        case 'boolean':
                        case 'string':
                            $stack[] = "{$column} = {$mapKey}";
                            $map[$mapKey] = $this->typeMap($value, $type);
                            break;
                    }
                }
            }
        }

        return implode($conjunctor . ' ', $stack);
    }

    /**
     * Build the where clause.
     *
     * @param array|null $where
     * @param array $map
     * @return string
     */
    protected function whereClause($where, array &$map) : string
    {
        $whereClause = '';

        if (is_array($where)) {
            $conditions = array_diff_key($where, array_flip(
                ['GROUP', 'ORDER', 'HAVING', 'LIMIT', 'LIKE', 'MATCH']
            ));

            if (!empty($conditions)) {
                $whereClause = ' WHERE ' . $this->dataImplode($conditions, $map, ' AND');
            }

            if (isset($where['MATCH']) && $this->type === 'mysql') {
                $match = $where['MATCH'];

                if (is_array($match) && isset($match['columns'], $match['keyword'])) {
                    $mode = '';

                    $modeMap = [
                        'natural' => 'IN NATURAL LANGUAGE MODE',
                        'natural+query' => 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION',
                        'boolean' => 'IN BOOLEAN MODE',
                        'query' => 'WITH QUERY EXPANSION'
                    ];

                    if (isset($match['mode'], $modeMap[$match['mode']])) {
                        $mode = ' ' . $modeMap[$match['mode']];
                    }

                    $columns = implode(', ', array_map([$this, 'columnQuote'], $match['columns']));
                    $mapKey = $this->mapKey();
                    $map[$mapKey] = [$match['keyword'], PDO::PARAM_STR];

                    $whereClause .= ($whereClause !== '' ? ' AND ' : ' WHERE') . ' MATCH (' . $columns . ') AGAINST (' . $mapKey . $mode . ')';
                }
            }

            if (isset($where['GROUP'])) {
                $group = $where['GROUP'];

                if (is_array($group)) {
                    $stack = [];

                    foreach ($group as $column => $value) {
                        $stack[] = $this->columnQuote($value);
                    }

                    $whereClause .= ' GROUP BY ' . implode(',', $stack);
                } elseif ($raw = $this->buildRaw($group, $map)) {
                    $whereClause .= ' GROUP BY ' . $raw;
                } else {
                    $whereClause .= ' GROUP BY ' . $this->columnQuote($group);
                }

                if (isset($where['HAVING'])) {
                    if ($raw = $this->buildRaw($where['HAVING'], $map)) {
                        $whereClause .= ' HAVING ' . $raw;
                    } else {
                        $whereClause .= ' HAVING ' . $this->dataImplode($where['HAVING'], $map, ' AND');
                    }
                }
            }

            if (isset($where['ORDER'])) {
                $order = $where['ORDER'];

                if (is_array($order)) {
                    $stack = [];

                    foreach ($order as $column => $value) {
                        if (is_array($value)) {
                            $stack[] = "FIELD({$this->columnQuote($column)}, {$this->arrayQuote($value)})";
                        } elseif ($value === 'ASC' || $value === 'DESC') {
                            $stack[] = $this->columnQuote($column) . ' ' . $value;
                        } elseif (is_int($column)) {
                            $stack[] = $this->columnQuote($value);
                        }
                    }

                    $whereClause .= ' ORDER BY ' . implode(',', $stack);
                } elseif ($raw = $this->buildRaw($order, $map)) {
                    $whereClause .= ' ORDER BY ' . $raw;
                } else {
                    $whereClause .= ' ORDER BY ' . $this->columnQuote($order);
                }

                if (
                    isset($where['LIMIT']) &&
                    in_array($this->type, ['oracle', 'mssql'])
                ) {
                    $limit = $where['LIMIT'];

                    if (is_numeric($limit)) {
                        $limit = [0, $limit];
                    }
                    
                    if (
                        is_array($limit) &&
                        is_numeric($limit[0]) &&
                        is_numeric($limit[1])
                    ) {
                        $whereClause .= " OFFSET {$limit[0]} ROWS FETCH NEXT {$limit[1]} ROWS ONLY";
                    }
                }
            }

            if (isset($where['LIMIT']) && !in_array($this->type, ['oracle', 'mssql'])) {
                $limit = $where['LIMIT'];

                if (is_numeric($limit)) {
                    $whereClause .= ' LIMIT ' . $limit;
                } elseif (
                    is_array($limit) &&
                    is_numeric($limit[0]) &&
                    is_numeric($limit[1])
                ) {
                    $whereClause .= " LIMIT {$limit[1]} OFFSET {$limit[0]}";
                }
            }
        } elseif ($raw = $this->buildRaw($where, $map)) {
            $whereClause .= ' ' . $raw;
        }

        return $whereClause;
    }

    /**
     * Build statement for the select query.
     *
     * @param string $table
     * @param array $map
     * @param array|string $join
     * @param array|string $columns
     * @param array $where
     * @param string $columnFn
     * @return string
     */
    protected function selectContext(
        string $table,
        array &$map,
        $join,
        &$columns = null,
        array $where = null,
        $columnFn = null
    ) : string
    {
        preg_match('/(?<table>(?![_\d])[\p{N}\p{L}_]+)\s*\((?<alias>(?![_\d])[\p{N}\p{L}_]+)\)/u', $table, $tableMatch);

        if (isset($tableMatch['table'], $tableMatch['alias'])) {
            $table = $this->tableQuote($tableMatch['table']);
            $tableAlias = $this->tableQuote($tableMatch['alias']);

            $tableQuery = "{$table} AS {$tableAlias}";
        } else {
            $table = $this->tableQuote($table);

            $tableQuery = $table;
        }

        $isJoin = false;
        $joinKey = is_array($join) ? array_keys($join) : null;

        if (
            isset($joinKey[0]) &&
            is_string($joinKey[0]) &&
            strpos($joinKey[0], '[') === 0
        ) {
            $isJoin = true;
            $tableQuery .= ' ' . $this->buildJoin($tableAlias ?? $table, $join);
        } else {
            if (is_null($columns)) {
                if (
                    !is_null($where) ||
                    (is_array($join) && isset($columnFn))
                ) {
                    $where = $join;
                    $columns = null;
                } else {
                    $where = null;
                    $columns = $join;
                }
            } else {
                $where = $columns;
                $columns = $join;
            }
        }

        if (isset($columnFn)) {
            if ($columnFn === 1) {
                $column = '1';

                if (is_null($where)) {
                    $where = $columns;
                }
            } elseif ($raw = $this->buildRaw($columnFn, $map)) {
                $column = $raw;
            } else {
                if (empty($columns) || $this->isRaw($columns)) {
                    $columns = '*';
                    $where = $join;
                }

                $column = $columnFn . '(' . $this->columnPush($columns, $map, true) . ')';
            }
        } else {
            $column = $this->columnPush($columns, $map, true, $isJoin);
        }

        return 'SELECT ' . $column . ' FROM ' . $tableQuery . $this->whereClause($where, $map);
    }

    /**
     * Build the join statement.
     *
     * @param string $table
     * @param array $join
     * @return string
     */
    protected function buildJoin(string $table, array $join) : string
    {
        $tableJoin = [];

        $joinMap = [
            '>' => 'LEFT',
            '<' => 'RIGHT',
            '<>' => 'FULL',
            '><' => 'INNER'
        ];

        foreach ($join as $subTable => $relation) {
            preg_match('/(\[(?<join>\<\>?|\>\<?)\])?(?<table>(?![_\d])[\p{N}\p{L}_]+)\s?(\((?<alias>(?![_\d])[\p{N}\p{L}_]+)\))?/u', $subTable, $match);

            if ($match['join'] !== '' && $match['table'] !== '') {
                if (is_string($relation)) {
                    $relation = 'USING ("' . $relation . '")';
                }

                if (is_array($relation)) {
                    // For ['column1', 'column2']
                    if (isset($relation[0])) {
                        $relation = 'USING ("' . implode('", "', $relation) . '")';
                    } else {
                        $joins = [];

                        foreach ($relation as $key => $value) {
                            $joins[] = (
                                strpos($key, '.') > 0 ?
                                    // For ['tableB.column' => 'column']
                                    $this->columnQuote($key) :

                                    // For ['column1' => 'column2']
                                    $table . '."' . $key . '"'
                            ) .
                            ' = ' .
                            $this->tableQuote($match['alias'] ?? $match['table']) . '."' . $value . '"';
                        }

                        $relation = 'ON ' . implode(' AND ', $joins);
                    }
                }

                $tableName = $this->tableQuote($match['table']) . ' ';

                if (isset($match['alias'])) {
                    $tableName .= 'AS ' . $this->tableQuote($match['alias']) . ' ';
                }

                $tableJoin[] = $joinMap[$match['join']] . ' JOIN ' . $tableName . $relation;
            }
        }

        return implode(' ', $tableJoin);
    }

    /**
     * Mapping columns for the stack.
     *
     * @param array|string $columns
     * @param array $stack
     * @param bool $root
     * @return array
     */
    protected function columnMap($columns, array &$stack, bool $root) : array
    {
        if ($columns === '*') {
            return $stack;
        }

        foreach ($columns as $key => $value) {
            if (is_int($key)) {
                preg_match('/((?![_\d])[\p{N}\p{L}_]+\.)?(?<column>(?![_\d])[\p{N}\p{L}_]+)(?:\s*\((?<alias>(?![_\d])[\p{N}\p{L}_]+)\))?(?:\s*\[(?<type>(?:String|Bool|Int|Number|Object|JSON))\])?/u', $value, $keyMatch);

                $columnKey = !empty($keyMatch['alias']) ?
                    $keyMatch['alias'] :
                    $keyMatch['column'];

                $stack[$value] = isset($keyMatch['type']) ?
                    [$columnKey, $keyMatch['type']] :
                    [$columnKey, 'String'];

            } elseif ($this->isRaw($value)) {
                preg_match('/((?![_\d])[\p{N}\p{L}_]+\.)?(?<column>(?![_\d])[\p{N}\p{L}_]+)(\s*\[(?<type>(String|Bool|Int|Number))\])?/u', $key, $keyMatch);

                $columnKey = $keyMatch['column'];

                $stack[$key] = isset($keyMatch['type']) ?
                    [$columnKey, $keyMatch['type']] :
                    [$columnKey, 'String'];
                
            } elseif (!is_int($key) && is_array($value)) {
                if ($root && count(array_keys($columns)) === 1) {
                    $stack[$key] = [$key, 'String'];
                }

                $this->columnMap($value, $stack, false);
            }
        }

        return $stack;
    }

    /**
     * Mapping the data.
     *
     * @param array $data
     * @param array $columns
     * @param array $columnMap
     * @param array $stack
     * @param bool $root
     * @param array $result
     * @return void
     */
    protected function dataMap(array $data, array $columns, array $columnMap, array &$stack, bool $root, array &$result) : void
    {
        if ($root) {
            $columnsKey = array_keys($columns);

            if (count($columnsKey) === 1 && is_array($columns[$columnsKey[0]])) {
                $indexKey = array_keys($columns)[0];
                $dataKey = preg_replace("/^(?![_\d])[\p{N}\p{L}_]+\./u", "", $indexKey);

                $currentStack = [];

                foreach ($data as $item) {
                    $this->dataMap($data, $columns[$indexKey], $columnMap, $currentStack, false, $result);

                    $index = $data[$dataKey];

                    $result[$index] = $currentStack;
                }
            } else {
                $currentStack = [];
                
                $this->dataMap($data, $columns, $columnMap, $currentStack, false, $result);

                $result[] = $currentStack;
            }

            return;
        }

        foreach ($columns as $key => $value) {
            $isRaw = $this->isRaw($value);

            if (is_int($key) || $isRaw) {
                $map = $columnMap[$isRaw ? $key : $value];

                $columnKey = $map[0];

                $item = $data[$columnKey];

                if (isset($map[1])) {
                    if ($isRaw && in_array($map[1], ['Object', 'JSON'])) {
                        continue;
                    }

                    if (is_null($item)) {
                        $stack[$columnKey] = null;
                        continue;
                    }

                    switch ($map[1]) {
                        case 'Number':
                            $stack[$columnKey] = (double) $item;
                            break;

                        case 'Int':
                            $stack[$columnKey] = (int) $item;
                            break;

                        case 'Bool':
                            $stack[$columnKey] = (bool) $item;
                            break;

                        case 'Object':
                            $stack[$columnKey] = unserialize($item);
                            break;

                        case 'JSON':
                            $stack[$columnKey] = json_decode($item, true);
                            break;

                        case 'String':
                            $stack[$columnKey] = $item;
                            break;
                    }
                } else {
                    $stack[$columnKey] = $item;
                }
            } else {
                $currentStack = [];

                $this->dataMap($data, $value, $columnMap, $currentStack, false, $result);

                $stack[$key] = $currentStack;
            }
        }
    }

    /**
     * Create a table.
     *
     * @param string $table
     * @param array $columns Columns definition.
     * @param array $options Additional table options for creating a table.
     * @return \PDOStatement|null
     */
    public function create(string $table, $columns, $options = null) : ?PDOStatement
    {
        $stack = [];

        $tableName = $this->prefix . $table;

        foreach ($columns as $name => $definition) {
            if (is_int($name)) {
                $stack[] = preg_replace('/\<((?![_\d])[\p{N}\p{L}_]+)\>/u', '"$1"', $definition);
            } elseif (is_array($definition)) {
                $stack[] = $name . ' ' . implode(' ', $definition);
            } elseif (is_string($definition)) {
                $stack[] = $name . ' ' . $this->query($definition);
            }
        }

        $tableOption = '';

        if (is_array($options)) {
            $optionStack = [];

            foreach ($options as $key => $value) {
                if (is_string($value) || is_int($value)) {
                    $optionStack[] = "{$key} = {$value}";
                }
            }

            $tableOption = ' ' . implode(', ', $optionStack);
        } elseif (is_string($options)) {
            $tableOption = ' ' . $options;
        }

        return $this->exec("CREATE TABLE IF NOT EXISTS $tableName (" . implode(', ', $stack) . ")$tableOption");
    }

    /**
     * Drop a table.
     *
     * @param string $table
     * @return \PDOStatement|null
     */
    public function drop(string $table) : ?PDOStatement
    {
        $tableName = $this->prefix . $table;

        return $this->exec('DROP TABLE IF EXISTS ' . $tableName);
    }

    /**
     * Select data from the table.
     *
     * @param string $table
     * @param array $join
     * @param array|string $columns
     * @param array $where
     * @return array
     */
    public function select(string $table, $join, $columns = null, $where = null) : array
    {
        $map = [];
        $result = [];
        $columnMap = [];
        $column = $where === null ? $join : $columns;
        $isSingle = (is_string($column) && $column !== '*');

        $statement = $this->exec($this->selectContext($table, $map, $join, $columns, $where), $map);

        $this->columnMap($columns, $columnMap, true);

        if (!$this->statement) {
            return $result;
        }

        if ($columns === '*') {
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        while ($data = $statement->fetch(PDO::FETCH_ASSOC)) {
            $currentStack = [];

            $this->dataMap($data, $columns, $columnMap, $currentStack, true, $result);
        }

        if ($isSingle) {
            $singleResult = [];
            $resultKey = $columnMap[$column][0];

            foreach ($result as $item) {
                $singleResult[] = $item[$resultKey];
            }

            return $singleResult;
        }

        return $result;
    }

    /**
     * Insert one or more records into table.
     *
     * @param string $table
     * @param array $datas
     * @return \PDOStatement|null
     */
    public function insert(string $table, array $datas) : ?PDOStatement
    {
        $stack = [];
        $columns = [];
        $fields = [];
        $map = [];

        if (!isset($datas[0])) {
            $datas = [$datas];
        }

        foreach ($datas as $data) {
            foreach ($data as $key => $value) {
                $columns[] = $key;
            }
        }

        $columns = array_unique($columns);

        foreach ($datas as $data) {
            $values = [];

            foreach ($columns as $key) {
                if ($raw = $this->buildRaw($data[$key], $map)) {
                    $values[] = $raw;
                    continue;
                }

                $mapKey = $this->mapKey();

                $values[] = $mapKey;

                if (!isset($data[$key])) {
                    $map[$mapKey] = [null, PDO::PARAM_NULL];
                } else {
                    $value = $data[$key];

                    $type = gettype($value);

                    switch ($type) {
                        case 'array':
                            $map[$mapKey] = [
                                strpos($key, '[JSON]') === strlen($key) - 6 ?
                                    json_encode($value) :
                                    serialize($value),
                                PDO::PARAM_STR
                            ];
                            break;

                        case 'object':
                            $value = serialize($value);

                            break;
                        case 'NULL':
                        case 'resource':
                        case 'boolean':
                        case 'integer':
                        case 'double':
                        case 'string':
                            $map[$mapKey] = $this->typeMap($value, $type);
                            break;
                    }
                }
            }

            $stack[] = '(' . implode(', ', $values) . ')';
        }

        foreach ($columns as $key) {
            $fields[] = $this->columnQuote(preg_replace("/(\s*\[JSON\]$)/i", '', $key));
        }

        return $this->exec('INSERT INTO ' . $this->tableQuote($table) . ' (' . implode(', ', $fields) . ') VALUES ' . implode(', ', $stack), $map);
    }

    /**
     * Modify data from the table.
     *
     * @param string $table
     * @param array $data
     * @param array $where
     * @return \PDOStatement|null
     */
    public function update(string $table, $data, $where = null) : ?PDOStatement
    {
        $fields = [];
        $map = [];

        foreach ($data as $key => $value) {
            $column = $this->columnQuote(preg_replace("/(\s*\[(JSON|\+|\-|\*|\/)\]$)/", '', $key));

            if ($raw = $this->buildRaw($value, $map)) {
                $fields[] = "{$column} = {$raw}";
                continue;
            }

            $mapKey = $this->mapKey();

            preg_match('/(?<column>(?![_\d])[\p{N}\p{L}_]+)(\[(?<operator>\+|\-|\*|\/)\])?/u', $key, $match);

            if (isset($match['operator'])) {
                if (is_numeric($value)) {
                    $fields[] = "{$column} = {$column} {$match['operator']} {$value}";
                }
            } else {
                $fields[] = "{$column} = {$mapKey}";

                $type = gettype($value);

                switch ($type) {
                    case 'array':
                        $map[$mapKey] = [
                            strpos($key, '[JSON]') === strlen($key) - 6 ?
                                json_encode($value) :
                                serialize($value),
                            PDO::PARAM_STR
                        ];
                        break;

                    case 'object':
                        $value = serialize($value);

                        break;
                    case 'NULL':
                    case 'resource':
                    case 'boolean':
                    case 'integer':
                    case 'double':
                    case 'string':
                        $map[$mapKey] = $this->typeMap($value, $type);
                        break;
                }
            }
        }

        return $this->exec('UPDATE ' . $this->tableQuote($table) . ' SET ' . implode(', ', $fields) . $this->whereClause($where, $map), $map);
    }

    /**
     * Delete data from the table.
     *
     * @param string $table
     * @param array $where
     * @return \PDOStatement|null
     */
    public function delete(string $table, array $where) : ?PDOStatement
    {
        $map = [];

        return $this->exec('DELETE FROM ' . $this->tableQuote($table) . $this->whereClause($where, $map), $map);
    }

    /**
     * Replace old data into new one.
     *
     * @param string $table
     * @param array $columns
     * @param array $where
     * @return \PDOStatement|null
     */
    public function replace(string $table, array $columns, $where = null) : ?PDOStatement
    {
        $map = [];
        $stack = [];

        foreach ($columns as $column => $replacements) {
            if (is_array($replacements)) {
                foreach ($replacements as $old => $new) {
                    $mapKey = $this->mapKey();

                    $columnName = $this->columnQuote($column);

                    $stack[] = "{$columnName} = REPLACE({$columnName}, {$mapKey}a, {$mapKey}b)";

                    $map[$mapKey . 'a'] = [$old, PDO::PARAM_STR];
                    $map[$mapKey . 'b'] = [$new, PDO::PARAM_STR];
                }
            }
        }

        if (empty($stack)) {
            throw new InvalidArgumentException('Invalid columns supplied.');
        }

        return $this->exec('UPDATE ' . $this->tableQuote($table) . ' SET ' . implode(', ', $stack) . $this->whereClause($where, $map), $map);
    }

    /**
     * Get only one record from the table.
     *
     * @param string $table
     * @param array $join
     * @param array|string $columns
     * @param array $where
     * @return mixed
     */
    public function get(string $table, $join = null, $columns = null, $where = null)
    {
        $map = [];
        $result = [];
        $columnMap = [];
        $currentStack = [];

        if ($where === null) {
            $column = $join;
            $columns['LIMIT'] = 1;
        } else {
            $column = $columns;
            $where['LIMIT'] = 1;
        }

        $isSingle = (is_string($column) && $column !== '*');

        $query = $this->exec($this->selectContext($table, $map, $join, $columns, $where), $map);

        if (!$this->statement) {
            return false;
        }

        $data = $query->fetchAll(PDO::FETCH_ASSOC);

        if (isset($data[0])) {
            if ($column === '*') {
                return $data[0];
            }

            $this->columnMap($columns, $columnMap, true);

            $this->dataMap($data[0], $columns, $columnMap, $currentStack, true, $result);

            if ($isSingle) {
                return $result[0][$columnMap[$column][0]];
            }

            return $result[0];
        }
    }

    /**
     * Determine whether the target data existed from the table.
     *
     * @param string $table
     * @param array $join
     * @param array $where
     * @return bool
     */
    public function has(string $table, $join, $where = null) : bool
    {
        $map = [];
        $column = null;

        if ($this->type === 'mssql') {
            $query = $this->exec($this->selectContext($table, $map, $join, $column, $where, Medoo::raw('TOP 1 1')), $map);
        } else {
            $query = $this->exec('SELECT EXISTS(' . $this->selectContext($table, $map, $join, $column, $where, 1) . ')', $map);
        }

        if (!$this->statement) {
            return false;
        }

        $result = $query->fetchColumn();

        return $result === '1' || $result === 1 || $result === true;
    }

    /**
     * Fetch data from the table randomly.
     *
     * @param string $table
     * @param array $join
     * @param array|string $columns
     * @param array $where
     * @return array
     */
    public function rand(string $table, $join = null, $columns = null, $where = null) : array
    {
        $order = ($this->type === 'mysql' ? 'RAND()'
                : $this->type === 'mssql') ? 'NEWID()'
                : 'RANDOM()';

        $orderRaw = $this->raw($order);

        if ($where === null) {
            if ($columns === null) {
                $columns = [
                    'ORDER' => $orderRaw
                ];
            } else {
                $columns = $join;
                unset($columns['ORDER']);

                $columns['ORDER'] = $orderRaw;
            }
        } else {
            unset($where['ORDER']);

            $where['ORDER'] = $orderRaw;
        }

        return $this->select($table, $join, $columns, $where);
    }

    /**
     * Build for aggregate function.
     *
     * @param string $type
     * @param string $table
     * @param array $join
     * @param string $column
     * @param array $where
     * @return string|null
     */
    private function aggregate(string $type, string $table, $join = null, $column = null, $where = null) : ?string
    {
        $map = [];

        $query = $this->exec($this->selectContext($table, $map, $join, $column, $where, strtoupper($type)), $map);

        if (!$this->statement) {
            return null;
        }

        return $query->fetchColumn();
    }

    /**
     * Count the number of rows from the table.
     *
     * @param string $table
     * @param array $join
     * @param string $column
     * @param array $where
     * @return int|null
     */
    public function count(string $table, $join = null, $column = null, $where = null) : ?int
    {
        return (int) $this->aggregate('count', $table, $join, $column, $where);
    }

    /**
     * Calculate the average value of the column.
     *
     * @param string $table
     * @param array $join
     * @param string $column
     * @param array $where
     * @return string|null
     */
    public function avg(string $table, $join, $column = null, $where = null) : ?string
    {
        return $this->aggregate('avg', $table, $join, $column, $where);
    }

    /**
     * Get the maximum value of the column.
     *
     * @param string $table
     * @param array $join
     * @param string $column
     * @param array $where
     * @return string|null
     */
    public function max(string $table, $join, $column = null, $where = null) : ?string
    {
        return $this->aggregate('max', $table, $join, $column, $where);
    }

    /**
     * Get the minimum value of the column.
     *
     * @param string $table
     * @param array $join
     * @param string $column
     * @param array $where
     * @return string|null
     */
    public function min(string $table, $join, $column = null, $where = null) : ?string
    {
        return $this->aggregate('min', $table, $join, $column, $where);
    }

    /**
     * Calculate the total value of the column.
     *
     * @param string $table
     * @param array $join
     * @param string $column
     * @param array $where
     * @return string|null
     */
    public function sum(string $table, $join, $column = null, $where = null) : ?string
    {
        return $this->aggregate('sum', $table, $join, $column, $where);
    }

    /**
     * Start a transaction.
     *
     * @param callable $actions
     * @return void
     */
    public function action(callable $actions) : void
    {
        if (is_callable($actions)) {
            $this->pdo->beginTransaction();

            try {
                $result = $actions($this);

                if ($result === false) {
                    $this->pdo->rollBack();
                } else {
                    $this->pdo->commit();
                }
            } catch (Exception $e) {
                $this->pdo->rollBack();

                throw $e;
            }
        }
    }

    /**
     * Return the ID for the last inserted row.
     *
     * @param string $name
     * @return string|null
     */
    public function id(string $name = null) : ?string
    {
        $type = $this->type;

        if ($type === 'oracle') {
            return "0";
        } elseif ($type === 'pgsql') {
            $id = $this->pdo->query('SELECT LASTVAL()')->fetchColumn();

            return (string) $id ?: null;
        }

        return $this->pdo->lastInsertId($name);
    }

    /**
     * Enable debug mode and output readable statement string.
     *
     * @return Medoo
     */
    public function debug() : self
    {
        $this->debugMode = true;

        return $this;
    }

    /**
     * Enable debug logging mode.
     *
     * @return void
     */
    public function beginDebug() : void
    {
        $this->debugMode = true;
        $this->debugLogging = true;
    }

    /**
     * Disable debug logging and return all readable statements.
     *
     * @return void
     */
    public function debugLog() : array
    {
        $this->debugMode = false;
        $this->debugLogging = false;

        return $this->debugLogs;
    }

    /**
     * Return the last performed statement.
     *
     * @return string|null
     */
    public function last() : ?string
    {
        if (empty($this->logs)) {
            return null;
        }

        $log = end($this->logs);

        return $this->generate($log[0], $log[1]);
    }

    /**
     * Return all executed statements.
     *
     * @return string[]
     */
    public function log() : array
    {
        return array_map(
            function ($log) {
                return $this->generate($log[0], $log[1]);
            },
            $this->logs
        );
    }

    /**
     * Get information about database connection.
     *
     * @return array
     */
    public function info() : array
    {
        $output = [
            'server' => 'SERVER_INFO',
            'driver' => 'DRIVER_NAME',
            'client' => 'CLIENT_VERSION',
            'version' => 'SERVER_VERSION',
            'connection' => 'CONNECTION_STATUS'
        ];

        foreach ($output as $key => $value) {
            $output[$key] = @$this->pdo->getAttribute(constant('PDO::ATTR_' . $value));
        }

        $output['dsn'] = $this->dsn;

        return $output;
    }
}
