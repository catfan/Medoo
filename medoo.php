<?php
/*!
 * Medoo database framework
 * http://medoo.in
 * Version 0.9.2
 * 
 * Copyright 2013, Angel Lai
 * Released under the MIT license
 */
class medoo
{
    protected $database_type = 'mysql';

    // For MySQL, MSSQL, Sybase
    protected $server = 'localhost';

    protected $username = 'username';

    protected $password = 'password';

    // For SQLite
    protected $database_file = '';

    // Optional
    protected $port = '';

    protected $charset = 'utf8';

    protected $database_name = '';

    protected $online_mode = true;

    protected $option = array();

    /**
     * @var string
     */
    protected $queryString;
    protected $_newLine = ' ';
    protected $_newLineTab = ' ';


    //for debug
    protected $logQueries = false;
    public $queryHistory = [];

    public function __construct($options)
    {
        try {
            $commands = array();
            $dsn = '';

            if (is_string($options)) {
                if (strtolower($this->database_type) == 'sqlite') {
                    $this->database_file = $options;
                } else {
                    $this->database_name = $options;
                }
            } else {
                foreach ($options as $option => $value) {
                    $this->$option = $value;
                }
            }

            $type = strtolower($this->database_type);

            if (isset($this->port) && is_int($this->port * 1)
            ) {
                $port = $this->port;
            }

            $set_charset = "SET NAMES '" . $this->charset . "'";

            switch ($type) {
                case 'mysql':
                case 'mariadb':
                    $dsn = $type . ':host=' . $this->server . (isset($port) ? ';port=' . $port : '')
                           . ';dbname=' . $this->database_name;
                    // Make MySQL using standard quoted identifier
                    $commands[] = 'SET GLOBAL SQL_MODE=ANSI_QUOTES';
                    $commands[] = $set_charset;
                    break;

                case 'pgsql':
                    $dsn = $type . ':host=' . $this->server . (isset($port) ? ';port=' . $port : '')
                           . ';dbname=' . $this->database_name;

                    break;

                case 'sybase':
                    $dsn = $type . ':host=' . $this->server . (isset($port) ? ',' . $port : '')
                           . ';dbname=' . $this->database_name;
                    $commands[] = $set_charset;

                    break;

                case 'mssql':
                    $dsn = strpos(PHP_OS, 'WIN') !== false
                        ? 'sqlsrv:server=' . $this->server . (isset($port) ? ',' . $port : '') . ';database='
                          . $this->database_name
                        : 'dblib:host=' . $this->server . (isset($port) ? ':' . $port : '') . ';dbname='
                          . $this->database_name;

                    // Keep MSSQL QUOTED_IDENTIFIER is ON for standard quoting
                    $commands[] = 'SET QUOTED_IDENTIFIER ON';
                    $commands[] = $set_charset;

                    break;

                case 'sqlite':
                    $dsn = $type . ':' . $this->database_file;
                    $this->username = null;
                    $this->password = null;

                    break;
            }

            $this->pdo = new PDO($dsn, $this->username, $this->password, $this->option);

            $this->exec($commands);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function query($queries)
    {
        if (!is_array($queries)) {
            $queries = array($queries);
        }
        foreach ($queries as $query) {
            $this->queryString = $query;
            $this->addQueryToHistory($query);
            if ($this->online_mode === false) {
                echo "{$query};\r\n";
                return true;
            } else {
                return $this->pdo->query($query);
            }
        }
        return false;
    }

    public function exec($queries)
    {
        if (!is_array($queries)) {
            $queries = array($queries);
        }
        foreach ($queries as $query) {
            $this->queryString = $query;
            $this->addQueryToHistory($query);
            if ($this->online_mode === false) {
                echo "{$query};\r\n";
                return true;
            } else {
                return $this->pdo->exec($query);
            }
        }
        return false;
    }

    protected function addQueryToHistory($query, $timestamp = true)
    {
        if ($this->logQueries == false) {
            return;
        }
        if ($timestamp) {
            $date = new DateTime();
            $query = $date->format("Y-m-d H:i:s") . ' ' . $query;
        }
        $this->queryHistory[] = $query;
    }

    public function enableLogQueries()
    {
        $this->logQueries = true;
    }

    public function disableLogQueries()
    {
        $this->logQueries = false;
    }

    /**
     * @return boolean
     */
    public function getOnlineMode()
    {
        return $this->online_mode;
    }

    /**
     * @param boolean $online_mode
     */
    public function setOnlineMode($online_mode)
    {
        $this->online_mode = $online_mode;
        if (!$online_mode) {
            $this->_newLine = "\r\n";
            $this->_newLineTab = "\r\n\t";
        } else {
            $this->_newLineTab = ' ';
            $this->_newLine = ' ';
        }
    }

    public function quote($string)
    {
        return $this->pdo->quote($string);
    }

    protected function column_quote($string)
    {
        return '`' . str_replace('.', '`.`', $string) . '`';
    }

    protected function column_push($columns)
    {
        $newLineTab = $this->_newLineTab == ' ' ? '' : $this->_newLineTab;
        if ($columns == '*') {
            return $columns;
        }

        if (is_string($columns)) {
            $columns = array($columns);
        }

        $stack = array();

        foreach ($columns as $value) {
            preg_match('/([a-zA-Z0-9_\-\.]*)\s*\(([a-zA-Z0-9_\-]*)\)/i', $value, $match);

            if (isset($match[1]) && isset($match[2])) {
                array_push($stack, $this->column_quote($match[1]) . ' AS ' . $this->column_quote($match[2]));
            } else {
                array_push($stack, $this->column_quote($value));
            }
        }

        return implode($stack, $newLineTab . ', ');
    }

    protected function array_quote($array)
    {
        $temp = array();

        foreach ($array as $value) {
            $temp[] = is_int($value) ? $value : $this->pdo->quote($value);
        }

        return implode($temp, ',');
    }

    protected function inner_conjunct($data, $conjunctor, $outer_conjunctor)
    {
        $haystack = array();
        foreach ($data as $value) {
            $haystack[] = '(' . $this->data_implode($value, $conjunctor) . ')';
        }
        return implode($outer_conjunctor . ' ', $haystack);
    }


    protected function data_implode($data, $conjunctor)
    {
        //TODO Review "$outer_conjunctor = null" for method definition
        $wheres = array();

        foreach ($data as $key => $value) {
            if (($key == 'AND' || $key == 'OR') && is_array($value)
            ) {
                $wheres[] = 0 !== count(array_diff_key($value, array_keys(array_keys($value))))
                    ? '(' . $this->data_implode($value, ' ' . $key) . ')' :
                    '(' . $this->inner_conjunct($value, ' ' . $key, $conjunctor) . ')';
            } else {
                preg_match('/([\w\.]+)(\[(\>|\>\=|\<|\<\=|\!|\<\>)\])?/i', $key, $match);
                if (isset($match[3])) {
                    if ($match[3] == '') {
                        $wheres[] = $this->column_quote($match[1]) . ' ' . $match[3] . '= ' . $this->quote($value);
                    } elseif ($match[3] == '!') {
                        $column = $this->column_quote($match[1]);

                        switch (gettype($value)) {
                            case 'NULL':
                                $wheres[] = $column . ' IS NOT NULL';
                                break;

                            case 'array':
                                $wheres[] = $column . ' NOT IN (' . $this->array_quote($value) . ')';
                                break;

                            case 'integer':
                                $wheres[] = $column . ' != ' . $value;
                                break;

                            case 'string':
                                $wheres[] = $column . ' != ' . $this->quote($value);
                                break;
                        }
                    } else {
                        if ($match[3] == '<>') {
                            if (is_array($value)) {
                                if (is_numeric($value[0]) && is_numeric($value[1])) {
                                    $wheres[] =
                                        $this->column_quote($match[1]) . ' BETWEEN ' . $value[0] . ' AND ' . $value[1];
                                } else {
                                    $wheres[] = $this->column_quote($match[1]) . ' BETWEEN ' . $this->quote($value[0])
                                                . ' AND ' . $this->quote($value[1]);
                                }
                            }
                        } else {
                            if (is_numeric($value)) {
                                $wheres[] = $this->column_quote($match[1]) . ' ' . $match[3] . ' ' . $value;
                            } else {
                                $datetime = strtotime($value);

                                if ($datetime) {
                                    $wheres[] = $this->column_quote($match[1]) . ' ' . $match[3] . ' ' . $this->quote(
                                            date('Y-m-d H:i:s', $datetime)
                                        );
                                }
                            }
                        }
                    }
                } else {
                    if (is_int($key)) {
                        $wheres[] = $this->quote($value);
                    } else {
                        $column = $this->column_quote($match[1]);

                        switch (gettype($value)) {
                            case 'NULL':
                                $wheres[] = $column . ' IS NULL';
                                break;

                            case 'array':
                                $wheres[] = $column . ' IN (' . $this->array_quote($value) . ')';
                                break;

                            case 'integer':
                                $wheres[] = $column . ' = ' . $value;
                                break;

                            case 'string':
                                $wheres[] = $column . ' = ' . $this->quote($value);
                                break;
                        }
                    }
                }
            }
        }

        return implode($this->_newLineTab . $conjunctor . ' ', $wheres);
    }

    public function where_clause($where)
    {
        $where_clause = '';

        if (is_array($where)) {
            $single_condition = array_diff_key(
                $where, array_flip(
                    explode(' ', 'AND OR GROUP ORDER HAVING LIMIT LIKE MATCH')
                )
            );

            $where_clause = $this->_newLine . 'WHERE' . $this->_newLineTab;
            if ($single_condition != array()) {
                $where_clause .= $this->data_implode($single_condition, '');
            }
            if (isset($where['AND'])) {
                $where_clause .= $this->data_implode($where['AND'], ' AND');
            }
            if (isset($where['OR'])) {
                $where_clause .= $this->data_implode($where['OR'], ' OR');
            }
            if (isset($where['LIKE'])) {
                $like_query = $where['LIKE'];
                if (is_array($like_query)) {
                    $is_OR = isset($like_query['OR']);

                    if ($is_OR || isset($like_query['AND'])) {
                        $connector = $is_OR ? 'OR' : 'AND';
                        $like_query = $is_OR ? $like_query['OR'] : $like_query['AND'];
                    } else {
                        $connector = 'AND';
                    }

                    $clause_wrap = array();
                    foreach ($like_query as $column => $keyword) {
                        if (is_array($keyword)) {
                            foreach ($keyword as $key) {
                                $clause_wrap[] =
                                    $this->column_quote($column) . ' LIKE ' . $this->quote('%' . $key . '%');
                            }
                        } else {
                            $clause_wrap[] =
                                $this->column_quote($column) . ' LIKE ' . $this->quote('%' . $keyword . '%');
                        }
                    }
                    $where_clause .= ($where_clause != '' ? ' AND ' : ' WHERE ') . '(' . implode(
                            $clause_wrap, ' ' . $connector . ' '
                        ) . ')';
                }
            }
            if (isset($where['MATCH'])) {
                $match_query = $where['MATCH'];
                if (is_array($match_query) && isset($match_query['columns']) && isset($match_query['keyword'])) {
                    $where_clause .= ($where_clause != '' ? ' AND ' : ' WHERE ') . ' MATCH (`' . str_replace(
                            '.', '`.`', implode($match_query['columns'], '`, `')
                        ) . '`) AGAINST (' . $this->quote($match_query['keyword']) . ')';
                }
            }
            if (isset($where['GROUP'])) {
                $where_clause .=
                    $this->_newLine . 'GROUP BY ' . $this->_newLineTab . $this->column_quote($where['GROUP']);
            }
            if (isset($where['ORDER'])) {
                preg_match('/(^[a-zA-Z0-9_\-\.]*)(\s*(DESC|ASC))?/', $where['ORDER'], $order_match);

                $where_clause .=
                    $this->_newLine . 'ORDER BY' . $this->_newLineTab . '`' . str_replace('.', '`.`', $order_match[1])
                    . '` ' . (isset($order_match[3]) ? $order_match[3] : '');

                if (isset($where['HAVING'])) {
                    $where_clause .=
                        $this->_newLine . 'HAVING' . $this->_newLineTab . $this->data_implode($where['HAVING'], '');
                }
            }
            if (isset($where['LIMIT'])) {
                $where_clause .= $this->limit_clause($where['LIMIT']);
            }
        } else {
            if ($where != null) {
                $where_clause .= ' ' . $where;
            }
        }

        return $where_clause;
    }

    public function select($table, $join, $columns = null, $where = null)
    {
        $table = '`' . $table . '`';
        $join_key = is_array($join) ? array_keys($join) : null;

        if (strpos($join_key[0], '[') !== false) {
            $table_join = array();

            $join_array = array(
                '>'  => 'LEFT',
                '<'  => 'RIGHT',
                '<>' => 'FULL',
                '><' => 'INNER'
            );

            foreach ($join as $sub_table => $relation) {
                preg_match('/(\[(\<|\>|\>\<|\<\>)\])?([a-zA-Z0-9_\-]*)/', $sub_table, $match);

                if ($match[2] != '' && $match[3] != '') {
                    if (is_string($relation)) {
                        $relation = 'USING (`' . $relation . '`)';
                    }

                    if (is_array($relation)) {
                        // For ['column1', 'column2']
                        if (isset($relation[0])) {
                            $relation = 'USING (`' . implode($relation, '`, `') . '`)';
                        } // For ['column1' => 'column2']
                        else {
                            $relation = 'ON ' . $table . '.`' . key($relation) . '` = `' . $match[3] . '`.`' . current(
                                    $relation
                                ) . '`';
                        }
                    }

                    $table_join[] = $join_array[$match[2]] . ' JOIN `' . $match[3] . '` ' . $relation;
                }
            }

            $table .= ' ' . implode($table_join, ' ');
        } else {
            $where = $columns;
            $columns = $join;
        }

        $query = $this->query(
            'SELECT' . $this->_newLineTab . $this->column_push($columns) . ' FROM ' . $table . $this->where_clause(
                $where
            )
        );

        return $query ? $query->fetchAll(
            (is_string($columns) && $columns != '*') ? PDO::FETCH_COLUMN : PDO::FETCH_ASSOC
        ) : false;
    }

    public function insert($table, $datas)
    {
        $lastId = array();

        // Check indexed or associative array
        if (!isset($datas[0])) {
            $datas = array($datas);
        }

        foreach ($datas as $data) {
            $keys = implode("`, `", array_keys($data));
            $values = array();

            foreach ($data as $value) {
                $values[] = $this->getTypedValue($value);
            }

            $this->exec('INSERT INTO `' . $table . '` (`' . $keys . '`) VALUES (' . implode($values, ', ') . ')');

            $lastId[] = $this->pdo->lastInsertId();
        }

        return count($lastId) > 1 ? $lastId : $lastId[0];
    }

    public function update($table, $data, $where = null, $limit = -1)
    {
        $fields = array();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $fields[] = $key . '=' . $this->quote(serialize($value));
            } else {
                preg_match('/([\w]+)(\[(\+|\-|\*|\/)\])?/i', $key, $match);
                if (isset($match[3])) {
                    if (is_numeric($value)) {
                        $fields[] =
                            $this->column_quote($match[1]) . ' = ' . $this->column_quote($match[1]) . ' ' . $match[3]
                            . ' ' . $value;
                    }
                } else {
                    $column = $this->column_quote($key);
                    $fields[] = $column . ' = ' . $this->getTypedValue($value);
                }
            }
        }

        $newLineTab = $this->_newLineTab == ' ' ? '' : $this->_newLineTab;
        return $this->exec(
            "UPDATE{$this->_newLineTab}`" . $table . "`{$this->_newLine}SET{$this->_newLineTab}" . implode(
                "{$newLineTab}, ", $fields
            ) . $this->where_clause($where) . $this->limit_clause(
                $limit
            )
        );
    }

