<?php

namespace Medoo;

/*!
 * Medoo database framework
 * http://medoo.in
 * Version 1.0.2
 *
 * Copyright 2016, Angel Lai
 * Released under the MIT license
 */
class Medoo
{
    // General
    protected $database_type;

    protected $charset;

    protected $database_name;

    // For MySQL, MariaDB, MSSQL, Sybase, PostgreSQL, Oracle
    protected $server;

    protected $username;

    protected $password;

    // For SQLite
    protected $database_file;

    // For MySQL or MariaDB with unix_socket
    protected $socket;

    // Optional
    protected $port;

    protected $prefix;

    protected $option = array();

    // Variable
    protected $logs = array();

    protected $debug_mode = false;

    public function __construct($options = null)
    {
        try {
            $commands = array();
            $dsn = '';

            if (is_array($options)) {
                foreach ($options as $option => $value) {
                    $this->$option = $value;
                }
            } else {
                return false;
            }

            if (isset($this->port) && is_int($this->port * 1)) {
                $port = $this->port;
            }

            $type = strtolower($this->database_type);
            $is_port = isset($port);

            if (isset($options[ 'prefix' ])) {
                $this->prefix = $options[ 'prefix' ];
            }

            switch ($type) {
                case 'mariadb':
                    $type = 'mysql';

                case 'mysql':
                    if ($this->socket) {
                        $dsn = $type.':unix_socket='.$this->socket.';dbname='.$this->database_name;
                    } else {
                        $dsn = $type.':host='.$this->server.($is_port ? ';port='.$port : '').';dbname='.$this->database_name;
                    }

                    // Make MySQL using standard quoted identifier
                    $commands[] = 'SET SQL_MODE=ANSI_QUOTES';
                    break;

                case 'pgsql':
                    $dsn = $type.':host='.$this->server.($is_port ? ';port='.$port : '').';dbname='.$this->database_name;
                    break;

                case 'sybase':
                    $dsn = 'dblib:host='.$this->server.($is_port ? ':'.$port : '').';dbname='.$this->database_name;
                    break;

                case 'oracle':
                    $dbname = $this->server ?
                        '//'.$this->server.($is_port ? ':'.$port : ':1521').'/'.$this->database_name :
                        $this->database_name;

                    $dsn = 'oci:dbname='.$dbname.($this->charset ? ';charset='.$this->charset : '');
                    break;

                case 'mssql':
                    $dsn = strstr(PHP_OS, 'WIN') ?
                        'sqlsrv:server='.$this->server.($is_port ? ','.$port : '').';database='.$this->database_name :
                        'dblib:host='.$this->server.($is_port ? ':'.$port : '').';dbname='.$this->database_name;

                    // Keep MSSQL QUOTED_IDENTIFIER is ON for standard quoting
                    $commands[] = 'SET QUOTED_IDENTIFIER ON';
                    break;

                case 'sqlite':
                    $dsn = $type.':'.$this->database_file;
                    $this->username = null;
                    $this->password = null;
                    break;
            }

            if (in_array($type, explode(' ', 'mariadb mysql pgsql sybase mssql')) && $this->charset) {
                $commands[] = "SET NAMES '".$this->charset."'";
            }

            $this->pdo = new PDO(
                $dsn,
                $this->username,
                $this->password,
                $this->option
            );

            foreach ($commands as $value) {
                $this->pdo->exec($value);
            }
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function query($query)
    {
        if ($this->debug_mode) {
            echo $query;

            $this->debug_mode = false;

            return false;
        }

        array_push($this->logs, $query);

        return $this->pdo->query($query);
    }

    public function exec($query)
    {
        if ($this->debug_mode) {
            echo $query;

            $this->debug_mode = false;

            return false;
        }

        array_push($this->logs, $query);

        return $this->pdo->exec($query);
    }

    public function quote($string)
    {
        return $this->pdo->quote($string);
    }

    protected function columnQuote($string)
    {
        if (strstr($string, '.')) {
            return '`'.$this->prefix.str_replace('.', '`.`', preg_replace('/(^#|\(JSON\)\s*)/', '', $string)).'`';
        } else {
            return '`'.str_replace('.', '`.`', preg_replace('/(^#|\(JSON\)\s*)/', '', $string)).'`';
        }
    }

    protected function columnPush($columns)
    {
        if ($columns == '*') {
            return $columns;
        }

        if (is_string($columns)) {
            $columns = array($columns);
        }

        $stack = array();

        foreach ($columns as $key => $value) {
            preg_match('/([a-zA-Z0-9_\-\.]*)\s*\(([a-zA-Z0-9_\-]*)\)/i', $value, $match);

            if (isset($match[ 1 ], $match[ 2 ])) {
                $column = $this->columnQuote($match[ 1 ]).' AS '.$this->columnQuote($match[ 2 ]);
            } else {
                $column = $this->columnQuote($value);
            }
            if (!is_int($key)) {
                $column = $key.'('.$column.')';
            }
            array_push($stack, $column);
        }

        return implode($stack, ',');
    }

    protected function arrayQuote($array)
    {
        $temp = array();

        foreach ($array as $value) {
            $temp[] = is_int($value) ? $value : $this->pdo->quote($value);
        }

        return implode($temp, ',');
    }

    protected function innerConjunct($data, $conjunctor, $outer_conjunctor)
    {
        $haystack = array();

        foreach ($data as $value) {
            $haystack[] = '('.$this->dataImplode($value, $conjunctor).')';
        }

        return implode($outer_conjunctor.' ', $haystack);
    }

    protected function fnQuote($column, $string)
    {
        return (strpos($column, '#') === 0 && preg_match('/^[A-Z0-9\_]*\([^)]*\)$/', $string)) ?

            $string :

            $this->quote($string);
    }

    protected function dataImplode($data, $conjunctor, $outer_conjunctor = null)
    {
        $wheres = array();

        foreach ($data as $key => $value) {
            $type = gettype($value);

            if (preg_match("/^(AND|OR)(\s+#.*)?$/i", $key, $relation_match) && $type == 'array') {
                $wheres[] = 0 !== count(array_diff_key($value, array_keys(array_keys($value)))) ?
                    '('.$this->dataImplode($value, ' '.$relation_match[ 1 ]).')' :
                    '('.$this->innerConjunct($value, ' '.$relation_match[ 1 ], $conjunctor).')';
            } else {
                preg_match('/(#?)([\w\.\-]+)(\[(\>|\>\=|\<|\<\=|\!|\<\>|\>\<|\!?~)\])?/i', $key, $match);
                $column = $this->columnQuote($match[ 2 ]);

                if (isset($match[ 4 ])) {
                    $operator = $match[ 4 ];

                    if ($operator == '!') {
                        switch ($type) {
                            case 'NULL':
                                $wheres[] = $column.' IS NOT NULL';
                                break;

                            case 'array':
                                $wheres[] = $column.' NOT IN ('.$this->arrayQuote($value).')';
                                break;

                            case 'integer':
                            case 'double':
                                $wheres[] = $column.' != '.$value;
                                break;

                            case 'boolean':
                                $wheres[] = $column.' != '.($value ? '1' : '0');
                                break;

                            case 'string':
                                $wheres[] = $column.' != '.$this->fnQuote($key, $value);
                                break;
                        }
                    }

                    if ($operator == '<>' || $operator == '><') {
                        if ($type == 'array') {
                            if ($operator == '><') {
                                $column .= ' NOT';
                            }

                            if (is_numeric($value[ 0 ]) && is_numeric($value[ 1 ])) {
                                $wheres[] = '('.$column.' BETWEEN '.$value[ 0 ].' AND '.$value[ 1 ].')';
                            } else {
                                $wheres[] = '('.$column.' BETWEEN '.$this->quote($value[ 0 ]).' AND '.$this->quote($value[ 1 ]).')';
                            }
                        }
                    }

                    if ($operator == '~' || $operator == '!~') {
                        if ($type != 'array') {
                            $value = array($value);
                        }

                        $like_clauses = array();

                        foreach ($value as $item) {
                            $item = strval($item);
                            $suffix = mb_substr($item, -1, 1);

                            if ($suffix === '_') {
                                $item = substr_replace($item, '%', -1);
                            } elseif ($suffix === '%') {
                                $item = '%'.substr_replace($item, '', -1, 1);
                            } elseif (preg_match('/^(?!%).+(?<!%)$/', $item)) {
                                $item = '%'.$item.'%';
                            }

                            $like_clauses[] = $column.($operator === '!~' ? ' NOT' : '').' LIKE '.$this->fnQuote($key, $item);
                        }

                        $wheres[] = implode(' OR ', $like_clauses);
                    }

                    if (in_array($operator, array('>', '>=', '<', '<='))) {
                        if (is_numeric($value)) {
                            $wheres[] = $column.' '.$operator.' '.$value;
                        } elseif (strpos($key, '#') === 0) {
                            $wheres[] = $column.' '.$operator.' '.$this->fnQuote($key, $value);
                        } else {
                            $wheres[] = $column.' '.$operator.' '.$this->quote($value);
                        }
                    }
                } else {
                    switch ($type) {
                        case 'NULL':
                            $wheres[] = $column.' IS NULL';
                            break;

                        case 'array':
                            $wheres[] = $column.' IN ('.$this->arrayQuote($value).')';
                            break;

                        case 'integer':
                        case 'double':
                            $wheres[] = $column.' = '.$value;
                            break;

                        case 'boolean':
                            $wheres[] = $column.' = '.($value ? '1' : '0');
                            break;

                        case 'string':
                            $wheres[] = $column.' = '.$this->fnQuote($key, $value);
                            break;
                    }
                }
            }
        }

        return implode($conjunctor.' ', $wheres);
    }

    protected function whereClause($where)
    {
        $where_clause = '';

        if (is_array($where)) {
            $where_keys = array_keys($where);
            $where_AND = preg_grep("/^AND\s*#?$/i", $where_keys);
            $where_OR = preg_grep("/^OR\s*#?$/i", $where_keys);

            $single_condition = array_diff_key($where, array_flip(
                explode(' ', 'AND OR GROUP ORDER HAVING LIMIT LIKE MATCH')
            ));

            if ($single_condition != array()) {
                $condition = $this->dataImplode($single_condition, '');

                if ($condition != '') {
                    $where_clause = ' WHERE '.$condition;
                }
            }

            if (!empty($where_AND)) {
                $value = array_values($where_AND);
                $where_clause = ' WHERE '.$this->dataImplode($where[ $value[ 0 ] ], ' AND');
            }

            if (!empty($where_OR)) {
                $value = array_values($where_OR);
                $where_clause = ' WHERE '.$this->dataImplode($where[ $value[ 0 ] ], ' OR');
            }

            if (isset($where[ 'MATCH' ])) {
                $MATCH = $where[ 'MATCH' ];

                if (is_array($MATCH) && isset($MATCH[ 'columns' ], $MATCH[ 'keyword' ])) {
                    $where_clause .= ($where_clause != '' ? ' AND ' : ' WHERE ').' MATCH ("'.str_replace('.', '"."', implode($MATCH[ 'columns' ], '", "')).'") AGAINST ('.$this->quote($MATCH[ 'keyword' ]).')';
                }
            }

            if (isset($where[ 'GROUP' ])) {
                $where_clause .= ' GROUP BY '.$this->columnQuote($where[ 'GROUP' ]);

                if (isset($where[ 'HAVING' ])) {
                    $where_clause .= ' HAVING '.$this->dataImplode($where[ 'HAVING' ], ' AND');
                }
            }

            if (isset($where[ 'ORDER' ])) {
                $rsort = '/(^[a-zA-Z0-9_\-\.]*)(\s*(DESC|ASC|desc|asc))?/';
                $ORDER = $where[ 'ORDER' ];

                if (is_array($ORDER)) {
                    if (isset($ORDER[ 1 ]) && is_array($ORDER[ 1 ])) {
                        $where_clause .= ' ORDER BY FIELD('.$this->columnQuote($ORDER[ 0 ]).', '.$this->arrayQuote($ORDER[ 1 ]).')';
                    } else {
                        $stack = array();

                        foreach ($ORDER as $column) {
                            preg_match($rsort, $column, $order_match);

                            array_push($stack, '"'.str_replace('.', '"."', $order_match[ 1 ]).'"'.(isset($order_match[ 3 ]) ? ' '.$order_match[ 3 ] : ''));
                        }

                        $where_clause .= ' ORDER BY '.implode($stack, ',');
                    }
                } else {
                    preg_match($rsort, $ORDER, $order_match);

                    $where_clause .= ' ORDER BY "'.str_replace('.', '"."', $order_match[ 1 ]).'"'.(isset($order_match[ 3 ]) ? ' '.$order_match[ 3 ] : '');
                }
            }

            if (isset($where[ 'LIMIT' ])) {
                $LIMIT = $where[ 'LIMIT' ];

                if (is_numeric($LIMIT)) {
                    $where_clause .= ' LIMIT '.$LIMIT;
                }

                if (is_array($LIMIT) && is_numeric($LIMIT[ 0 ]) && is_numeric($LIMIT[ 1 ])) {
                    if ($this->database_type === 'pgsql') {
                        $where_clause .= ' OFFSET '.$LIMIT[ 0 ].' LIMIT '.$LIMIT[ 1 ];
                    } else {
                        $where_clause .= ' LIMIT '.$LIMIT[ 0 ].','.$LIMIT[ 1 ];
                    }
                }
            }
        } else {
            if ($where != null) {
                $where_clause .= ' '.$where;
            }
        }

        return $where_clause;
    }

    protected function selectContext($table, $join, &$columns = null, $where = null, $column_fn = null)
    {
        $table = '`'.$this->prefix.$table.'`';
        $join_key = is_array($join) ? array_keys($join) : null;

        if (isset($join_key[ 0 ]) && strpos($join_key[ 0 ], '[') === 0) {
            $table_join = array();

            $join_array = array(
                '>' => 'LEFT',
                '<' => 'RIGHT',
                '<>' => 'FULL',
                '><' => 'INNER',
            );

            foreach ($join as $sub_table => $relation) {
                preg_match('/(\[(\<|\>|\>\<|\<\>)\])?([a-zA-Z0-9_\-]*)\s?(\(([a-zA-Z0-9_\-]*)\))?/', $sub_table, $match);

                if ($match[ 2 ] != '' && $match[ 3 ] != '') {
                    if (is_string($relation)) {
                        $relation = 'USING ("'.$relation.'")';
                    }

                    if (is_array($relation)) {
                        // For ['column1', 'column2']
                        if (isset($relation[ 0 ])) {
                            $relation = 'USING ("'.implode($relation, '", "').'")';
                        } else {
                            $joins = array();

                            foreach ($relation as $key => $value) {
                                $joins[] = (
                                    strpos($key, '.') > 0 ?
                                        // For ['tableB.column' => 'column']
                                        '`'.str_replace('.', '`.`', $key).'`' :

                                        // For ['column1' => 'column2']
                                        $table.'.`'.$key.'`'
                                ).
                                ' = '.
                                '`'.$this->prefix.(isset($match[ 5 ]) ? $match[ 5 ] : $match[ 3 ]).'`.`'.$value.'`';
                            }
                            $relation = 'ON '.implode($joins, ' AND ');
                        }
                    }

                    $table_join[] = $join_array[ $match[ 2 ] ].' JOIN `'.$this->prefix.$match[ 3 ].'` '.(isset($match[ 5 ]) ?  'AS `'.$match[ 5 ].'` ' : '').$relation;
                }
            }

            $table .= ' '.implode($table_join, ' ');
        } else {
            if (is_null($columns)) {
                if (is_null($where)) {
                    if (is_array($join) && isset($column_fn)) {
                        $where = $join;
                        $columns = null;
                    } else {
                        $where = null;
                        $columns = $join;
                    }
                } else {
                    $where = $join;
                    $columns = null;
                }
            } else {
                $where = $columns;
                $columns = $join;
            }
        }

        if (isset($column_fn)) {
            if ($column_fn == 1) {
                $column = '1';

                if (is_null($where)) {
                    $where = $columns;
                }
            } else {
                if (empty($columns)) {
                    $columns = '*';
                    $where = $join;
                }

                $column = $column_fn.'('.$this->columnPush($columns).')';
            }
        } else {
            $column = $this->columnPush($columns);
        }

        return 'SELECT '.$column.' FROM '.$table.$this->whereClause($where);
    }

    public function select($table, $join, $columns = null, $where = null)
    {
        $query = $this->query($this->selectContext($table, $join, $columns, $where));

        return $query ? $query->fetchAll(
            (is_string($columns) && $columns != '*') ? PDO::FETCH_COLUMN : PDO::FETCH_ASSOC
        ) : false;
    }

    public function insert($table, $datas)
    {
        $fields = array();
        $columns = $this->query('SHOW COLUMNS FROM '.$this->prefix.$table)->fetchAll();
        foreach ($columns as $key => $val) {
            $fields[] = $val['Field'];
        }
        $lastId = array();
        // Check indexed or associative array
        if (!isset($datas[ 0 ])) {
            $datas = array($datas);
        }

        foreach ($datas as $data) {
            $values = array();
            $columns = array();

            foreach ($data as $key => $value) {
                if (!in_array($key, $fields)) {
                    continue;
                }
                array_push($columns, $this->columnQuote($key));

                switch (gettype($value)) {
                    case 'NULL':
                        $values[] = 'NULL';
                        break;

                    case 'array':
                        preg_match("/\(JSON\)\s*([\w]+)/i", $key, $column_match);

                        $values[] = isset($column_match[ 0 ]) ?
                            $this->quote(json_encode($value)) :
                            $this->quote(serialize($value));
                        break;

                    case 'boolean':
                        $values[] = ($value ? '1' : '0');
                        break;

                    case 'integer':
                    case 'double':
                    case 'string':
                        $values[] = $this->fnQuote($key, $value);
                        break;
                }
            }

            $this->exec('INSERT INTO '.$this->prefix.$table.' ('.implode(', ', $columns).') VALUES ('.implode($values, ', ').')');

            $lastId[] = $this->pdo->lastInsertId();
        }

        return count($lastId) > 1 ? $lastId : $lastId[ 0 ];
    }

    public function update($table, $data, $where = null)
    {
        $fields = array();

        foreach ($data as $key => $value) {
            preg_match('/([\w]+)(\[(\+|\-|\*|\/)\])?/i', $key, $match);

            if (isset($match[ 3 ])) {
                if (is_numeric($value)) {
                    $fields[] = $this->columnQuote($match[ 1 ]).' = '.$this->columnQuote($match[ 1 ]).' '.$match[ 3 ].' '.$value;
                }
            } else {
                $column = $this->columnQuote($key);

                switch (gettype($value)) {
                    case 'NULL':
                        $fields[] = $column.' = NULL';
                        break;

                    case 'array':
                        preg_match("/\(JSON\)\s*([\w]+)/i", $key, $column_match);

                        $fields[] = $column.' = '.$this->quote(isset($column_match[ 0 ]) ? json_encode($value) : serialize($value));
                        break;

                    case 'boolean':
                        $fields[] = $column.' = '.($value ? '1' : '0');
                        break;

                    case 'integer':
                    case 'double':
                    case 'string':
                        $fields[] = $column.' = '.$this->fnQuote($key, $value);
                        break;
                }
            }
        }

        return $this->exec('UPDATE '.$this->prefix.$table.' SET '.implode(', ', $fields).$this->whereClause($where));
    }

    public function delete($table, $where)
    {
        return $this->exec('DELETE FROM '.$this->prefix.$table.' '.$this->whereClause($where));
    }

    public function replace($table, $columns, $search = null, $replace = null, $where = null)
    {
        if (is_array($columns)) {
            $replace_query = array();

            foreach ($columns as $column => $replacements) {
                foreach ($replacements as $replace_search => $replace_replacement) {
                    $replace_query[] = $column.' = REPLACE('.$this->columnQuote($column).', '.$this->quote($replace_search).', '.$this->quote($replace_replacement).')';
                }
            }

            $replace_query = implode(', ', $replace_query);
            $where = $search;
        } else {
            if (is_array($search)) {
                $replace_query = array();

                foreach ($search as $replace_search => $replace_replacement) {
                    $replace_query[] = $columns.' = REPLACE('.$this->columnQuote($columns).', '.$this->quote($replace_search).', '.$this->quote($replace_replacement).')';
                }

                $replace_query = implode(', ', $replace_query);
                $where = $replace;
            } else {
                $replace_query = $columns.' = REPLACE('.$this->columnQuote($columns).', '.$this->quote($search).', '.$this->quote($replace).')';
            }
        }

        return $this->exec('UPDATE '.$this->prefix.$table.' SET '.$replace_query.$this->whereClause($where));
    }

    public function get($table, $join = null, $column = null, $where = null)
    {
        $query = $this->query($this->selectContext($table, $join, $column, $where).' LIMIT 1');

        if ($query) {
            $data = $query->fetchAll(PDO::FETCH_ASSOC);

            if (isset($data[ 0 ])) {
                $column = $where == null ? $join : $column;

                if (is_string($column) && $column != '*') {
                    return $data[ 0 ][ $column ];
                }

                return $data[ 0 ];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function has($table, $join, $where = null)
    {
        $column = null;

        $query = $this->query('SELECT EXISTS('.$this->selectContext($table, $join, $column, $where, 1).')');

        if ($query) {
            return $query->fetchColumn() === '1';
        } else {
            return false;
        }
    }

    public function count($table, $join = null, $column = null, $where = null)
    {
        $query = $this->query($this->selectContext($table, $join, $column, $where, 'COUNT'));

        return $query ? 0 + $query->fetchColumn() : false;
    }

    public function max($table, $join, $column = null, $where = null)
    {
        $query = $this->query($this->selectContext($table, $join, $column, $where, 'MAX'));

        if ($query) {
            $max = $query->fetchColumn();

            return is_numeric($max) ? $max + 0 : $max;
        } else {
            return false;
        }
    }

    public function min($table, $join, $column = null, $where = null)
    {
        $query = $this->query($this->selectContext($table, $join, $column, $where, 'MIN'));

        if ($query) {
            $min = $query->fetchColumn();

            return is_numeric($min) ? $min + 0 : $min;
        } else {
            return false;
        }
    }

    public function avg($table, $join, $column = null, $where = null)
    {
        $query = $this->query($this->selectContext($table, $join, $column, $where, 'AVG'));

        return $query ? 0 + $query->fetchColumn() : false;
    }

    public function sum($table, $join, $column = null, $where = null)
    {
        $query = $this->query($this->selectContext($table, $join, $column, $where, 'SUM'));

        return $query ? 0 + $query->fetchColumn() : false;
    }

    public function action($actions, $param = null)
    {
        if (is_callable($actions)) {
            $this->pdo->beginTransaction();

            $result = $actions($this, $param);

            if ($result === false) {
                $this->pdo->rollBack();
            } else {
                $this->pdo->commit();
            }

            return $result;
        } else {
            return false;
        }
    }

    public function debug()
    {
        $this->debug_mode = true;

        return $this;
    }

    public function error()
    {
        return $this->pdo->errorInfo();
    }

    public function lastQuery()
    {
        return end($this->logs);
    }

    public function log()
    {
        return $this->logs;
    }

    public function info()
    {
        $output = array(
            'server' => 'SERVER_INFO',
            'driver' => 'DRIVER_NAME',
            'client' => 'CLIENT_VERSION',
            'version' => 'SERVER_VERSION',
            'connection' => 'CONNECTION_STATUS',
        );

        foreach ($output as $key => $value) {
            $output[ $key ] = $this->pdo->getAttribute(constant('PDO::ATTR_'.$value));
        }

        return $output;
    }
}
