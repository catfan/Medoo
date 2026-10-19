<?php

declare(strict_types=1);
/**
 * Medoo Database Framework.
 *
 * The Lightweight PHP Database Framework to Accelerate Development.
 *
 * @package Medoo
 * @author Angel Lai
 * @copyright Angel Lai
 * @license https://opensource.org/licenses/MIT
 * @link https://medoo.in
 */

namespace Medoo;

use PDO;
use Exception;
use PDOException;
use PDOStatement;
use InvalidArgumentException;

/**
 * Represents a raw SQL expression.
 */
class Raw
{
    /**
     * Placeholder bindings for the raw SQL expression.
     *
     * @var array<string, mixed>
     */
    public $map;

    /**
     * The raw SQL expression string.
     *
     * @var string
     */
    public $value;
}

/**
 * Core Medoo database class.
 *
 * @method null select(string $table, array $columns, callable $callback)
 * @method null select(string $table, string $column, callable $callback)
 * @method null select(string $table, array $columns, array $where, callable $callback)
 * @method null select(string $table, string $column, array $where, callable $callback)
 * @method null select(string $table, array $join, array $columns, array $where, callable $callback)
 * @method null select(string $table, array $join, string $column, array $where, callable $callback)
 * @method array select(string $table, string $column)
 * @method array select(string $table, string $column, array $where)
 * @method array select(string $table, array $join, string $column)
 * @method array select(string $table, array $join, string $column, array $where)
 * @method array select(string $table, array $columns)
 * @method array select(string $table, array $columns, array $where)
 * @method array select(string $table, array $join, array $columns)
 * @method array select(string $table, array $join, array $columns, array $where)
 * @method mixed get(string $table, array|string|null $join = null, array|string|null $columns = null, array|null $where = null)
 * @method bool has(string $table, array $join, array|null $where = null)
 * @method array rand(string $table, array|string|null $join = null, array|string|null $columns = null, array|null $where = null)
 * @method int|null count(string $table, array|string|null $join = null, string|null $column = null, array|null $where = null)
 * @method string|null max(string $table, array|string|null $join = null, string|null $column = null, array|null $where = null)
 * @method string|null min(string $table, array|string|null $join = null, string|null $column = null, array|null $where = null)
 * @method string|null avg(string $table, array|string|null $join = null, string|null $column = null, array|null $where = null)
 * @method string|null sum(string $table, array|string|null $join = null, string|null $column = null, array|null $where = null)
 *
 * @phpstan-type Parameter array{mixed, int}
 * @phpstan-type ParameterMap array<string, Parameter>
 * @phpstan-type QueryLog array{string, ParameterMap, float}
 * @phpstan-type ColumnMetadata array{0: string, 1?: string}
 * @phpstan-type ColumnMap array<array-key, ColumnMetadata>
 * @phpstan-type Row array<array-key, mixed>
 * @phpstan-type Condition array<array-key, mixed>
 */
class Medoo
{
    /**
     * The PDO database connection instance.
     *
     * @var \PDO
     */
    public $pdo;

    /**
     * The database type.
     *
     * @var string|null
     */
    public $type;

    /**
     * The table prefix.
     *
     * @var string|null
     */
    protected $prefix;

    /**
     * Current PDO statement instance.
     *
     * @var \PDOStatement|null
     */
    protected $statement;

    /**
     * The DSN connection string.
     *
     * @var string|null
     */
    protected $dsn;

    /**
     * Logged queries.
     *
     * @var list<QueryLog>
     */
    protected $logs = [];

    /**
     * Whether query logging is enabled.
     *
     * @var bool
     */
    protected $logging = false;

    /**
     * Whether the database is in test mode.
     *
     * @var bool
     */
    protected $testMode = false;

    /**
     * The last generated query string in test mode.
     *
     * @var string|null
     */
    public $queryString;

    /**
     * Whether debug mode is enabled.
     *
     * @var bool
     */
    protected $debugMode = false;

    /**
     * Whether debug logging is enabled.
     *
     * @var bool
     */
    protected $debugLogging = false;

    /**
     * Logged debug queries.
     *
     * @var list<string>
     */
    protected $debugLogs = [];

    /** @var array<string, array{column: string, alias: string, type: string}> */
    protected $columnCache = [];

    /**
     * The unique global identifier.
     *
     * @var int
     */
    protected $guid = 0;

    /**
     * The last inserted record ID.
     *
     * @var string
     */
    public $returnId = '';

    /**
     * The last error message.
     *
     * @var string|null
     */
    public $error = null;

    /**
     * The last error details.
     *
     * @var array<array-key, mixed>|null
     */
    public $errorInfo = null;

    /**
     * The connector used for table aliases.
     *
     * @var string
     */
    protected $tableAliasConnector = ' AS ';

    /**
     * The pattern used for quoting identifiers.
     *
     * @var string
     */
    protected $quotePattern = '"$1"';

    /**
     * Regular expression pattern for valid table names.
     *
     * @var string
     */
    protected const TABLE_PATTERN = "[\p{L}_][\p{L}\p{N}@$#\-_]*";

    /**
     * Regular expression pattern for valid column names.
     *
     * @var string
     */
    protected const COLUMN_PATTERN = "[\p{L}_][\p{L}\p{N}@$#\-_\.]*";

    /**
     * Regular expression pattern for valid alias names.
     *
     * @var string
     */
    protected const ALIAS_PATTERN = "[\p{L}_][\p{L}\p{N}@$#\-_]*";

    /**
     * Create a database connection.
     *
     * Example usage:
     *
     * ```
     * $database = new Medoo([
     *     // Required
     *      'type' => 'mysql',
     *      'database' => 'name',
     *      'host' => 'localhost',
     *      'username' => 'your_username',
     *      'password' => 'your_password',
     *
     *     // Optional
     *      'charset' => 'utf8mb4',
     *      'port' => 3306,
     *      'prefix' => 'PREFIX_'
     * ]);
     * ```
     *
     * @param array{
     *     prefix?: string,
     *     testMode?: bool,
     *     type?: string,
     *     database_type?: string,
     *     pdo?: mixed,
     *     database?: string,
     *     database_name?: string,
     *     socket?: string,
     *     host?: string,
     *     server?: string|false,
     *     logging?: mixed,
     *     dsn?: mixed,
     *     port?: mixed,
     *     charset?: mixed,
     *     driver?: string,
     *     appname?: string,
     *     collation?: mixed,
     *     username?: string,
     *     password?: string,
     *     option?: array<int, mixed>,
     *     error?: int,
     *     command?: mixed,
     *     ...
     * } $options The connection options.
     * @throws PDOException If the connection fails.
     * @link https://medoo.in/api/new
     * @codeCoverageIgnore
     */