    public function delete($table, $where)
    {
        return $this->exec('DELETE FROM `' . $table . '`' . $this->where_clause($where));
    }

    public function replace($table, $columns, $search = null, $replace = null, $where = null)
    {
        if (is_array($columns)) {
            $replace_query = array();

            foreach ($columns as $column => $replacements) {
                foreach ($replacements as $replace_search => $replace_replacement) {
                    $replace_query[] = $column . ' = REPLACE(`' . $column . '`, ' . $this->quote($replace_search) . ', '
                                       . $this->quote($replace_replacement) . ')';
                }
            }
            $replace_query = implode(', ', $replace_query);
            $where = $search;
        } else {
            if (is_array($search)) {
                $replace_query = array();

                foreach ($search as $replace_search => $replace_replacement) {
                    $replace_query[] =
                        $columns . ' = REPLACE(`' . $columns . '`, ' . $this->quote($replace_search) . ', '
                        . $this->quote($replace_replacement) . ')';
                }
                $replace_query = implode(', ', $replace_query);
                $where = $replace;
            } else {
                $replace_query =
                    $columns . ' = REPLACE(`' . $columns . '`, ' . $this->quote($search) . ', ' . $this->quote(
                        $replace
                    ) . ')';
            }
        }

        return $this->exec('UPDATE `' . $table . '` SET ' . $replace_query . $this->where_clause($where));
    }

