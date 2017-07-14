<?php

namespace Medoo;

/**
 * Interface DatabaseInterface
 * @package Medoo
 */
interface DatabaseInterface
{
    public function __construct($options = null);

    public function query($query, $map = []);

    public function exec($query, $map = []);

    public function quote($string);

    public function select($table, $join, $columns = null, $where = null);

    public function insert($table, $datas);

    public function update($table, $data, $where = null);

    public function delete($table, $where);

    public function replace($table, $columns, $where = null);

    public function get($table, $join = null, $columns = null, $where = null);

    public function has($table, $join, $where = null);

    public function count($table, $join = null, $column = null, $where = null);

    public function max($table, $join, $column = null, $where = null);

    public function min($table, $join, $column = null, $where = null);

    public function avg($table, $join, $column = null, $where = null);

    public function sum($table, $join, $column = null, $where = null);

    public function action($actions);

    public function id();

    public function debug();

    public function error();

    public function last();

    public function log();

    public function info();
}
