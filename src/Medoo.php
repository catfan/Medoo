<?php
/*!
 * Medoo database framework
 * https://medoo.in
 * Version 1.6
 *
 * Copyright 2018, Angel Lai
 * Released under the MIT license
 */

namespace Medoo;

use PDO;
use Exception;
use PDOException;
use InvalidArgumentException;

class Raw {
	public $map;
	public $value;
}

class Medoo
{
	public $pdo;

	protected $type;

	protected $prefix;

	protected $statement;

	protected $dsn;

	protected $logs = [];

	protected $logging = false;

	protected $debug_mode = false;

	protected $guid = 0;

	public function __construct(array $options)
	{
		if (isset($options[ 'database_type' ]))
		{
			$this->type = strtolower($options[ 'database_type' ]);

			if ($this->type === 'mariadb')
			{
				$this->type = 'mysql';
			}
		}

		if (isset($options[ 'prefix' ]))
		{
			$this->prefix = $options[ 'prefix' ];
		}

		if (isset($options[ 'logging' ]) && is_bool($options[ 'logging' ]))
		{
			$this->logging = $options[ 'logging' ];
		}

		$option = isset($options[ 'option' ]) ? $options[ 'option' ] : [];
		$commands = (isset($options[ 'command' ]) && is_array($options[ 'command' ])) ? $options[ 'command' ] : [];

		switch ($this->type)
		{
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

			switch ($this->type)
			{
				case 'mysql':
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

					break;

				case 'pgsql':
					$attr = [
						'driver' => 'pgsql',
						'host' => $options[ 'server' ],
						'dbname' => $options[ 'database_name' ]
					];

					if ($is_port)
					{
						$attr[ 'port' ] = $port;
					}

					break;

				case 'sybase':
					$attr = [
						'driver' => 'dblib',
						'host' => $options[ 'server' ],
						'dbname' => $options[ 'database_name' ]
					];

					if ($is_port)
					{
						$attr[ 'port' ] = $port;
					}

					break;

				case 'oracle':
					$attr = [
						'driver' => 'oci',
						'dbname' => $options[ 'server' ] ?
							'//' . $options[ 'server' ] . ($is_port ? ':' . $port : ':1521') . '/' . $options[ 'database_name' ] :
							$options[ 'database_name' ]
					];

					if (isset($options[ 'charset' ]))
					{
						$attr[ 'charset' ] = $options[ 'charset' ];
					}

					break;

				case 'mssql':
					if (isset($options[ 'driver' ]) && $options[ 'driver' ] === 'dblib')
					{
						$attr = [
							'driver' => 'dblib',
							'host' => $options[ 'server' ] . ($is_port ? ':' . $port : ''),
							'dbname' => $options[ 'database_name' ]
						];

						if (isset($options[ 'appname' ]))
						{
							$attr[ 'appname' ] = $options[ 'appname' ];
						}

						if (isset($options[ 'charset' ]))
						{
							$attr[ 'charset' ] = $options[ 'charset' ];
						}
					}
					else
					{
						$attr = [
							'driver' => 'sqlsrv',
							'Server' => $options[ 'server' ] . ($is_port ? ',' . $port : ''),
							'Database' => $options[ 'database_name' ]
						];

						if (isset($options[ 'appname' ]))
						{
							$attr[ 'APP' ] = $options[ 'appname' ];
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

						foreach ($config as $value)
						{
							$keyname = strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $value));

							if (isset($options[ $keyname ]))
							{
								$attr[ $value ] = $options[ $keyname ];
							}
						}
					}

					break;

				case 'sqlite':
					$attr = [
						'driver' => 'sqlite',
						$options[ 'database_file' ]
					];

					break;
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

		if (
			in_array($this->type, ['mysql', 'pgsql', 'sybase', 'mssql']) &&
			isset($options[ 'charset' ])
		)
		{
			$commands[] = "SET NAMES '{$options[ 'charset' ]}'" . (
				$this->type === 'mysql' && isset($options[ 'collation' ]) ?
				" COLLATE '{$options[ 'collation' ]}'" : ''
			);
		}

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

	public function query($query, $map = [])
	{
		$raw = $this->raw($query, $map);

		$query = $this->buildRaw($raw, $map);

		return $this->exec($query, $map);
	}

	public function exec($query, $map = [])
	{
		if ($this->debug_mode)
		{
			echo $this->generate($query, $map);

			$this->debug_mode = false;

			return false;
		}

		if ($this->logging)
		{
			$this->logs[] = [$query, $map];
		}
		else
		{
			$this->logs = [[$query, $map]];
		}

		$statement = $this->pdo->prepare($query);

		if ($statement)
		{
			foreach ($map as $key => $value)
			{
				$statement->bindValue($key, $value[ 0 ], $value[ 1 ]);
			}

			$statement->execute();

			$this->statement = $statement;

			return $statement;
		}

		return false;
	}

	protected function generate($query, $map)
	{
		$identifier = [
			'mysql' => '`$1`',
			'mssql' => '[$1]'
		];

		$query = preg_replace(
			'/"([a-zA-Z0-9_]+)"/i',
			isset($identifier[ $this->type ]) ?  $identifier[ $this->type ] : '"$1"',
			$query
		);

		foreach ($map as $key => $value)
		{
			if ($value[ 1 ] === PDO::PARAM_STR)
			{
				$replace = $this->quote($value[ 0 ]);
			}
			elseif ($value[ 1 ] === PDO::PARAM_NULL)
			{
				$replace = 'NULL';
			}
			elseif ($value[ 1 ] === PDO::PARAM_LOB)
			{
				$replace = '{LOB_DATA}';
			}
			else
			{
				$replace = $value[ 0 ];
			}

			$query = str_replace($key, $replace, $query);
		}

		return $query;
	}

	public static function raw($string, $map = [])
	{
		$raw = new Raw();

		$raw->map = $map;
		$raw->value = $string;

		return $raw;
	}

	protected function isRaw($object)
	{
		return $object instanceof Raw;
	}

	protected function buildRaw($raw, &$map)
	{
		if (!$this->isRaw($raw))
		{
			return false;
		}

		$query = preg_replace_callback(
			'/((FROM|TABLE|INTO|UPDATE)\s*)?\<([a-zA-Z0-9_\.]+)\>/i',
			function ($matches)
			{
				if (!empty($matches[ 2 ]))
				{
					return $matches[ 2 ] . ' ' . $this->tableQuote($matches[ 3 ]);
				}

				return $this->columnQuote($matches[ 3 ]);
			},
			$raw->value);

		$raw_map = $raw->map;

		if (!empty($raw_map))
		{
			foreach ($raw_map as $key => $value)
			{
				$map[ $key ] = $this->typeMap($value, gettype($value));
			}
		}

		return $query;
	}

	public function quote($string)
	{
		return $this->pdo->quote($string);
	}

	protected function tableQuote($table)
	{
		return '"' . $this->prefix . $table . '"';
	}

	protected function mapKey()
	{
		return ':MeDoO_' . $this->guid++ . '_mEdOo';
	}

	protected function typeMap($value, $type)
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

		if ($type === 'boolean')
		{
			$value = ($value ? '1' : '0');
		}
		elseif ($type === 'NULL')
		{
			$value = null;
		}

		return [$value, $map[ $type ]];
	}

	protected function columnQuote($string)
	{
		if (strpos($string, '.') !== false)
		{
			return '"' . $this->prefix . str_replace('.', '"."', $string) . '"';
		}

		return '"' . $string . '"';
	}

	protected function columnPush(&$columns, &$map)
	{
		if ($columns === '*')
		{
			return $columns;
		}

		$stack = [];

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

		return implode($stack, ',');
	}

	protected function arrayQuote($array)
	{
		$stack = [];

		foreach ($array as $value)
		{
			$stack[] = is_int($value) ? $value : $this->pdo->quote($value);
		}

		return implode($stack, ',');
	}

	protected function innerConjunct($data, $map, $conjunctor, $outer_conjunctor)
	{
		$stack = [];

		foreach ($data as $value)
		{
			$stack[] = '(' . $this->dataImplode($value, $map, $conjunctor) . ')';
		}

		return implode($outer_conjunctor . ' ', $stack);
	}

	protected function dataImplode($data, &$map, $conjunctor)
	{
		$stack = [];

		foreach ($data as $key => $value)
		{
			$type = gettype($value);

			if (
				$type === 'array' &&
				preg_match("/^(AND|OR)(\s+#.*)?$/", $key, $relation_match)
			)
			{
				$relationship = $relation_match[ 1 ];

				$stack[] = $value !== array_keys(array_keys($value)) ?
					'(' . $this->dataImplode($value, $map, ' ' . $relationship) . ')' :
					'(' . $this->innerConjunct($value, $map, ' ' . $relationship, $conjunctor) . ')';

				continue;
			}

			$map_key = $this->mapKey();

			if (
				is_int($key) &&
				preg_match('/([a-zA-Z0-9_\.]+)\[(?<operator>\>\=?|\<\=?|\!?\=)\]([a-zA-Z0-9_\.]+)/i', $value, $match)
			)
			{
				$stack[] = $this->columnQuote($match[ 1 ]) . ' ' . $match[ 'operator' ] . ' ' . $this->columnQuote($match[ 3 ]);
			}
			else
			{
				preg_match('/([a-zA-Z0-9_\.]+)(\[(?<operator>\>\=?|\<\=?|\!|\<\>|\>\<|\!?~|REGEXP)\])?/i', $key, $match);
				$column = $this->columnQuote($match[ 1 ]);

				if (isset($match[ 'operator' ]))
				{
					$operator = $match[ 'operator' ];

					if (in_array($operator, ['>', '>=', '<', '<=']))
					{
						$condition = $column . ' ' . $operator . ' ';

						if (is_numeric($value))
						{
							$condition .= $map_key;
							$map[ $map_key ] = [$value, PDO::PARAM_INT];
						}
						elseif ($raw = $this->buildRaw($value, $map))
						{
							$condition .= $raw;
						}
						else
						{
							$condition .= $map_key;
							$map[ $map_key ] = [$value, PDO::PARAM_STR];
						}

						$stack[] = $condition;
					}
					elseif ($operator === '!')
					{
						switch ($type)
						{
							case 'NULL':
								$stack[] = $column . ' IS NOT NULL';
								break;

							case 'array':
								$placeholders = [];

								foreach ($value as $index => $item)
								{
									$placeholders[] = $map_key . $index . '_i';
									$map[ $map_key . $index . '_i' ] = $this->typeMap($item, gettype($item));
								}

								$stack[] = $column . ' NOT IN (' . implode(', ', $placeholders) . ')';
								break;

							case 'object':
								if ($raw = $this->buildRaw($value, $map))
								{
									$stack[] = $column . ' != ' . $raw;
								}
								break;

							case 'integer':
							case 'double':
							case 'boolean':
							case 'string':
								$stack[] = $column . ' != ' . $map_key;
								$map[ $map_key ] = $this->typeMap($value, $type);
								break;
						}
					}
					elseif ($operator === '~' || $operator === '!~')
					{
						if ($type !== 'array')
						{
							$value = [ $value ];
						}

						$connector = ' OR ';
						$data = array_values($value);

						if (is_array($data[ 0 ]))
						{
							if (isset($value[ 'AND' ]) || isset($value[ 'OR' ]))
							{
								$connector = ' ' . array_keys($value)[ 0 ] . ' ';
								$value = $data[ 0 ];
							}
						}

						$like_clauses = [];

						foreach ($value as $index => $item)
						{
							$item = strval($item);

							if (!preg_match('/(\[.+\]|_|%.+|.+%)/', $item))
							{
								$item = '%' . $item . '%';
							}

							$like_clauses[] = $column . ($operator === '!~' ? ' NOT' : '') . ' LIKE ' . $map_key . 'L' . $index;
							$map[ $map_key . 'L' . $index ] = [$item, PDO::PARAM_STR];
						}

						$stack[] = '(' . implode($connector, $like_clauses) . ')';
					}
					elseif ($operator === '<>' || $operator === '><')
					{
						if ($type === 'array')
						{
							if ($operator === '><')
							{
								$column .= ' NOT';
							}

							$stack[] = '(' . $column . ' BETWEEN ' . $map_key . 'a AND ' . $map_key . 'b)';

							$data_type = (is_numeric($value[ 0 ]) && is_numeric($value[ 1 ])) ? PDO::PARAM_INT : PDO::PARAM_STR;

							$map[ $map_key . 'a' ] = [$value[ 0 ], $data_type];
							$map[ $map_key . 'b' ] = [$value[ 1 ], $data_type];
						}
					}
					elseif ($operator === 'REGEXP')
					{
						$stack[] = $column . ' REGEXP ' . $map_key;
						$map[ $map_key ] = [$value, PDO::PARAM_STR];
					}
				}
				else
				{
					switch ($type)
					{
						case 'NULL':
							$stack[] = $column . ' IS NULL';
							break;

						case 'array':
							$placeholders = [];

							foreach ($value as $index => $item)
							{
								$placeholders[] = $map_key . $index . '_i';
								$map[ $map_key . $index . '_i' ] = $this->typeMap($item, gettype($item));
							}

							$stack[] = $column . ' IN (' . implode(', ', $placeholders) . ')';
							break;

						case 'object':
							if ($raw = $this->buildRaw($value, $map))
							{
								$stack[] = $column . ' = ' . $raw;
							}
							break;

						case 'integer':
						case 'double':
						case 'boolean':
						case 'string':
							$stack[] = $column . ' = ' . $map_key;
							$map[ $map_key ] = $this->typeMap($value, $type);
							break;
					}
				}
			}
		}

		return implode($conjunctor . ' ', $stack);
	}

	protected function whereClause($where, &$map)
	{
		$where_clause = '';

		if (is_array($where))
		{
			$where_keys = array_keys($where);

			$conditions = array_diff_key($where, array_flip(
				['GROUP', 'ORDER', 'HAVING', 'LIMIT', 'LIKE', 'MATCH']
			));

			if (!empty($conditions))
			{
				$where_clause = ' WHERE ' . $this->dataImplode($conditions, $map, ' AND');
			}

			if (isset($where[ 'MATCH' ]) && $this->type === 'mysql')
			{
				$MATCH = $where[ 'MATCH' ];

				if (is_array($MATCH) && isset($MATCH[ 'columns' ], $MATCH[ 'keyword' ]))
				{
					$mode = '';

					$mode_array = [
						'natural' => 'IN NATURAL LANGUAGE MODE',
						'natural+query' => 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION',
						'boolean' => 'IN BOOLEAN MODE',
						'query' => 'WITH QUERY EXPANSION'
					];

					if (isset($MATCH[ 'mode' ], $mode_array[ $MATCH[ 'mode' ] ]))
					{
						$mode = ' ' . $mode_array[ $MATCH[ 'mode' ] ];
					}

					$columns = implode(array_map([$this, 'columnQuote'], $MATCH[ 'columns' ]), ', ');
					$map_key = $this->mapKey();
					$map[ $map_key ] = [$MATCH[ 'keyword' ], PDO::PARAM_STR];

					$where_clause .= ($where_clause !== '' ? ' AND ' : ' WHERE') . ' MATCH (' . $columns . ') AGAINST (' . $map_key . $mode . ')';
				}
			}

			if (isset($where[ 'GROUP' ]))
			{
				$GROUP = $where[ 'GROUP' ];

				if (is_array($GROUP))
				{
					$stack = [];

					foreach ($GROUP as $column => $value)
					{
						$stack[] = $this->columnQuote($value);
					}

					$where_clause .= ' GROUP BY ' . implode($stack, ',');
				}
				elseif ($raw = $this->buildRaw($GROUP, $map))
				{
					$where_clause .= ' GROUP BY ' . $raw;
				}
				else
				{
					$where_clause .= ' GROUP BY ' . $this->columnQuote($GROUP);
				}

				if (isset($where[ 'HAVING' ]))
				{
					if ($raw = $this->buildRaw($where[ 'HAVING' ], $map))
					{
						$where_clause .= ' HAVING ' . $raw;
					}
					else
					{
						$where_clause .= ' HAVING ' . $this->dataImplode($where[ 'HAVING' ], $map, ' AND');
					}
				}
			}

			if (isset($where[ 'ORDER' ]))
			{
				$ORDER = $where[ 'ORDER' ];

				if (is_array($ORDER))
				{
					$stack = [];

					foreach ($ORDER as $column => $value)
					{
						if (is_array($value))
						{
							$stack[] = 'FIELD(' . $this->columnQuote($column) . ', ' . $this->arrayQuote($value) . ')';
						}
						elseif ($value === 'ASC' || $value === 'DESC')
						{
							$stack[] = $this->columnQuote($column) . ' ' . $value;
						}
						elseif (is_int($column))
						{
							$stack[] = $this->columnQuote($value);
						}
					}

					$where_clause .= ' ORDER BY ' . implode($stack, ',');
				}
				elseif ($raw = $this->buildRaw($ORDER, $map))
				{
					$where_clause .= ' ORDER BY ' . $raw;	
				}
				else
				{
					$where_clause .= ' ORDER BY ' . $this->columnQuote($ORDER);
				}

				if (
					isset($where[ 'LIMIT' ]) &&
					in_array($this->type, ['oracle', 'mssql'])
				)
				{
					$LIMIT = $where[ 'LIMIT' ];

					if (is_numeric($LIMIT))
					{
						$LIMIT = [0, $LIMIT];
					}
					
					if (
						is_array($LIMIT) &&
						is_numeric($LIMIT[ 0 ]) &&
						is_numeric($LIMIT[ 1 ])
					)
					{
						$where_clause .= ' OFFSET ' . $LIMIT[ 0 ] . ' ROWS FETCH NEXT ' . $LIMIT[ 1 ] . ' ROWS ONLY';
					}
				}
			}

			if (isset($where[ 'LIMIT' ]) && !in_array($this->type, ['oracle', 'mssql']))
			{
				$LIMIT = $where[ 'LIMIT' ];

				if (is_numeric($LIMIT))
				{
					$where_clause .= ' LIMIT ' . $LIMIT;
				}
				elseif (
					is_array($LIMIT) &&
					is_numeric($LIMIT[ 0 ]) &&
					is_numeric($LIMIT[ 1 ])
				)
				{
					$where_clause .= ' LIMIT ' . $LIMIT[ 1 ] . ' OFFSET ' . $LIMIT[ 0 ];
				}
			}
		}
		elseif ($raw = $this->buildRaw($where, $map))
		{
			$where_clause .= ' ' . $raw;
		}

		return $where_clause;
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
						$relation = 'USING ("' . $relation . '")';
					}

					if (is_array($relation))
					{
						// For ['column1', 'column2']
						if (isset($relation[ 0 ]))
						{
							$relation = 'USING ("' . implode($relation, '", "') . '")';
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
										$table . '."' . $key . '"'
								) .
								' = ' .
								$this->tableQuote(isset($match[ 'alias' ]) ? $match[ 'alias' ] : $match[ 'table' ]) . '."' . $value . '"';
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

	protected function columnMap($columns, &$stack)
	{
		if ($columns === '*')
		{
			return $stack;
		}

		foreach ($columns as $key => $value)
		{
			if (is_int($key))
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

	protected function dataMap($data, $columns, $column_map, &$stack)
	{
		foreach ($columns as $key => $value)
		{
			$isRaw = $this->isRaw($value);

			if (is_int($key) || $isRaw)
			{
				$map = $column_map[ $isRaw ? $key : $value ];

				$column_key = $map[ 0 ];

				$result = $data[ $column_key ];

				if (isset($map[ 1 ]))
				{
					if ($isRaw && in_array($map[ 1 ], ['Object', 'JSON']))
					{
						continue;
					}

					if (is_null($result))
					{
						$stack[ $column_key ] = null;
						continue;
					}

					switch ($map[ 1 ])
					{
						case 'Number':
							$stack[ $column_key ] = (double) $result;
							break;

						case 'Int':
							$stack[ $column_key ] = (int) $result;
							break;

						case 'Bool':
							$stack[ $column_key ] = (bool) $result;
							break;

						case 'Object':
							$stack[ $column_key ] = unserialize($result);
							break;

						case 'JSON':
							$stack[ $column_key ] = json_decode($result, true);
							break;

						case 'String':
							$stack[ $column_key ] = $result;
							break;
					}
				}
				else
				{
					$stack[ $column_key ] = $result;
				}
			}
			else
			{
				$current_stack = [];

				$this->dataMap($data, $value, $column_map, $current_stack);

				$stack[ $key ] = $current_stack;
			}
		}
	}

	public function select($table, $join, $columns = null, $where = null)
	{
		$map = [];
		$stack = [];
		$column_map = [];

		$index = 0;

		$column = $where === null ? $join : $columns;

		$is_single = (is_string($column) && $column !== '*');

		$query = $this->exec($this->selectContext($table, $map, $join, $columns, $where), $map);

		$this->columnMap($columns, $column_map);

		if (!$query)
		{
			return false;
		}

		if ($columns === '*')
		{
			return $query->fetchAll(PDO::FETCH_ASSOC);
		}

		if ($is_single)
		{
			return $query->fetchAll(PDO::FETCH_COLUMN);
		}

		while ($data = $query->fetch(PDO::FETCH_ASSOC))
		{
			$current_stack = [];

			$this->dataMap($data, $columns, $column_map, $current_stack);

			$stack[ $index ] = $current_stack;

			$index++;
		}

		return $stack;
	}

	public function insert($table, $datas)
	{
		$stack = [];
		$columns = [];
		$fields = [];
		$map = [];

		if (!isset($datas[ 0 ]))
		{
			$datas = [$datas];
		}

		foreach ($datas as $data)
		{
			foreach ($data as $key => $value)
			{
				$columns[] = $key;
			}
		}

		$columns = array_unique($columns);

		foreach ($datas as $data)
		{
			$values = [];

			foreach ($columns as $key)
			{
				if ($raw = $this->buildRaw($data[ $key ], $map))
				{
					$values[] = $raw;
					continue;
				}

				$map_key =$this->mapKey();

				$values[] = $map_key;

				if (!isset($data[ $key ]))
				{
					$map[ $map_key ] = [null, PDO::PARAM_NULL];
				}
				else
				{
					$value = $data[ $key ];

					$type = gettype($value);

					switch ($type)
					{
						case 'array':
							$map[ $map_key ] = [
								strpos($key, '[JSON]') === strlen($key) - 6 ?
									json_encode($value) :
									serialize($value),
								PDO::PARAM_STR
							];
							break;

						case 'object':
							$value = serialize($value);

						case 'NULL':
						case 'resource':
						case 'boolean':
						case 'integer':
						case 'double':
						case 'string':
							$map[ $map_key ] = $this->typeMap($value, $type);
							break;
					}
				}
			}

			$stack[] = '(' . implode($values, ', ') . ')';
		}

		foreach ($columns as $key)
		{
			$fields[] = $this->columnQuote(preg_replace("/(\s*\[JSON\]$)/i", '', $key));
		}

		return $this->exec('INSERT INTO ' . $this->tableQuote($table) . ' (' . implode(', ', $fields) . ') VALUES ' . implode(', ', $stack), $map);
	}

	public function update($table, $data, $where = null)
	{
		$fields = [];
		$map = [];

		foreach ($data as $key => $value)
		{
			$column = $this->columnQuote(preg_replace("/(\s*\[(JSON|\+|\-|\*|\/)\]$)/i", '', $key));

			if ($raw = $this->buildRaw($value, $map))
			{
				$fields[] = $column . ' = ' . $raw;
				continue;
			}

			$map_key = $this->mapKey();

			preg_match('/(?<column>[a-zA-Z0-9_]+)(\[(?<operator>\+|\-|\*|\/)\])?/i', $key, $match);

			if (isset($match[ 'operator' ]))
			{
				if (is_numeric($value))
				{
					$fields[] = $column . ' = ' . $column . ' ' . $match[ 'operator' ] . ' ' . $value;
				}
			}
			else
			{
				$fields[] = $column . ' = ' . $map_key;

				$type = gettype($value);

				switch ($type)
				{
					case 'array':
						$map[ $map_key ] = [
							strpos($key, '[JSON]') === strlen($key) - 6 ?
								json_encode($value) :
								serialize($value),
							PDO::PARAM_STR
						];
						break;

					case 'object':
						$value = serialize($value);

					case 'NULL':
					case 'resource':
					case 'boolean':
					case 'integer':
					case 'double':
					case 'string':
						$map[ $map_key ] = $this->typeMap($value, $type);
						break;
				}
			}
		}

		return $this->exec('UPDATE ' . $this->tableQuote($table) . ' SET ' . implode(', ', $fields) . $this->whereClause($where, $map), $map);
	}

	public function delete($table, $where)
	{
		$map = [];

		return $this->exec('DELETE FROM ' . $this->tableQuote($table) . $this->whereClause($where, $map), $map);
	}

	public function replace($table, $columns, $where = null)
	{
		if (!is_array($columns) || empty($columns))
		{
			return false;
		}

		$map = [];
		$stack = [];

		foreach ($columns as $column => $replacements)
		{
			if (is_array($replacements))
			{
				foreach ($replacements as $old => $new)
				{
					$map_key = $this->mapKey();

					$stack[] = $this->columnQuote($column) . ' = REPLACE(' . $this->columnQuote($column) . ', ' . $map_key . 'a, ' . $map_key . 'b)';

					$map[ $map_key . 'a' ] = [$old, PDO::PARAM_STR];
					$map[ $map_key . 'b' ] = [$new, PDO::PARAM_STR];
				}
			}
		}

		if (!empty($stack))
		{
			return $this->exec('UPDATE ' . $this->tableQuote($table) . ' SET ' . implode(', ', $stack) . $this->whereClause($where, $map), $map);
		}

		return false;
	}

	public function get($table, $join = null, $columns = null, $where = null)
	{
		$map = [];
		$stack = [];
		$column_map = [];

		if ($where === null)
		{
			$column = $join;
			unset($columns[ 'LIMIT' ]);
		}
		else
		{
			$column = $columns;
			unset($where[ 'LIMIT' ]);
		}

		$is_single = (is_string($column) && $column !== '*');

		$query = $this->exec($this->selectContext($table, $map, $join, $columns, $where) . ' LIMIT 1', $map);

		if ($query)
		{
			$data = $query->fetchAll(PDO::FETCH_ASSOC);

			if (isset($data[ 0 ]))
			{
				if ($column === '*')
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

	public function has($table, $join, $where = null)
	{
		$map = [];
		$column = null;

		$query = $this->exec('SELECT EXISTS(' . $this->selectContext($table, $map, $join, $column, $where, 1) . ')', $map);

		if ($query)
		{
			$result = $query->fetchColumn();

			return $result === '1' || $result === true;
		}

		return false;
	}

	public function rand($table, $join = null, $columns = null, $where = null)
	{
		$type = $this->type;

		$order = 'RANDOM()';

		if ($type === 'mysql')
		{
			$order = 'RAND()';
		}
		elseif ($type === 'mssql')
		{
			$order = 'NEWID()';
		}

		$order_raw = $this->raw($order);

		if ($where === null)
		{
			if ($columns === null)
			{
				$columns = [
					'ORDER'  => $order_raw
				];
			}
			else
			{
				$column = $join;
				unset($columns[ 'ORDER' ]);

				$columns[ 'ORDER' ] = $order_raw;
			}
		}
		else
		{
			unset($where[ 'ORDER' ]);

			$where[ 'ORDER' ] = $order_raw;
		}

		return $this->select($table, $join, $columns, $where);
	}

	private function aggregate($type, $table, $join = null, $column = null, $where = null)
	{
		$map = [];

		$query = $this->exec($this->selectContext($table, $map, $join, $column, $where, strtoupper($type)), $map);

		if ($query)
		{
			$number = $query->fetchColumn();

			return is_numeric($number) ? $number + 0 : $number;
		}

		return false;
	}

	public function count($table, $join = null, $column = null, $where = null)
	{
		return $this->aggregate('count', $table, $join, $column, $where);
	}

	public function avg($table, $join, $column = null, $where = null)
	{
		return $this->aggregate('avg', $table, $join, $column, $where);
	}

	public function max($table, $join, $column = null, $where = null)
	{
		return $this->aggregate('max', $table, $join, $column, $where);
	}

	public function min($table, $join, $column = null, $where = null)
	{
		return $this->aggregate('min', $table, $join, $column, $where);
	}

	public function sum($table, $join, $column = null, $where = null)
	{
		return $this->aggregate('sum', $table, $join, $column, $where);
	}

	public function action($actions)
	{
		if (is_callable($actions))
		{
			$this->pdo->beginTransaction();

			try {
				$result = $actions($this);

				if ($result === false)
				{
					$this->pdo->rollBack();
				}
				else
				{
					$this->pdo->commit();
				}
			}
			catch (Exception $e) {
				$this->pdo->rollBack();

				throw $e;
			}

			return $result;
		}

		return false;
	}

	public function id()
	{
		$type = $this->type;

		if ($type === 'oracle')
		{
			return 0;
		}
		elseif ($type === 'mssql')
		{
			return $this->pdo->query('SELECT SCOPE_IDENTITY()')->fetchColumn();
		}
		elseif ($type === 'pgsql')
		{
			return $this->pdo->query('SELECT LASTVAL()')->fetchColumn();
		}

		return $this->pdo->lastInsertId();
	}

	public function debug()
	{
		$this->debug_mode = true;

		return $this;
	}

	public function error()
	{
		return $this->statement ? $this->statement->errorInfo() : null;
	}

	public function last()
	{
		$log = end($this->logs);

		return $this->generate($log[ 0 ], $log[ 1 ]);
	}

	public function log()
	{
		return array_map(function ($log)
			{
				return $this->generate($log[ 0 ], $log[ 1 ]);
			},
			$this->logs
		);
	}

	public function info()
	{
		$output = [
			'server' => 'SERVER_INFO',
			'driver' => 'DRIVER_NAME',
			'client' => 'CLIENT_VERSION',
			'version' => 'SERVER_VERSION',
			'connection' => 'CONNECTION_STATUS'
		];

		foreach ($output as $key => $value)
		{
			$output[ $key ] = @$this->pdo->getAttribute(constant('PDO::ATTR_' . $value));
		}

		$output[ 'dsn' ] = $this->dsn;

		return $output;
	}
}