    public function get($table, $columns, $where = null)
    {
        if (!isset($where)) {
            $where = array();
        }
        $where['LIMIT'] = 1;

        $data = $this->select($table, $columns, $where);

        return isset($data[0]) ? $data[0] : false;
    }

    public function has($table, $where)
    {
        return $this->query('SELECT EXISTS(SELECT 1 FROM `' . $table . '`' . $this->where_clause($where) . ')')
                   ->fetchColumn() === '1';
    }

    public function count($table, $where = null)
    {
        return intval(
            $this->query('SELECT COUNT(*) FROM `' . $table . '`' . $this->where_clause($where))->fetchColumn(), 10
        );
    }

    public function max($table, $column, $where = null)
    {
        return intval(
            $this->query('SELECT MAX(`' . $column . '`) FROM `' . $table . '`' . $this->where_clause($where))
                ->fetchColumn(), 10
        );
    }

    public function min($table, $column, $where = null)
    {
        return intval(
            $this->query('SELECT MIN(`' . $column . '`) FROM `' . $table . '`' . $this->where_clause($where))
                ->fetchColumn(), 10
        );
    }

    public function avg($table, $column, $where = null)
    {
        return intval(
            $this->query('SELECT AVG(`' . $column . '`) FROM `' . $table . '`' . $this->where_clause($where))
                ->fetchColumn(), 10
        );
    }