    public function __construct(array $options)
    {
        if (isset($options['prefix'])) {
            $this->prefix = $options['prefix'];
        }

        if ($options['testMode'] ?? false) {
            $this->testMode = true;
            return;
        }

        $options['type'] = $options['type'] ?? $options['database_type'] ?? null;

        if (!is_string($options['type']) || $options['type'] === '') {
            throw new InvalidArgumentException('Database type is required.');
        }

        $this->setupType($options['type']);

        $database = $options['database'] ?? $options['database_name'] ?? '';
        $host = $options['host'] ?? $options['server'] ?? '';
        $host = $host === false ? '' : $host;
        $charset = $options['charset'] ?? null;
        $encodingPattern = '/\A[A-Za-z][A-Za-z0-9_-]*\z/';

        if ($charset !== null && (!is_string($charset) || preg_match($encodingPattern, $charset) !== 1)) {
            throw new InvalidArgumentException('Invalid charset supplied.');
        }

        if (isset($options['logging']) && is_bool($options['logging'])) {
            $this->logging = $options['logging'];
        }

        $commands = [];

        switch ($this->type) {

            case 'mysql':
                // Use standard quoted identifiers in MySQL.
                $commands[] = 'SET SQL_MODE=ANSI_QUOTES';

                if ($charset !== null && isset($options['collation'])) {
                    $collation = $options['collation'];

                    if (!is_string($collation) || preg_match($encodingPattern, $collation) !== 1) {
                        throw new InvalidArgumentException('Invalid collation supplied.');
                    }

                    $commands[] = "SET NAMES '{$charset}' COLLATE '{$collation}'";
                }

                break;

            case 'pgsql':
                if ($charset !== null) {
                    $commands[] = "SET NAMES '{$charset}'";
                }

                break;

            case 'mssql':
                // Keep QUOTED_IDENTIFIER enabled in SQL Server for standard identifier quoting.
                $commands[] = 'SET QUOTED_IDENTIFIER ON';

                // Keep ANSI_NULLS enabled in SQL Server for standard NULL comparisons.
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

        $dsn = null;
        $attr = null;

        if (isset($options['dsn'])) {
            if (is_string($options['dsn']) && $options['dsn'] !== '') {
                $dsn = $options['dsn'];
            } elseif (
                is_array($options['dsn']) &&
                isset($options['dsn']['driver']) &&
                is_string($options['dsn']['driver'])
            ) {
                $attr = $options['dsn'];
            } else {
                throw new InvalidArgumentException('Invalid DSN option supplied.');
            }
        } else {
            $port = isset($options['port']) && is_numeric($options['port']) ? $options['port'] : null;
            $isPort = $port !== null;

            switch ($this->type) {

                case 'mysql':
                    $attr = [
                        'driver' => 'mysql',
                        'dbname' => $database
                    ];

                    if ($charset !== null) {
                        $attr['charset'] = $charset;
                    }

                    if (isset($options['socket'])) {
                        $attr['unix_socket'] = $options['socket'];
                    } else {
                        $attr['host'] = $host;

                        if ($isPort) {
                            $attr['port'] = $port;
                        }
                    }

                    break;

                case 'pgsql':
                    $attr = [
                        'driver' => 'pgsql'
                    ];

                    $pgsqlHost = $options['socket'] ?? $host;

                    if ($pgsqlHost !== '') {
                        $attr['host'] = $pgsqlHost;
                    }

                    if ($database !== '') {
                        $attr['dbname'] = $database;
                    }

                    if ($isPort) {
                        $attr['port'] = $port;
                    }

                    break;

                case 'sybase':
                    $attr = [
                        'driver' => 'dblib',
                        'host' => $host,
                        'dbname' => $database
                    ];

                    if ($charset !== null) {
                        $attr['charset'] = $charset;
                    }

                    if ($isPort) {
                        $attr['port'] = $port;
                    }

                    break;

                case 'oracle':
                    $attr = [
                        'driver' => 'oci',
                        'dbname' => $host !== '' ?
                            '//' . $host . ($isPort ? ':' . $port : ':1521') . '/' . $database :
                            $database
                    ];

                    if ($charset !== null) {
                        $attr['charset'] = $charset;
                    }

                    break;

                case 'mssql':
                    if (isset($options['driver']) && $options['driver'] === 'dblib') {
                        $attr = [
                            'driver' => 'dblib',
                            'host' => $host . ($isPort ? ':' . $port : ''),
                            'dbname' => $database
                        ];

                        if (isset($options['appname'])) {
                            $attr['appname'] = $options['appname'];
                        }

                        if ($charset !== null) {
                            $attr['charset'] = $charset;
                        }
                    } else {
                        $attr = [
                            'driver' => 'sqlsrv',
                            'Server' => $host . ($isPort ? ',' . $port : ''),
                            'Database' => $database
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
                            $keyname = strtolower(
                                preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $value) ?? $value
                            );

                            if (isset($options[$keyname])) {
                                $attr[$value] = $options[$keyname];
                            }
                        }
                    }

                    break;

                case 'sqlite':
                    $attr = [
                        'driver' => 'sqlite',
                        $database
                    ];

                    break;
            }
        }

        if ($dsn === null) {
            if ($attr === null) {
                throw new InvalidArgumentException('Incorrect connection options.');
            }

            if (!is_string($attr['driver']) || $attr['driver'] === '') {
                throw new InvalidArgumentException('Invalid DSN option supplied.');
            }

            $driver = $attr['driver'];

            if (!in_array($driver, PDO::getAvailableDrivers(), true)) {
                throw new InvalidArgumentException("Unsupported PDO driver: {$driver}.");
            }

            unset($attr['driver']);

            $stack = [];

            foreach ($attr as $key => $value) {
                if (!is_scalar($value)) {
                    throw new InvalidArgumentException('Invalid DSN attribute supplied.');
                }

                $stack[] = is_int($key) ? (string) $value : $key . '=' . $value;
            }

            $dsn = $driver . ':' . implode(';', $stack);
        }

        $this->dsn = $dsn;

        try {
            $this->pdo = new PDO(
                $dsn,
                $options['username'] ?? null,
                $options['password'] ?? null,
                $options['option'] ?? []
            );

            if (isset($options['error'])) {
                if (
                    !in_array($options['error'], [
                        PDO::ERRMODE_SILENT,
                        PDO::ERRMODE_WARNING,
                        PDO::ERRMODE_EXCEPTION
                    ])
                ) {
                    throw new InvalidArgumentException("Unsupported PDO error mode: {$options['error']}.");
                }

                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, $options['error']);
            }

            if (isset($options['command']) && is_array($options['command'])) {
                $commands = array_merge($commands, array_filter($options['command'], 'is_string'));
            }

            foreach ($commands as $value) {
                $this->pdo->exec($value);
            }
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    /**
     * Set up the database type.
     *
     * @param string $type The database driver name.
     * @return void
     */
    public function setupType(string $type)
    {
        $databaseType = strtolower($type);

        if ($databaseType === 'mariadb') {
            $databaseType = 'mysql';
        }

        if ($databaseType === 'oracle') {
            $this->tableAliasConnector = ' ';
        } elseif ($databaseType === 'mysql') {
            $this->quotePattern = '`$1`';
        } elseif ($databaseType === 'mssql') {
            $this->quotePattern = '[$1]';
        }

        $this->type = $databaseType;
    }

    /**
     * Generate a new map key for the placeholder.
     *
     * @return string
     */
    protected function mapKey(): string
    {
        return ':MeD' . $this->guid++ . '_mK';
    }

    /**
     * Return the initialized database connection.
     *
     * @throws InvalidArgumentException If no connection has been initialized.
     */
    private function connection(): PDO
    {
        if ($this->pdo === null) {
            throw new InvalidArgumentException('Database connection is not initialized.');
        }

        return $this->pdo;
    }

    /**
     * Execute a raw SQL statement with bound parameters.
     *
     * @param string $statement The raw SQL statement.
     * @param array<string, mixed> $map The parameter values for the prepared statement.
     * @return \PDOStatement|null
     */
    public function query(string $statement, array $map = []): ?PDOStatement
    {
        $raw = $this->raw($statement, $map);
        $parameterMap = [];
        $statement = $this->buildRaw($raw, $parameterMap);

        if ($statement === null) {
            return null;
        }

        return $this->exec($statement, $parameterMap);
    }

    /**
     * Prepare and execute an SQL statement.
     *
     * @param string $statement The SQL statement.
     * @param ParameterMap $map The parameter values for the prepared statement.
     * @param (callable(PDOStatement): void)|null $callback An optional callback invoked before execution inside a transaction.
     * @codeCoverageIgnore
     * @return \PDOStatement|null
     */
    public function exec(string $statement, array $map = [], ?callable $callback = null): ?PDOStatement
    {
        $this->statement = null;
        $this->errorInfo = null;
        $this->error = null;

        if ($this->testMode) {
            $this->queryString = $this->generate($statement, $map);
            return null;
        }

        if ($this->debugMode) {
            if ($this->debugLogging) {
                $this->debugLogs[] = $this->generate($statement, $map);
                return null;
            }

            echo $this->generate($statement, $map);

            $this->debugMode = false;

            return null;
        }

        $pdo = $this->connection();
        $pdoStatement = $pdo->prepare($statement);
        $errorInfo = $pdo->errorInfo();

        if (!$pdoStatement instanceof PDOStatement) {
            $this->errorInfo = $errorInfo;
            $this->error = isset($errorInfo[2]) && is_string($errorInfo[2]) ? $errorInfo[2] : null;
            return null;
        }

        if ($errorInfo[0] !== '00000') {
            $this->errorInfo = $errorInfo;
            $this->error = isset($errorInfo[2]) && is_string($errorInfo[2]) ? $errorInfo[2] : null;

            return null;
        }

        foreach ($map as $key => $value) {
            $pdoStatement->bindValue($key, $value[0], $value[1]);
        }

        $start = hrtime(true);

        if ($callback !== null) {
            $pdo->beginTransaction();
            $callback($pdoStatement);
            $execute = $pdoStatement->execute();
            $pdo->commit();
        } else {
            $execute = $pdoStatement->execute();
        }

        $log = [$statement, $map, (hrtime(true) - $start) / 1e9];

        if ($this->logging) {
            $this->logs[] = $log;
        } else {
            $this->logs = [$log];
        }

        $errorInfo = $pdoStatement->errorInfo();

        if ($errorInfo[0] !== '00000') {
            $this->errorInfo = $errorInfo;
            $this->error = isset($errorInfo[2]) && is_string($errorInfo[2]) ? $errorInfo[2] : null;

            return null;
        }

        if ($execute) {
            $this->statement = $pdoStatement;
        }

        return $pdoStatement;
    }

    /**
     * Generate a readable SQL statement with bound values interpolated.
     *
     * @param string $statement The SQL statement template.
     * @param ParameterMap $map The parameter values bound to the statement.
     * @codeCoverageIgnore
     * @return string
     */
    protected function generate(string $statement, array $map): string
    {
        $statement = preg_replace(
            '/(?!\'[^\s]+\s?)"(' . $this::COLUMN_PATTERN . ')"(?!\s?[^\s]+\')/u',
            $this->quotePattern,
            $statement
        ) ?? $statement;

        foreach ($map as $key => $value) {
            if ($value[1] === PDO::PARAM_STR) {
                $replace = $this->quote(is_scalar($value[0]) ? (string) $value[0] : '');
            } elseif ($value[1] === PDO::PARAM_NULL) {
                $replace = 'NULL';
            } elseif ($value[1] === PDO::PARAM_LOB) {
                $replace = '{LOB_DATA}';
            } else {
                $replace = is_scalar($value[0]) ? (string) $value[0] : '';
            }

            $statement = str_replace($key, $replace, $statement);
        }

        return $statement;
    }

    /**
     * Create a raw SQL expression object.
     *
     * @param string $string The raw SQL expression.
     * @param array<string, mixed> $map The placeholder bindings for the raw SQL expression.
     * @return Raw
     */
    public static function raw(string $string, array $map = []): Raw
    {
        $raw = new Raw();

        $raw->map = $map;
        $raw->value = $string;

        return $raw;
    }

    /**
     * Build the SQL fragment represented by a Raw object.
     *
     * @param mixed $raw The value to inspect.
     * @param ParameterMap $map The parameter map, passed by reference.
     * @return string|null
     */
    protected function buildRaw($raw, array &$map): ?string
    {
        if (!$raw instanceof Raw) {
            return null;
        }

        $query = preg_replace_callback(
            '/(([`\'])[\<]*?)?((FROM|TABLE|TABLES LIKE|INTO|UPDATE|JOIN|TABLE IF EXISTS)\s*)?\<((' . $this::TABLE_PATTERN . ')(\.' . $this::COLUMN_PATTERN . ')?)\>([^,]*?\2)?/',
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
     * Escape and quote a string literal for use in an SQL statement.
     *
     * @param string $string The string literal to quote.
     * @return string
     */
    public function quote(string $string): string
    {
        if ($this->type === 'mysql') {
            return "'" . preg_replace(['/([\'"])/', '/(\\\\\\\")/'], ["\\\\\${1}", '\\\${1}'], $string) . "'";
        }

        return "'" . preg_replace('/\'/', '\'\'', $string) . "'";
    }

    /**
     * Apply the configured prefix and quote a table identifier.
     *
     * @param string $table The table name to quote.
     * @return string
     * @throws InvalidArgumentException If the table name is invalid.
     */
    public function tableQuote(string $table): string
    {
        if (preg_match("/^" . $this::TABLE_PATTERN . "$/u", $table)) {
            return '"' . $this->prefix . $table . '"';
        }

        throw new InvalidArgumentException("Incorrect table name: {$table}.");
    }

    /**
     * Quote a column identifier for use in an SQL statement.
     *
     * @param string $column The column name to quote.
     * @return string
     * @throws InvalidArgumentException If the column name is invalid.
     */
    public function columnQuote(string $column): string
    {
        if (preg_match("/^" . $this::TABLE_PATTERN . "(\.?" . $this::ALIAS_PATTERN . ")?$/u", $column)) {
            return strpos($column, '.') !== false ?
                '"' . $this->prefix . str_replace('.', '"."', $column) . '"' :
                '"' . $column . '"';
        }

        throw new InvalidArgumentException("Incorrect column name: {$column}.");
    }

    /**
     * Map a PHP value type to its PDO parameter type.
     *
     * @param mixed $value The value to map.
     * @param string $type The PHP type name returned by gettype().
     * @return Parameter
     */
    protected function typeMap($value, string $type): array
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
     * Cache and return parsed column metadata for repeated column definitions.
     *
     * @param string $column The column definition string.
     * @param string $pattern The regular expression used to parse the definition.
     * @return array{column: string, alias: string, type: string}
     */
    protected function columnMatch(string $column, string $pattern): array
    {
        $key = $pattern . $column;

        if (!isset($this->columnCache[$key])) {
            if (count($this->columnCache) >= 1024) {
                $this->columnCache = [];
            }

            preg_match($pattern, $column, $match);

            $this->columnCache[$key] = [
                'column' => $match['column'] ?? '',
                'alias' => $match['alias'] ?? '',
                'type' => $match['type'] ?? ''
            ];
        }

        return $this->columnCache[$key];
    }

    /**
     * Build the column list fragment for an SQL statement.
     *
     * @param array<array-key, mixed>|string $columns The requested columns.
     * @param ParameterMap $map The parameter map, passed by reference.
     * @param bool $root Whether the current level is the root column definition.
     * @param bool $isJoin Whether the statement includes joins.
     * @return string
     */
    protected function columnPush(&$columns, array &$map, bool $root, bool $isJoin = false): string
    {
        if ($columns === '*') {
            return $columns;
        }

        $stack = [];
        $hasDistinct = false;

        if (is_string($columns)) {
            $columns = [$columns];
        }

        $isSingleRootColumn = $root && count($columns) === 1;

        foreach ($columns as $key => $value) {
            $isIntKey = is_int($key);
            $isArrayValue = is_array($value);

            if (!$isIntKey && $isArrayValue && $isSingleRootColumn) {
                $stack[] = $this->columnQuote($key);
                $stack[] = $this->columnPush($value, $map, false, $isJoin);
            } elseif ($isArrayValue) {
                $stack[] = $this->columnPush($value, $map, false, $isJoin);
            } elseif (!$isIntKey && $raw = $this->buildRaw($value, $map)) {
                $match = $this->columnMatch(
                    $key,
                    "/(?<column>" . $this::COLUMN_PATTERN . ")(\s*\[(?<type>(String|Bool|Int|Number))\])?/u"
                );
                $stack[] = "{$raw} AS {$this->columnQuote($match['column'])}";
            } elseif ($isIntKey && is_string($value)) {
                if ($isJoin && strpos($value, '*') !== false) {
                    throw new InvalidArgumentException('Cannot use table.* to select all columns while joining table.');
                }

                $match = $this->columnMatch(
                    $value,
                    "/(?<column>" . $this::COLUMN_PATTERN . ")(?:\s*\((?<alias>" . $this::ALIAS_PATTERN . ")\))?(?:\s*\[(?<type>(?:String|Bool|Int|Number|Object|JSON))\])?/u"
                );

                $columnString = '';

                if (!empty($match['alias'])) {
                    $columnString = "{$this->columnQuote($match['column'])} AS {$this->columnQuote($match['alias'])}";
                    $columns[$key] = $match['alias'];

                    if (!empty($match['type'])) {
                        $columns[$key] .= ' [' . $match['type'] . ']';
                    }
                } else {
                    $columnString = $this->columnQuote($match['column']);
                }

                if (!$hasDistinct && strpos($value, '@') === 0) {
                    $columnString = 'DISTINCT ' . $columnString;
                    $hasDistinct = true;
                    array_unshift($stack, $columnString);

                    continue;
                }

                $stack[] = $columnString;
            }
        }

        return implode(',', $stack);
    }

    /**
     * Build the conditional expression for a WHERE-style clause.
     *
     * @param Condition $data The condition data.
     * @param ParameterMap $map The parameter map, passed by reference.
     * @param string $conjunctor The logical conjunction used between conditions.
     * @return string
     */
    protected function dataImplode(array $data, array &$map, string $conjunctor): string
    {
        $stack = [];

        foreach ($data as $key => $value) {
            $type = gettype($value);

            if (
                is_array($value) &&
                is_string($key) &&
                preg_match("/^(AND|OR)(\s+#.*)?$/", $key, $relationMatch)
            ) {
                $stack[] = '(' . $this->dataImplode($value, $map, ' ' . $relationMatch[1]) . ')';
                continue;
            }

            $mapKey = $this->mapKey();
            $isIndex = is_int($key);

            $subject = $isIndex ? $value : $key;

            if (!is_string($subject)) {
                continue;
            }

            preg_match(
                "/(?<column>" . $this::COLUMN_PATTERN . ")(\[(?<operator>.*)\])?(?<comparison>" . $this::COLUMN_PATTERN . ")?/u",
                $subject,
                $match
            );

            $column = $this->columnQuote($match['column']);
            $operator = $match['operator'] ?? null;

            if ($isIndex && isset($match['comparison']) && in_array($operator, ['>', '>=', '<', '<=', '=', '!='])) {
                $stack[] = "{$column} {$operator} " . $this->columnQuote($match['comparison']);
                continue;
            }

            if ($operator && $operator !== '=') {
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
                            if (!is_array($value)) {
                                break;
                            }

                            if ($value === []) {
                                $stack[] = '1 = 1';
                                break;
                            }

                            $values = [];

                            foreach ($value as $index => $item) {
                                if ($raw = $this->buildRaw($item, $map)) {
                                    $values[] = $raw;
                                } else {
                                    $stackKey = $mapKey . $index . '_i';

                                    $values[] = $stackKey;
                                    $map[$stackKey] = $this->typeMap($item, gettype($item));
                                }
                            }

                            $stack[] = $column . ' NOT IN (' . implode(', ', $values) . ')';
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
                    if (!is_array($value)) {
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
                        $likeKey = "{$mapKey}_{$index}_i";

                        if (!is_scalar($item) && $item !== null) {
                            continue;
                        }

                        $item = (string) $item;

                        if (!preg_match('/((?<!\\\)\[.+(?<!\\\)\]|(?<!\\\)[\*\?\!\%#^_]|%.+|.+%)/', $item)) {
                            $item = '%' . $item . '%';
                        }

                        $likeClauses[] = $column . ($operator === '!~' ? ' NOT' : '') . " LIKE {$likeKey}";
                        $map[$likeKey] = [$item, PDO::PARAM_STR];
                    }

                    $stack[] = '(' . implode($connector, $likeClauses) . ')';
                } elseif ($operator === '<>' || $operator === '><') {
                    if (is_array($value) && isset($value[0], $value[1])) {
                        if ($operator === '><') {
                            $column .= ' NOT';
                        }

                        if ($value[0] instanceof Raw && $value[1] instanceof Raw) {
                            $stack[] = "({$column} BETWEEN {$this->buildRaw($value[0], $map)} AND {$this->buildRaw($value[1], $map)})";
                        } else {
                            $stack[] = "({$column} BETWEEN {$mapKey}a AND {$mapKey}b)";
                            $dataType = (is_numeric($value[0]) && is_numeric($value[1])) ? PDO::PARAM_INT : PDO::PARAM_STR;

                            $map[$mapKey . 'a'] = [$value[0], $dataType];
                            $map[$mapKey . 'b'] = [$value[1], $dataType];
                        }
                    }
                } elseif ($operator === 'REGEXP' || $operator === '!REGEXP') {
                    $isNegative = $operator === '!REGEXP';
                    $map[$mapKey] = [$value, PDO::PARAM_STR];

                    switch ($this->type) {
                        case 'pgsql':
                            $stack[] = "{$column} " . ($isNegative ? '!~' : '~') . " {$mapKey}";
                            break;

                        case 'oracle':
                        case 'mssql':
                            $stack[] = ($isNegative ? 'NOT ' : '') . "REGEXP_LIKE({$column}, {$mapKey})";
                            break;

                        default:
                            $stack[] = "{$column} " . ($isNegative ? 'NOT ' : '') . "REGEXP {$mapKey}";
                    }
                } else {
                    throw new InvalidArgumentException("Invalid operator [{$operator}] for column {$column} supplied.");
                }

                continue;
            }

            switch ($type) {

                case 'NULL':
                    $stack[] = $column . ' IS NULL';
                    break;

                case 'array':
                    if (!is_array($value)) {
                        break;
                    }

                    if ($value === []) {
                        $stack[] = '1 = 0';
                        break;
                    }

                    $values = [];

                    foreach ($value as $index => $item) {
                        if ($raw = $this->buildRaw($item, $map)) {
                            $values[] = $raw;
                        } else {
                            $stackKey = $mapKey . $index . '_i';

                            $values[] = $stackKey;
                            $map[$stackKey] = $this->typeMap($item, gettype($item));
                        }
                    }

                    $stack[] = $column . ' IN (' . implode(', ', $values) . ')';
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

        return implode($conjunctor . ' ', $stack);
    }

    /**
     * Build the WHERE clause.
     *
     * @param Condition|Raw|string|null $where The WHERE clause definition.
     * @param ParameterMap $map The parameter map, passed by reference.
     * @return string
     */
    protected function whereClause($where, array &$map): string
    {
        $clause = '';

        if (is_array($where)) {
            $conditions = array_diff_key($where, array_flip(
                ['GROUP', 'ORDER', 'HAVING', 'LIMIT', 'LIKE', 'MATCH']
            ));

            if (!empty($conditions)) {
                $clause = ' WHERE ' . $this->dataImplode($conditions, $map, ' AND');
            }

            if (isset($where['MATCH']) && $this->type === 'mysql') {
                $match = $where['MATCH'];

                if (is_array($match) && isset($match['columns'], $match['keyword'])) {
                    $mode = '';

                    $options = [
                        'natural' => 'IN NATURAL LANGUAGE MODE',
                        'natural+query' => 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION',
                        'boolean' => 'IN BOOLEAN MODE',
                        'query' => 'WITH QUERY EXPANSION'
                    ];

                    if (isset($match['mode']) && is_string($match['mode']) && isset($options[$match['mode']])) {
                        $mode = ' ' . $options[$match['mode']];
                    }

                    if (is_array($match['columns'])) {
                        $matchColumns = array_map(
                            [$this, 'columnQuote'],
                            array_filter($match['columns'], 'is_string')
                        );

                        $columns = implode(', ', $matchColumns);
                        $mapKey = $this->mapKey();
                        $map[$mapKey] = [$match['keyword'], PDO::PARAM_STR];
                        $clause .= ($clause !== '' ? ' AND ' : ' WHERE') . ' MATCH (' . $columns . ') AGAINST (' . $mapKey . $mode . ')';
                    }
                }
            }

            if (isset($where['GROUP'])) {
                $group = $where['GROUP'];

                if (is_array($group)) {
                    $stack = array_map([$this, 'columnQuote'], array_filter($group, 'is_string'));

                    $clause .= ' GROUP BY ' . implode(',', $stack);
                } elseif ($raw = $this->buildRaw($group, $map)) {
                    $clause .= ' GROUP BY ' . $raw;
                } elseif (is_string($group)) {
                    $clause .= ' GROUP BY ' . $this->columnQuote($group);
                }
            }

            if (isset($where['HAVING'])) {
                $having = $where['HAVING'];

                if ($raw = $this->buildRaw($having, $map)) {
                    $clause .= ' HAVING ' . $raw;
                } elseif (is_array($having)) {
                    $clause .= ' HAVING ' . $this->dataImplode($having, $map, ' AND');
                }
            }

            if (isset($where['ORDER'])) {
                $order = $where['ORDER'];

                if (is_array($order)) {
                    $stack = [];

                    foreach ($order as $column => $value) {
                        if (is_array($value)) {
                            if (!is_string($column)) {
                                continue;
                            }

                            $valueStack = [];
                            $isOrderField = $this->type === 'mysql';

                            foreach ($value as $index => $item) {
                                if ($raw = $this->buildRaw($item, $map)) {
                                    $fieldValue = $raw;
                                } else {
                                    $fieldKey = $this->mapKey();
                                    $fieldValue = $fieldKey;
                                    $map[$fieldKey] = $this->typeMap($item, gettype($item));
                                }

                                $valueStack[] = $isOrderField ?
                                    $fieldValue :
                                    "WHEN {$fieldValue} THEN " . ($index + 1);
                            }

                            $stack[] = $isOrderField ?
                                "FIELD({$this->columnQuote($column)}, " . implode(',', $valueStack) . ')' :
                                "CASE {$this->columnQuote($column)} " . implode(' ', $valueStack) . ' ELSE 0 END';
                        } elseif (is_string($column) && ($value === 'ASC' || $value === 'DESC')) {
                            $stack[] = $this->columnQuote($column) . ' ' . $value;
                        } elseif (is_int($column) && is_string($value)) {
                            $stack[] = $this->columnQuote($value);
                        }
                    }

                    $clause .= ' ORDER BY ' . implode(',', $stack);
                } elseif ($raw = $this->buildRaw($order, $map)) {
                    $clause .= ' ORDER BY ' . $raw;
                } elseif (is_string($order)) {
                    $clause .= ' ORDER BY ' . $this->columnQuote($order);
                }
            }

            if (isset($where['LIMIT'])) {
                $limit = $where['LIMIT'];

                if (in_array($this->type, ['oracle', 'mssql'])) {
                    if ($this->type === 'mssql' && !isset($where['ORDER'])) {
                        $clause .= ' ORDER BY (SELECT 0)';
                    }

                    if (is_numeric($limit)) {
                        $limit = [0, $limit];
                    }

                    if (
                        is_array($limit) &&
                        is_numeric($limit[0]) &&
                        is_numeric($limit[1])
                    ) {
                        $clause .= " OFFSET {$limit[0]} ROWS FETCH NEXT {$limit[1]} ROWS ONLY";
                    }
                } else {
                    if (is_numeric($limit)) {
                        $clause .= ' LIMIT ' . $limit;
                    } elseif (
                        is_array($limit) &&
                        is_numeric($limit[0]) &&
                        is_numeric($limit[1])
                    ) {
                        $clause .= " LIMIT {$limit[1]} OFFSET {$limit[0]}";
                    }
                }
            }
        } elseif ($raw = $this->buildRaw($where, $map)) {
            $clause .= ' ' . $raw;
        }

        return $clause;
    }

    /**
     * Build a SELECT statement.
     *
     * @param string $table The table name, optionally with an alias.
     * @param ParameterMap $map The parameter map, passed by reference.
     * @param array<array-key, mixed>|string|Raw|null $join The JOIN definition, selected columns, or raw WHERE clause when no JOIN is used.
     * @param array<array-key, mixed>|string|Raw|null $columns The selected columns, or the WHERE clause when no JOIN is used.
     * @param Condition|Raw|null $where The WHERE clause definition.
     * @param string|Raw|int|null $columnFn An aggregate function name or custom select expression.
     * @return string
     */
    protected function selectContext(
        string $table,
        array &$map,
        $join,
        &$columns = null,
        $where = null,
        $columnFn = null
    ): string {
        preg_match("/(?<table>" . $this::TABLE_PATTERN . ")\s*\((?<alias>" . $this::ALIAS_PATTERN . ")\)/u", $table, $tableMatch);

        if (isset($tableMatch['table'], $tableMatch['alias'])) {
            $table = $this->tableQuote($tableMatch['table']);
            $tableAlias = $this->tableQuote($tableMatch['alias']);
            $tableQuery = "{$table}{$this->tableAliasConnector}{$tableAlias}";
        } else {
            $table = $this->tableQuote($table);
            $tableQuery = $table;
        }

        $isJoin = $this->isJoin($join);

        if ($isJoin && is_array($join)) {
            $tableQuery .= ' ' . $this->buildJoin($tableAlias ?? $table, $join, $map);
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
                if (empty($columns) || $columns instanceof Raw) {
                    $columns = '*';
                    $where = $join;
                }

                if (!is_string($columnFn)) {
                    throw new InvalidArgumentException('Invalid aggregate function supplied.');
                }

                $column = $columnFn . '(' . $this->columnPush($columns, $map, true) . ')';
            }
        } else {
            if (!is_array($columns) && !is_string($columns)) {
                $columns = '*';
            }

            $column = $this->columnPush($columns, $map, true, $isJoin);
        }

        return 'SELECT ' . $column . ' FROM ' . $tableQuery . $this->whereClause($where, $map);
    }

    /**
     * Determine whether the given array uses Medoo join syntax.
     *
     * @param mixed $join The value to inspect.
     * @return bool
     */
    protected function isJoin($join): bool
    {
        if (!is_array($join)) {
            return false;
        }

        $key = array_key_first($join);

        if (is_string($key) && isset($key[0]) && $key[0] === '[') {
            return true;
        }

        return false;
    }

    /**
     * Build the JOIN clause.
     *
     * @param string $table The base table name or alias used in join conditions.
     * @param array<array-key, mixed> $join The JOIN definition.
     * @param ParameterMap $map The parameter map, passed by reference.
     * @return string
     */
    protected function buildJoin(string $table, array $join, array &$map): string
    {
        $tableJoin = [];
        $type = [
            '>' => 'LEFT',
            '<' => 'RIGHT',
            '<>' => 'FULL',
            '><' => 'INNER'
        ];

        foreach ($join as $subtable => $relation) {
            if (!is_string($subtable)) {
                continue;
            }

            preg_match("/(\[(?<join>\<\>?|\>\<?)\])?(?<table>" . $this::TABLE_PATTERN . ")\s?(\((?<alias>" . $this::ALIAS_PATTERN . ")\))?/u", $subtable, $match);

            if ($match['join'] === '' || $match['table'] === '') {
                continue;
            }

            if (is_string($relation)) {
                $relation = 'USING (' . $this->columnQuote($relation) . ')';
            } elseif (is_array($relation)) {
                // Syntax: ['column1', 'column2']
                if (isset($relation[0])) {
                    $columns = array_map(
                        [$this, 'columnQuote'],
                        array_filter($relation, 'is_string')
                    );

                    $relation = 'USING (' . implode(', ', $columns) . ')';
                } else {
                    $joins = [];

                    foreach ($relation as $key => $value) {
                        if ($key === 'AND' && is_array($value)) {
                            $joins[] = $this->dataImplode($value, $map, ' AND');
                            continue;
                        }

                        if (!is_string($key) || !is_string($value)) {
                            continue;
                        }

                        $joins[] = (
                            strpos($key, '.') > 0 ?
                                // Syntax: ['tableB.column' => 'column']
                                $this->columnQuote($key) :

                                // Syntax: ['column1' => 'column2']
                                $table . '.' . $this->columnQuote($key)
                        ) .
                        ' = ' .
                        $this->tableQuote($match['alias'] ?? $match['table']) . '.' . $this->columnQuote($value);
                    }

                    $relation = 'ON ' . implode(' AND ', $joins);
                }
            } elseif ($raw = $this->buildRaw($relation, $map)) {
                $relation = $raw;
            }

            if (!is_string($relation)) {
                continue;
            }

            $tableName = $this->tableQuote($match['table']);

            if (isset($match['alias'])) {
                $tableName .= $this->tableAliasConnector . $this->tableQuote($match['alias']);
            }

            $tableJoin[] = $type[$match['join']] . " JOIN {$tableName} {$relation}";
        }

        return implode(' ', $tableJoin);
    }

    /**
     * Build the column metadata used for result mapping.
     *
     * @param array<array-key, mixed>|string $columns The requested columns.
     * @param array<array-key, ColumnMetadata> $stack The column metadata stack, passed by reference.
     * @param bool $root Whether the current level is the root column definition.
     * @return array<array-key, ColumnMetadata>
     */
    protected function columnMap($columns, array &$stack, bool $root): array
    {
        if ($columns === '*') {
            return $stack;
        }

        if (!is_array($columns)) {
            return $stack;
        }

        $isSingleRootColumn = $root && count($columns) === 1;

        foreach ($columns as $key => $value) {
            $isIntKey = is_int($key);

            if ($isIntKey && is_string($value)) {
                $keyMatch = $this->columnMatch(
                    $value,
                    "/(" . $this::TABLE_PATTERN . "\.)?(?<column>" . $this::COLUMN_PATTERN . ")(?:\s*\((?<alias>" . $this::ALIAS_PATTERN . ")\))?(?:\s*\[(?<type>(?:String|Bool|Int|Number|Object|JSON))\])?/u"
                );

                $columnKey = !empty($keyMatch['alias']) ?
                    $keyMatch['alias'] :
                    $keyMatch['column'];

                $stack[$value] = !empty($keyMatch['type']) ?
                    [$columnKey, $keyMatch['type']] :
                    [$columnKey];
            } elseif ($value instanceof Raw) {
                $keyMatch = $this->columnMatch(
                    $key,
                    "/(" . $this::TABLE_PATTERN . "\.)?(?<column>" . $this::COLUMN_PATTERN . ")(\s*\[(?<type>(String|Bool|Int|Number))\])?/u"
                );
                $columnKey = $keyMatch['column'];

                $stack[$key] = !empty($keyMatch['type']) ?
                    [$columnKey, $keyMatch['type']] :
                    [$columnKey];
            } elseif (is_array($value)) {
                if ($isSingleRootColumn) {
                    if (is_string($key)) {
                        $stack[$key] = [$key, 'String'];
                    }
                }

                $this->columnMap($value, $stack, false);
            }
        }

        return $stack;
    }

    /**
     * Map fetched row data to the requested result structure.
     *
     * @param Row $data The fetched row data.
     * @param array<array-key, mixed> $columns The requested columns.
     * @param ColumnMap $columnMap The column metadata map.
     * @param array<array-key, mixed> $stack The current result stack, passed by reference.
     * @param bool $root Whether the current level is the root mapping level.
     * @param array<array-key, mixed>|null $result The result accumulator, passed by reference.
     * @codeCoverageIgnore
     * @return void
     */
    final protected function dataMap(
        array $data,
        array $columns,
        array $columnMap,
        array &$stack,
        bool $root,
        ?array &$result = null
    ): void {
        if ($root) {
            $indexKey = array_key_first($columns);

            if (is_string($indexKey) && count($columns) === 1 && is_array($columns[$indexKey])) {
                $dataKey = strpos($indexKey, '.') === false ? $indexKey :
                    (preg_replace("/^" . $this::COLUMN_PATTERN . "\./u", '', $indexKey) ?? $indexKey);
                $currentStack = [];

                $this->dataMap($data, $columns[$indexKey], $columnMap, $currentStack, false, $result);
                $index = $data[$dataKey];

                if (!is_int($index) && !is_string($index)) {
                    throw new InvalidArgumentException('Invalid result index value.');
                }

                if (isset($result)) {
                    $result[$index] = $currentStack;
                } else {
                    $stack[$index] = $currentStack;
                }
            } else {
                $currentStack = [];
                $this->dataMap($data, $columns, $columnMap, $currentStack, false, $result);

                if (isset($result)) {
                    $result[] = $currentStack;
                } else {
                    $stack = $currentStack;
                }
            }

            return;
        }

        foreach ($columns as $key => $value) {
            $isRaw = $value instanceof Raw;

            if ((is_int($key) && is_string($value)) || $isRaw) {
                $mapKey = $isRaw ? $key : $value;

                if (!is_string($mapKey) || !isset($columnMap[$mapKey])) {
                    continue;
                }

                $map = $columnMap[$mapKey];
                $columnKey = $map[0];
                $item = $data[$columnKey];

                if (isset($map[1])) {
                    $type = $map[1];

                    if ($isRaw && ($type === 'Object' || $type === 'JSON')) {
                        continue;
                    }

                    if (is_null($item)) {
                        $stack[$columnKey] = null;
                        continue;
                    }

                    switch ($type) {

                        case 'Number':
                            $stack[$columnKey] = is_numeric($item) ? (float) $item : 0.0;
                            break;

                        case 'Int':
                            $stack[$columnKey] = is_numeric($item) ? (int) $item : 0;
                            break;

                        case 'Bool':
                            $stack[$columnKey] = (bool) $item;
                            break;

                        case 'Object':
                            $stack[$columnKey] = is_string($item) ? unserialize($item) : false;
                            break;

                        case 'JSON':
                            $stack[$columnKey] = is_string($item) ? json_decode($item, true) : null;
                            break;

                        case 'String':
                            $stack[$columnKey] = is_scalar($item) ? (string) $item : '';
                            break;
                    }
                } else {
                    $stack[$columnKey] = $item;
                }
            } elseif (is_string($key) && is_array($value)) {
                $currentStack = [];
                $this->dataMap($data, $value, $columnMap, $currentStack, false, $result);

                $stack[$key] = $currentStack;
            }
        }
    }

    /**
     * Build and execute a query with RETURNING and INTO clauses.
     *
     * @param string $query The SQL statement.
     * @param ParameterMap $map The parameter map, passed by reference.
     * @param array<string, array{0: string, 1: mixed, 2: int, 3?: int}> $data The RETURNING metadata, passed by reference.
     * @return \PDOStatement|null
     */
    private function returningQuery(string $query, array &$map, array &$data): ?PDOStatement
    {
        $returnColumns = array_map(
            function (array $value): string {
                return $value[0];
            },
            $data
        );

        $query .= ' RETURNING ' .
                    implode(', ', array_map([$this, 'columnQuote'], $returnColumns)) .
                    ' INTO ' .
                    implode(', ', array_keys($data));

        return $this->exec($query, $map, function (PDOStatement $statement) use (&$data): void {
            // @codeCoverageIgnoreStart
            foreach ($data as $key => $return) {
                if (isset($return[3])) {
                    $statement->bindParam($key, $data[$key][1], $return[2], $return[3]);
                } else {
                    $statement->bindParam($key, $data[$key][1], $return[2]);
                }
            }
            // @codeCoverageIgnoreEnd
        });
    }

    /**
     * Create a table.
     *
     * @param string $table The table name.
     * @param array<array-key, array<array-key, string>|string> $columns The column definitions.
     * @param array<array-key, int|string>|string|null $options Additional CREATE TABLE options.
     * @return \PDOStatement|null
     */
    public function create(string $table, $columns, $options = null): ?PDOStatement
    {
        $stack = [];
        $tableOption = '';
        $tableName = $this->tableQuote($table);
        $identityColumn = null;

        foreach ($columns as $name => $definition) {
            if (is_int($name)) {
                if (!is_string($definition)) {
                    continue;
                }

                if (!isset($definition[0]) || $definition[0] !== '@') {
                    $stack[] = preg_replace("/\<(" . $this::COLUMN_PATTERN . ")\>/u", '"$1"', $definition) ?? $definition;
                    continue;
                }

                if (preg_match("/\A@(" . $this::TABLE_PATTERN . ")\z/u", $definition, $match) !== 1) {
                    throw new InvalidArgumentException("Invalid identity column supplied: {$definition}.");
                }

                if ($identityColumn !== null) {
                    throw new InvalidArgumentException('Multiple identity columns supplied.');
                }

                $identityColumn = $match[1];

                if (array_key_exists($identityColumn, $columns)) {
                    throw new InvalidArgumentException("Identity column {$identityColumn} is already defined.");
                }

                $identityDefinition = [
                    'mysql' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'pgsql' => 'BIGINT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY',
                    'sqlite' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
                    'mssql' => 'BIGINT IDENTITY(1,1) PRIMARY KEY',
                    'oracle' => 'NUMBER(19) GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY',
                    'sybase' => 'BIGINT IDENTITY PRIMARY KEY'
                ][$this->type] ?? null;

                if ($identityDefinition === null) {
                    throw new InvalidArgumentException("Identity columns are not supported for database type {$this->type}.");
                }

                $stack[] = $this->columnQuote($identityColumn) . ' ' . $identityDefinition;
            } elseif (is_array($definition)) {
                $stack[] = $this->columnQuote($name) . ' ' . implode(' ', $definition);
            } elseif (is_string($definition)) {
                $stack[] = $this->columnQuote($name) . ' ' . $definition;
            }
        }

        if (is_array($options)) {
            $optionStack = [];

            foreach ($options as $key => $value) {
                $optionStack[] = "{$key} = {$value}";
            }

            $tableOption = ' ' . implode(', ', $optionStack);
        } elseif (is_string($options)) {
            $tableOption = ' ' . $options;
        }

        $command = 'CREATE TABLE';

        if (in_array($this->type, ['mysql', 'pgsql', 'sqlite'])) {
            $command .= ' IF NOT EXISTS';
        }

        return $this->exec("{$command} {$tableName} (" . implode(', ', $stack) . "){$tableOption}");
    }

    /**
     * Drop a table if it exists.
     *
     * @param string $table The table name.
     * @return \PDOStatement|null
     */
    public function drop(string $table): ?PDOStatement
    {
        return $this->exec('DROP TABLE IF EXISTS ' . $this->tableQuote($table));
    }

    /**
     * Fetch rows from a table.
     *
     * @param string $table The table name.
     * @param array<array-key, mixed>|string $join The JOIN definition, or the columns to select when no JOIN is used.
     * @param array<array-key, mixed>|string|Raw|callable|null $columns The selected columns, the WHERE clause, or a row callback depending on the call signature.
     * @param array<array-key, mixed>|Raw|callable|null $where The WHERE clause, or a row callback depending on the call signature.
     * @param (callable(Row): void)|null $callback A row callback function.
     * @return array<array-key, mixed>|null
     */
    public function select(string $table, $join, $columns = null, $where = null, $callback = null): ?array
    {
        $map = [];
        $result = [];

        $args = func_get_args();
        $lastArgs = end($args);
        $callback = is_callable($lastArgs) ? $lastArgs : null;

        $where = is_callable($where) ? null : $where;
        $columns = is_callable($columns) ? null : $columns;

        $column = $where === null ? $join : $columns;
        $isSingle = (is_string($column) && $column !== '*');

        $this->exec($this->selectContext($table, $map, $join, $columns, $where), $map);

        $statement = $this->statement;

        if ($statement === null) {
            return $result;
        }

        // @codeCoverageIgnoreStart
        if ($columns === '*') {
            if (isset($callback)) {
                while (($data = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
                    $callback($data);
                }

                return null;
            }

            $data = $statement->fetchAll(PDO::FETCH_ASSOC);

            return is_array($data) ? $data : [];
        }

        if (!is_array($columns)) {
            return $result;
        }

        $data = $statement->fetch(PDO::FETCH_ASSOC);

        if ($data === false) {
            return isset($callback) ? null : $result;
        }

        $columnMap = [];
        $this->columnMap($columns, $columnMap, true);
        $resultKey = $isSingle ? ($columnMap[array_key_first($columnMap)][0] ?? null) : null;
        $columnKeys = [];

        foreach ($columns as $key => $value) {
            if (
                !is_int($key) || !is_string($value) ||
                !isset($columnMap[$value]) || isset($columnMap[$value][1])
            ) {
                $columnKeys = null;
                break;
            }

            $columnKeys[$columnMap[$value][0]] = true;
        }

        $direct = $columnKeys !== null &&
            (!$isSingle || $resultKey !== null) && array_keys($data) === array_keys($columnKeys);

        if ($direct) {
            $fetchMode = PDO::FETCH_ASSOC;

            if ($isSingle && $statement->columnCount() === 1) {
                $fetchMode = PDO::FETCH_NUM;
                $data = [$data[$resultKey]];
                $resultKey = 0;
            }

            if (isset($callback)) {
                do {
                    $callback($isSingle ? $data[$resultKey] : $data);
                } while (($data = $statement->fetch($fetchMode)) !== false);

                return null;
            }

            do {
                $result[] = $isSingle ? $data[$resultKey] : $data;
            } while (($data = $statement->fetch($fetchMode)) !== false);

            return $result;
        }

        do {
            $currentStack = [];

            if ($isSingle || isset($callback)) {
                $this->dataMap($data, $columns, $columnMap, $currentStack, true);
                $row = $isSingle ? ($resultKey !== null ? $currentStack[$resultKey] : null) : $currentStack;

                if (isset($callback)) {
                    $callback($row);
                } else {
                    $result[] = $row;
                }
            } else {
                $this->dataMap($data, $columns, $columnMap, $currentStack, true, $result);
            }
        } while (($data = $statement->fetch(PDO::FETCH_ASSOC)) !== false);

        return isset($callback) ? null : $result;
    }
    // @codeCoverageIgnoreEnd

    /**
     * Insert one or more rows into a table.
     *
     * @param string $table The table name.
     * @param array<array-key, mixed> $values A row or a list of rows to insert.
     * @param string|null $primaryKey The primary key column used with Oracle or PostgreSQL RETURNING.
     * @return \PDOStatement|null
     */
    public function insert(string $table, array $values, ?string $primaryKey = null): ?PDOStatement
    {
        $this->returnId = '';
        $stack = [];
        $columns = [];
        $fields = [];
        $map = [];
        $returnings = [];

        if (!isset($values[0])) {
            $values = [$values];
        }

        foreach ($values as $data) {
            if (!is_array($data)) {
                throw new InvalidArgumentException('Invalid insert values supplied.');
            }

            foreach ($data as $key => $value) {
                if (is_string($key)) {
                    $columns[] = $key;
                }
            }
        }

        $columns = array_unique($columns);

        foreach ($values as $data) {
            $values = [];

            foreach ($columns as $key) {
                $value = $data[$key] ?? null;
                $type = gettype($value);

                if ($this->type === 'oracle' && $type === 'resource') {
                    $values[] = 'EMPTY_BLOB()';
                    $returnings[$this->mapKey()] = [$key, $value, PDO::PARAM_LOB];
                    continue;
                }

                if ($raw = $this->buildRaw($data[$key], $map)) {
                    $values[] = $raw;
                    continue;
                }

                $mapKey = $this->mapKey();
                $values[] = $mapKey;

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

            $stack[] = '(' . implode(', ', $values) . ')';
        }

        foreach ($columns as $key) {
            $fields[] = $this->columnQuote(preg_replace("/(\s*\[JSON\]$)/i", '', $key) ?? $key);
        }

        $query = 'INSERT INTO ' . $this->tableQuote($table) . ' (' . implode(', ', $fields) . ') VALUES ' . implode(', ', $stack);

        if ($this->type === 'pgsql' && $primaryKey !== null && $primaryKey !== '') {
            $statement = $this->exec($query . ' RETURNING ' . $this->columnQuote($primaryKey), $map);

            if ($statement !== null) {
                $returnIds = $statement->fetchAll(PDO::FETCH_COLUMN);

                if (is_array($returnIds) && $returnIds !== []) {
                    $returnId = end($returnIds);

                    if ($returnId !== false && is_scalar($returnId)) {
                        $this->returnId = (string) $returnId;
                    }
                }
            }

            return $statement;
        }

        if (
            $this->type === 'oracle' && (!empty($returnings) || isset($primaryKey))
        ) {
            if ($primaryKey) {
                $returnings[':RETURNID'] = [$primaryKey, '', PDO::PARAM_INT, 8];
            }

            $statement = $this->returningQuery($query, $map, $returnings);

            if ($primaryKey) {
                $returnId = $returnings[':RETURNID'][1];
                $this->returnId = is_scalar($returnId) ? (string) $returnId : '';
            }

            return $statement;
        }

        return $this->exec($query, $map);
    }

    /**
     * Update rows in a table.
     *
     * @param string $table The table name.
     * @param array<string, mixed> $data The column values to update.
     * @param Condition|Raw|null $where The WHERE clause definition.
     * @return \PDOStatement|null
     */
    public function update(string $table, $data, $where = null): ?PDOStatement
    {
        $fields = [];
        $map = [];
        $returnings = [];

        foreach ($data as $key => $value) {
            $column = $this->columnQuote(
                preg_replace("/(\s*\[(JSON|\+|\-|\*|\/)\]$)/", '', $key) ?? $key
            );
            $type = gettype($value);

            if ($this->type === 'oracle' && $type === 'resource') {
                $fields[] = "{$column} = EMPTY_BLOB()";
                $returnings[$this->mapKey()] = [$key, $value, PDO::PARAM_LOB];
                continue;
            }

            if ($raw = $this->buildRaw($value, $map)) {
                $fields[] = "{$column} = {$raw}";
                continue;
            }

            preg_match("/" . $this::COLUMN_PATTERN . "(\[(?<operator>\+|\-|\*|\/)\])?/u", $key, $match);

            if (isset($match['operator'])) {
                if (is_numeric($value)) {
                    $fields[] = "{$column} = {$column} {$match['operator']} {$value}";
                }
            } else {
                $mapKey = $this->mapKey();
                $fields[] = "{$column} = {$mapKey}";

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

        $query = 'UPDATE ' . $this->tableQuote($table) . ' SET ' . implode(', ', $fields) . $this->whereClause($where, $map);

        if ($this->type === 'oracle' && !empty($returnings)) {
            return $this->returningQuery($query, $map, $returnings);
        }

        return $this->exec($query, $map);
    }

    /**
     * Delete rows from a table.
     *
     * @param string $table The table name.
     * @param Condition|Raw $where The WHERE clause definition.
     * @return \PDOStatement|null
     */
    public function delete(string $table, $where): ?PDOStatement
    {
        $map = [];

        return $this->exec('DELETE FROM ' . $this->tableQuote($table) . $this->whereClause($where, $map), $map);
    }

    /**
     * Replace substrings in column values by using SQL REPLACE().
     *
     * @param string $table The table name.
     * @param array<string, array<array-key, mixed>> $columns The replacement map in the form ['column' => ['old' => 'new']].
     * @param Condition|Raw|null $where The WHERE clause definition.
     * @return \PDOStatement|null
     */
    public function replace(string $table, array $columns, $where = null): ?PDOStatement
    {
        $map = [];
        $stack = [];

        foreach ($columns as $column => $replacements) {
            foreach ($replacements as $old => $new) {
                $mapKey = $this->mapKey();
                $columnName = $this->columnQuote($column);
                $stack[] = "{$columnName} = REPLACE({$columnName}, {$mapKey}a, {$mapKey}b)";

                $map[$mapKey . 'a'] = [$old, PDO::PARAM_STR];
                $map[$mapKey . 'b'] = [$new, PDO::PARAM_STR];
            }
        }

        if (empty($stack)) {
            throw new InvalidArgumentException('Invalid columns supplied.');
        }

        return $this->exec('UPDATE ' . $this->tableQuote($table) . ' SET ' . implode(', ', $stack) . $this->whereClause($where, $map), $map);
    }

    /**
     * Fetch a single row or scalar value from a table.
     *
     * @param string $table The table name.
     * @param array<array-key, mixed>|string|null $join The JOIN definition, or the columns to select when no JOIN is used.
     * @param array<array-key, mixed>|string|Raw|null $columns The selected columns, or the WHERE clause when no JOIN is used.
     * @param Condition|Raw|null $where The WHERE clause definition.
     * @return mixed
     */
    public function get(string $table, $join = null, $columns = null, $where = null)
    {
        $map = [];
        $result = [];
        $columnMap = [];
        $currentStack = [];

        if ($where === null) {
            if ($this->isJoin($join)) {
                $where = [];
                $where['LIMIT'] = 1;
            } else {
                if (!is_array($columns)) {
                    $columns = [];
                }
                $columns['LIMIT'] = 1;
            }

            $column = $join;
        } else {
            $column = $columns;
            if (is_array($where)) {
                $where['LIMIT'] = 1;
            }
        }

        $isSingle = (is_string($column) && $column !== '*');
        $this->exec($this->selectContext($table, $map, $join, $columns, $where), $map);
        $statement = $this->statement;

        if ($statement === null) {
            return false;
        }

        // @codeCoverageIgnoreStart
        $data = $statement->fetch(PDO::FETCH_ASSOC);

        if (is_array($data)) {
            if ($column === '*') {
                return $data;
            }

            if (!is_array($columns)) {
                return false;
            }

            $this->columnMap($columns, $columnMap, true);
            $this->dataMap($data, $columns, $columnMap, $currentStack, true);

            if ($isSingle) {
                return $currentStack[$columnMap[$column][0]];
            }

            return $currentStack;
        }
    }
    // @codeCoverageIgnoreEnd

    /**
     * Determine whether at least one row matches the given conditions.
     *
     * @param string $table The table name.
     * @param array<array-key, mixed> $join The JOIN definition, or the WHERE clause when no JOIN is used.
     * @param Condition|null $where The WHERE clause definition when JOINs are provided.
     * @return bool
     */
    public function has(string $table, $join, $where = null): bool
    {
        $map = [];
        $column = null;

        $this->exec(
            $this->type === 'mssql' ?
                $this->selectContext($table, $map, $join, $column, $where, Medoo::raw('TOP 1 1')) :
                'SELECT EXISTS(' . $this->selectContext($table, $map, $join, $column, $where, 1) . ')',
            $map
        );
        $statement = $this->statement;

        if ($statement === null) {
            return false;
        }

        // @codeCoverageIgnoreStart
        $result = $statement->fetchColumn();

        return (bool) $result;
    }
    // @codeCoverageIgnoreEnd

    /**
     * Fetch rows in random order.
     *
     * @param string $table The table name.
     * @param array<array-key, mixed>|string|null $join The JOIN definition, or the columns to select when no JOIN is used.
     * @param array<array-key, mixed>|string|null $columns The selected columns, or the WHERE clause when no JOIN is used.
     * @param Condition|null $where The WHERE clause definition.
     * @return array<array-key, mixed>
     */
    public function rand(string $table, $join = null, $columns = null, $where = null): array
    {
        switch ($this->type) {
            case 'mysql':
            case 'sybase':
                $random = 'RAND()';
                break;

            case 'mssql':
                $random = 'NEWID()';
                break;

            case 'oracle':
                $random = 'DBMS_RANDOM.VALUE';
                break;

            default:
                $random = 'RANDOM()';
        }

        $orderRaw = $this->raw($random);

        if ($where === null) {
            if ($this->isJoin($join)) {
                $where = [];
                $where['ORDER'] = $orderRaw;
            } else {
                if (!is_array($columns)) {
                    $columns = [];
                }
                $columns['ORDER'] = $orderRaw;
            }
        } else {
            $where['ORDER'] = $orderRaw;
        }

        return $this->select($table, $join ?? [], $columns, $where) ?? [];
    }

    /**
     * Build and execute an aggregate query.
     *
     * @param string $type The aggregate function name.
     * @param string $table The table name.
     * @param array<array-key, mixed>|string|Raw|null $join The JOIN definition, target column, or WHERE clause when no JOIN is used.
     * @param string|null $column The target column or expression.
     * @param Condition|null $where The WHERE clause definition.
     * @return string|null
     */
    private function aggregate(string $type, string $table, $join = null, $column = null, $where = null): ?string
    {
        $map = [];

        $this->exec($this->selectContext($table, $map, $join, $column, $where, $type), $map);
        $statement = $this->statement;

        if ($statement === null) {
            return null;
        }

        // @codeCoverageIgnoreStart
        return (string) $statement->fetchColumn();
    }
    // @codeCoverageIgnoreEnd

    /**
     * Count matching rows.
     *
     * @param string $table The table name.
     * @param array<array-key, mixed>|string|Raw|null $join The JOIN definition, target column, or WHERE clause when no JOIN is used.
     * @param string|null $column The target column or expression for COUNT().
     * @param Condition|null $where The WHERE clause definition.
     * @return int|null
     */
    public function count(string $table, $join = null, $column = null, $where = null): ?int
    {
        return (int) $this->aggregate('COUNT', $table, $join, $column, $where);
    }

    /**
     * Calculate the average value of a column.
     *
     * @param string $table The table name.
     * @param array<array-key, mixed>|string|Raw|null $join The JOIN definition, target column, or WHERE clause when no JOIN is used.
     * @param string|null $column The target column or expression.
     * @param Condition|null $where The WHERE clause definition.
     * @return string|null
     */
    public function avg(string $table, $join, $column = null, $where = null): ?string
    {
        return $this->aggregate('AVG', $table, $join, $column, $where);
    }

    /**
     * Get the maximum value of a column.
     *
     * @param string $table The table name.
     * @param array<array-key, mixed>|string|Raw|null $join The JOIN definition, target column, or WHERE clause when no JOIN is used.
     * @param string|null $column The target column or expression.
     * @param Condition|null $where The WHERE clause definition.
     * @return string|null
     */
    public function max(string $table, $join, $column = null, $where = null): ?string
    {
        return $this->aggregate('MAX', $table, $join, $column, $where);
    }

    /**
     * Get the minimum value of a column.
     *
     * @param string $table The table name.
     * @param array<array-key, mixed>|string|Raw|null $join The JOIN definition, target column, or WHERE clause when no JOIN is used.
     * @param string|null $column The target column or expression.
     * @param Condition|null $where The WHERE clause definition.
     * @return string|null
     */
    public function min(string $table, $join, $column = null, $where = null): ?string
    {
        return $this->aggregate('MIN', $table, $join, $column, $where);
    }

    /**
     * Calculate the sum of a column.
     *
     * @param string $table The table name.
     * @param array<array-key, mixed>|string|Raw|null $join The JOIN definition, target column, or WHERE clause when no JOIN is used.
     * @param string|null $column The target column or expression.
     * @param Condition|null $where The WHERE clause definition.
     * @return string|null
     */
    public function sum(string $table, $join, $column = null, $where = null): ?string
    {
        return $this->aggregate('SUM', $table, $join, $column, $where);
    }

    /**
     * Execute a callback within a transaction.
     *
     * @param callable $actions The callback to execute transactionally.
     * @codeCoverageIgnore
     * @return void
     */
    public function action(callable $actions): void
    {
        $pdo = $this->connection();
        $pdo->beginTransaction();

        try {
            $result = $actions($this);

            if ($result === false) {
                $pdo->rollBack();
            } else {
                $pdo->commit();
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Return the ID of the last inserted row.
     *
     * @param string|null $name The sequence name, when required by the driver.
     * @codeCoverageIgnore
     * @return string|null
     */
    public function id(?string $name = null): ?string
    {
        $type = $this->type;

        if ($type === 'oracle') {
            return $this->returnId;
        }

        if ($this->pdo === null) {
            return null;
        }

        if ($type === 'pgsql' && $name === null) {
            if ($this->returnId !== '') {
                return $this->returnId;
            }

            try {
                $statement = $this->pdo->query('SELECT LASTVAL()');
                $id = $statement ? $statement->fetchColumn() : false;
            } catch (PDOException $e) {
                $errorInfo = $e->errorInfo;
                $sqlState = is_array($errorInfo) && isset($errorInfo[0]) ? $errorInfo[0] : $e->getCode();

                if ($sqlState === '55000') {
                    return null;
                }

                throw $e;
            }
        } elseif ($type === 'sybase') {
            $statement = $this->pdo->query('SELECT @@IDENTITY');
            $id = $statement ? $statement->fetchColumn() : false;
        } else {
            $id = $name === null ? $this->pdo->lastInsertId() : $this->pdo->lastInsertId($name);
        }

        return $id === false ? null : (string) $id;
    }

    /**
     * Enable debug mode and output the next interpolated SQL statement.
     *
     * @codeCoverageIgnore
     * @return self
     */
    public function debug(): self
    {
        $this->debugMode = true;

        return $this;
    }

    /**
     * Enable debug logging mode.
     *
     * @codeCoverageIgnore
     * @return void
     */
    public function beginDebug(): void
    {
        $this->debugMode = true;
        $this->debugLogging = true;
    }

    /**
     * Disable debug logging and return all collected interpolated SQL statements.
     *
     * @codeCoverageIgnore
     * @return string[]
     */
    public function debugLog(): array
    {
        $this->debugMode = false;
        $this->debugLogging = false;

        return $this->debugLogs;
    }

    /**
     * Return the last executed SQL statement with bound values interpolated.
     *
     * @codeCoverageIgnore
     * @return string|null
     */
    public function last(): ?string
    {
        if (empty($this->logs)) {
            return null;
        }

        $log = $this->logs[array_key_last($this->logs)];

        return $this->generate($log[0], $log[1]);
    }

    /**
     * Return all executed SQL statements with bound values interpolated.
     *
     * @codeCoverageIgnore
     * @return list<array{string, string}>
     */
    public function log(): array
    {
        return array_map(
            function ($log) {
                return [
                    $this->generate($log[0], $log[1]),
                    sprintf('%.6fs', $log[2])
                ];
            },
            $this->logs
        );
    }

    /**
     * Get information about the current database connection.
     *
     * @codeCoverageIgnore
     * @return array<string, mixed>
     */
    public function info(): array
    {
        $pdo = $this->connection();
        $output = [
            'server' => 'SERVER_INFO',
            'driver' => 'DRIVER_NAME',
            'client' => 'CLIENT_VERSION',
            'version' => 'SERVER_VERSION',
            'connection' => 'CONNECTION_STATUS'
        ];

        foreach ($output as $key => $value) {
            try {
                $output[$key] = $pdo->getAttribute(constant('PDO::ATTR_' . $value));
            } catch (PDOException $e) {
                $output[$key] = $e->getMessage();
            }
        }

        $output['dsn'] = $this->dsn;

        return $output;
    }
}
