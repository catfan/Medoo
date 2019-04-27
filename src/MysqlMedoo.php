<?php

namespace Medoo;

use PDO;
use Exception;
use PDOException;
use InvalidArgumentException;
use Medoo\Medoo;

class MysqlMedoo extends Medoo 
{
    const SELECT_MODIFIERS = [
        'DISTINCT',
        // 'DISTINCTROW',
        // 'HIGH_PRIORITY',
        // 'STRAIGHT_JOIN',
        // 'SQL_SMALL_RESULT',
        // 'SQL_BIG_RESULT',
        // 'SQL_BUFFER_RESULT',
        // 'SQL_CACHE',
        // 'SQL_NO_CACHE',
        'SQL_CALC_FOUND_ROWS',
    ];

    const DEFAULT_PDO_ATTRS = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    public function __construct(array $options)
    {
        $this->type = 'mysql';
        if (isset($options['prefix'])) {
            $this->prefix = $options['prefix'];
        }
        if (isset($options['logging']) && is_bool($options['logging'])) {
            $this->logging = $optins['logging'];
        }

        $option = isset($options[ 'option' ]) ? (array) $options[ 'option' ] : [];
        foreach(self::DEFAULT_PDO_ATTRS as $key => $value) {
            if (!isset($option[$key])) {
                $option[$key] = $value;
            }
        }
        $commands = (isset($options[ 'command' ]) && is_array($options[ 'command' ])) ? $options[ 'command' ] : [];

        if (isset($options[ 'pdo' ]))
        {
            if (!$options[ 'pdo' ] instanceof PDO)
            {
                throw new InvalidArgumentException('Invalid PDO object supplied');
            }

            $this->pdo = $options[ 'pdo' ];

            foreach ($commands as $value)
            {
                $this->pdo->exec($value);
            }

            return;
        }

        if (isset($options[ 'dsn' ]))
        {
            if (is_array($options[ 'dsn' ]) && isset($options[ 'dsn' ][ 'driver' ]))
            {
                $attr = $options[ 'dsn' ];
            }
            else
            {
                throw new InvalidArgumentException('Invalid DSN option supplied');
            }
        }
        else
        {
            if (
                isset($options[ 'port' ]) &&
                is_int($options[ 'port' ] * 1)
            )
            {
                $port = $options[ 'port' ];
            }

            $is_port = isset($port);

            $attr = [
                'driver' => 'mysql',
                'dbname' => $options[ 'database_name' ]
            ];

            if (isset($options[ 'socket' ]))
            {
                $attr[ 'unix_socket' ] = $options[ 'socket' ];
            }
            else
            {
                $attr[ 'host' ] = $options[ 'server' ];

                if ($is_port)
                {
                    $attr[ 'port' ] = $port;
                }
            }
            if (isset($options['charset'])) {
                $attr['charset'] = $options['charset'];
            
            }
        }

        if (!isset($attr))
        {
            throw new InvalidArgumentException('Incorrect connection options');
        }

        $driver = $attr[ 'driver' ];

        if (!in_array($driver, PDO::getAvailableDrivers()))
        {
            throw new InvalidArgumentException("Unsupported PDO driver: {$driver}");
        }

        unset($attr[ 'driver' ]);

        $stack = [];

        foreach ($attr as $key => $value)
        {
            $stack[] = is_int($key) ? $value : $key . '=' . $value;
        }

        $dsn = $driver . ':' . implode($stack, ';');

        $this->dsn = $dsn;

        try {
            $this->pdo = new PDO(
                $dsn,
                isset($options[ 'username' ]) ? $options[ 'username' ] : null,
                isset($options[ 'password' ]) ? $options[ 'password' ] : null,
                $option
            );

            foreach ($commands as $value)
            {
                $this->pdo->exec($value);
            }
        }
        catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    protected function tableQuote($table)
    {
        return '`' . $this->prefix . $table . '`';
    }

    protected function columnQuote($string)
    {
        if (strpos($string, '.') !== false)
        {
            return '`' . $this->prefix . str_replace('.', '`.`', $string) . '`';
        }

        return '`' . $string . '`';
    }

    protected function hasStar($columns)
    {
        if (is_string($columns)) {
            return $columns === '*' || $columns[-1] === '*'; 
        } else if (is_array($columns)) {
            foreach($columns as $key => $value) {
                if (is_string($value) && $value[-1] === '*') {
                    return true;
                }
            }
        }
        return false;
    }

    protected function columnPush(&$columns, &$map)
    {
        if ($columns === '*')
        {
            return $columns;
        }

        $stack = [];
        $modifiers = [];

        if (is_string($columns))
        {
            $columns = [$columns];
        }

        foreach ($columns as $key => $value)
        {
            if (is_array($value))
            {
                $stack[] = $this->columnPush($value, $map);
            }
            elseif (!is_int($key) && $raw = $this->buildRaw($value, $map))
            {
                preg_match('/(?<column>[a-zA-Z0-9_\.]+)(\s*\[(?<type>(String|Bool|Int|Number))\])?/i', $key, $match);

                $stack[] = $raw . ' AS ' . $this->columnQuote( $match[ 'column' ] );
            }
            elseif (is_int($key) && is_string($value))
            {
                if (in_array($value, self::SELECT_MODIFIERS)) 
                {
                    $modifiers[] = $value;
                    unset($columns[$key]);
                    continue;
                }
                elseif ($value === '*')
                {
                    $stack[] = $value;
                    continue;
                }
                elseif ($value[-1] === '*' && preg_match("/(?<table>[a-zA-Z0-9_]+)\.\*/", $value, $match))
                {
                    $stack[] = $this->tableQuote($match['table']) . '.*'; 
                    continue;
                }

                preg_match('/(?<column>[a-zA-Z0-9_\.]+)(?:\s*\((?<alias>[a-zA-Z0-9_]+)\))?(?:\s*\[(?<type>(?:String|Bool|Int|Number|Object|JSON))\])?/i', $value, $match);

                if (!empty($match[ 'alias' ]))
                {
                    $stack[] = $this->columnQuote( $match[ 'column' ] ) . ' AS ' . $this->columnQuote( $match[ 'alias' ] );

                    $columns[ $key ] = $match[ 'alias' ];

                    if (!empty($match[ 'type' ]))
                    {
                        $columns[ $key ] .= ' [' . $match[ 'type' ] . ']';
                    }
                }
                else
                {
                    $stack[] = $this->columnQuote( $match[ 'column' ] );
                }
            }
        }

        return implode($modifiers, ' ') . ' ' . implode($stack, ',');
    }

    protected function columnMap($columns, &$stack)
    {
        if ($columns === '*')
        {
            return $stack;
        }

        foreach ($columns as $key => $value)
        {
            if (is_int($key) && is_string($value))
            {
                preg_match('/([a-zA-Z0-9_]+\.)?(?<column>[a-zA-Z0-9_]+)(?:\s*\((?<alias>[a-zA-Z0-9_]+)\))?(?:\s*\[(?<type>(?:String|Bool|Int|Number|Object|JSON))\])?/i', $value, $key_match);

                $column_key = !empty($key_match[ 'alias' ]) ?
                    $key_match[ 'alias' ] :
                    $key_match[ 'column' ];

                if (isset($key_match[ 'type' ]))
                {
                    $stack[ $value ] = [$column_key, $key_match[ 'type' ]];
                }
                else
                {
                    $stack[ $value ] = [$column_key, 'String'];
                }
            }
            elseif ($this->isRaw($value))
            {
                preg_match('/([a-zA-Z0-9_]+\.)?(?<column>[a-zA-Z0-9_]+)(\s*\[(?<type>(String|Bool|Int|Number))\])?/i', $key, $key_match);

                $column_key = $key_match[ 'column' ];

                if (isset($key_match[ 'type' ]))
                {
                    $stack[ $key ] = [$column_key, $key_match[ 'type' ]];
                }
                else
                {
                    $stack[ $key ] = [$column_key, 'String'];
                }
            }
            elseif (!is_int($key) && is_array($value))
            {
                $this->columnMap($value, $stack);
            }
        }

        return $stack;
    }

    public function select($table, $join, $columns = null, $where = null)
    {
        $map = [];
        $stack = [];
        $column_map = [];

        $index = 0;

        $column = $where === null ? $join : $columns;

        $is_single = (is_string($column) && $column !== '*' && $column[-1] !== '*');

        $query = $this->exec($this->selectContext($table, $map, $join, $columns, $where), $map);

        if (!$query)
        {
            return false;
        }

        if ($columns === '*' || $this->hasStar($columns))
        {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($is_single)
        {
            return $query->fetchAll(PDO::FETCH_COLUMN);
        }

        $this->columnMap($columns, $column_map);

        while ($data = $query->fetch(PDO::FETCH_ASSOC))
        {
            $current_stack = [];

            $this->dataMap($data, $columns, $column_map, $current_stack);

            $stack[ $index ] = $current_stack;

            $index++;
        }

        return $stack;
    }

    public function get($table, $join = null, $columns = null, $where = null)
    {
        $map = [];
        $stack = [];
        $column_map = [];

        if ($where === null)
        {
            $column = $join;
            $columns['LIMIT'] = [0, 1];
        }
        else
        {
            $column = $columns;
            $where['LIMIT'] = [0, 1];
        }

        $is_single = (is_string($column) && $column !== '*' && $column[-1] !== '*');

        $query = $this->exec($this->selectContext($table, $map, $join, $columns, $where), $map);

        if ($query)
        {
            $data = $query->fetchAll(PDO::FETCH_ASSOC);

            if (isset($data[ 0 ]))
            {
                if ($column === '*' || $this->hasStar($column))
                {
                    return $data[ 0 ];
                }

                $this->columnMap($columns, $column_map);

                $this->dataMap($data[ 0 ], $columns, $column_map, $stack);

                if ($is_single)
                {
                    return $stack[ $column_map[ $column ][ 0 ] ];
                }

                return $stack;
            }
        }
    }

	protected function selectContext($table, &$map, $join, &$columns = null, $where = null, $column_fn = null)
	{
		preg_match('/(?<table>[a-zA-Z0-9_]+)\s*\((?<alias>[a-zA-Z0-9_]+)\)/i', $table, $table_match);

		if (isset($table_match[ 'table' ], $table_match[ 'alias' ]))
		{
			$table = $this->tableQuote($table_match[ 'table' ]);

			$table_query = $table . ' AS ' . $this->tableQuote($table_match[ 'alias' ]);
		}
		else
		{
			$table = $this->tableQuote($table);

			$table_query = $table;
		}

		$join_key = is_array($join) ? array_keys($join) : null;

		if (
			isset($join_key[ 0 ]) &&
			strpos($join_key[ 0 ], '[') === 0
		)
		{
			$table_join = [];

			$join_array = [
				'>' => 'LEFT',
				'<' => 'RIGHT',
				'<>' => 'FULL',
				'><' => 'INNER'
			];

			foreach($join as $sub_table => $relation)
			{
				preg_match('/(\[(?<join>\<\>?|\>\<?)\])?(?<table>[a-zA-Z0-9_]+)\s?(\((?<alias>[a-zA-Z0-9_]+)\))?/', $sub_table, $match);

				if ($match[ 'join' ] !== '' && $match[ 'table' ] !== '')
				{
					if (is_string($relation))
					{
						$relation = 'USING (' . $this->columnQuote($relation) . ')';
					}

					if (is_array($relation))
					{
						// For ['column1', 'column2']
						if (isset($relation[ 0 ]))
						{
							$relation = 'USING (`' . implode($relation, '`, `') . '`)';
						}
						else
						{
							$joins = [];

							foreach ($relation as $key => $value)
							{
								$joins[] = (
									strpos($key, '.') > 0 ?
										// For ['tableB.column' => 'column']
										$this->columnQuote($key) :

										// For ['column1' => 'column2']
										$table . '.' . $this->columnQuote($key)
								) .
								' = ' .
								$this->tableQuote(isset($match[ 'alias' ]) ? $match[ 'alias' ] : $match[ 'table' ]) . '.' . $this->columnQuote($value);
							}

							$relation = 'ON ' . implode($joins, ' AND ');
						}
					}

					$table_name = $this->tableQuote($match[ 'table' ]) . ' ';

					if (isset($match[ 'alias' ]))
					{
						$table_name .= 'AS ' . $this->tableQuote($match[ 'alias' ]) . ' ';
					}

					$table_join[] = $join_array[ $match[ 'join' ] ] . ' JOIN ' . $table_name . $relation;
				}
			}

			$table_query .= ' ' . implode($table_join, ' ');
		}
		else
		{
			if (is_null($columns))
			{
				if (
					!is_null($where) ||
					(is_array($join) && isset($column_fn))
				)
				{
					$where = $join;
					$columns = null;
				}
				else
				{
					$where = null;
					$columns = $join;
				}
			}
			else
			{
				$where = $columns;
				$columns = $join;
			}
		}

		if (isset($column_fn))
		{
			if ($column_fn === 1)
			{
				$column = '1';

				if (is_null($where))
				{
					$where = $columns;
				}
			}
			elseif ($raw = $this->buildRaw($column_fn, $map))
			{
				$column = $raw;
			}
			else
			{
				if (empty($columns) || $this->isRaw($columns))
				{
					$columns = '*';
					$where = $join;
				}

				$column = $column_fn . '(' . $this->columnPush($columns, $map) . ')';
			}
		}
		else
		{
			$column = $this->columnPush($columns, $map);
		}

		return 'SELECT ' . $column . ' FROM ' . $table_query . $this->whereClause($where, $map);
	}

    protected function whereClause($where, &$map)
    {
        $lockMode = '';
        if (isset($where['LOCK'])) {
            if ($where['LOCK'] === 'SHARE') {
                $lockMode = ' LOCK IN SHARE MODE';
            } else if ($where['LOCK'] === 'UPDATE') {
                $lockMode = ' FOR UPDATE';
            }
            unset($where['LOCK']);
        }
        $where_clause = parent::whereClause($where, $map);
        return $where_clause . $lockMode;
    }

    public function begin()
    {
        $this->pdo->beginTransaction();
    }

    public function commit()
    {
        $this->pdo->commit();
    }

    public function rollBack()
    {
        $this->pdo->rollBack();
    }

    public function id()
    {
        return $this->pdo->lastInsertId();
    }

    public function getPdo()
    {
        return $this->pdo; 
    }
}