    public function sum($table, $column, $where = null)
    {
        return intval(
            $this->query('SELECT SUM(`' . $column . '`) FROM `' . $table . '`' . $this->where_clause($where))
                ->fetchColumn(), 10
        );
    }


    public function begin_transaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollback()
    {
        try {
            return $this->pdo->rollBack();
        } catch (PDOException $ex) {
            echo $ex->getMessage();
            return false;
        }
    }

    public function error()
    {
        return $this->pdo->errorInfo();
    }

    public function last_query()
    {
        return $this->queryString;
    }

    public function info()
    {
        return array(
            'server'     => $this->database_type == 'sqlite' ? null : $this->pdo->getAttribute(PDO::ATTR_SERVER_INFO),
            'client'     => $this->pdo->getAttribute(PDO::ATTR_CLIENT_VERSION),
            'driver'     => $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
            'version'    => $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
            'connection' =>
                $this->database_type == 'sqlite' ? null : $this->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS)
        );
    }

    public function limit_clause($limit)
    {
        if (is_array($limit)) {
            if (count($limit) == 2 && is_numeric($limit[0]) && $limit[0] >= 0 && is_numeric($limit[1])
                && $limit[1] >= 0
            ) {
                return "{$this->_newLine}LIMIT{$this->_newLineTab}{$limit[0]}, {$limit[1]}";
            } elseif (count($limit) == 1 && $limit[0] >= 0) {
                return "{$this->_newLine}LIMIT{$this->_newLineTab}{$limit[0]}";
            }
        } elseif (is_numeric($limit) && $limit >= 0) {
            return "{$this->_newLine}LIMIT{$this->_newLineTab}{$limit}";
        }
        return '';
    }

    protected function getTypedValue($value)
    {
        switch (gettype($value)) {
            case 'boolean':
                $typedValue = boolval($value) ? 'TRUE' : 'FALSE';
                break;

            case 'NULL':
                $typedValue = 'NULL';
                break;

            case 'array':
                $typedValue = $this->quote(serialize($value));
                break;

            case 'integer':
                $typedValue = intval($value, 10);
                break;

            case 'double':
                $typedValue = floatval($value);
                break;


            case 'object':
                if ('medooSqlType' == get_class($value)) {
                    $typedValue = $value;
                } else {
                    $typedValue = $this->quote(serialize($value));
                }
                break;

            case 'string':
            default:
                if (empty($value) && !is_numeric($value)) {
                    $typedValue = "''";
                } else {
                    $typedValue = $this->quote($value);
                }
                break;
        }
        return $typedValue;
    }
}