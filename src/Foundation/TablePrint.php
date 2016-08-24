<?php

namespace Medoo\Foundation;
use Medoo\Foundation\DataBase as DB;
use Closure;

/**
 * @author Alireza Josheghani <josheghani.dev@gmail.com>
 * @version 1.0
 * @package Medoo Foundation | TablePrint
 * */

class TablePrint {

    /**
     * TablePrint protected varibules
     */
    protected $table;
    protected $columns;
    protected $connect;

    /**
     * TablePrint constructor.
     * @param null $table
     * @param Closure|null $callback
     */
    public function __construct($table = null, Closure $callback = null)
    {
        $this->columns = [];
        $this->table = $table;
        $this->connect = new DB();
        if (!is_null($callback)) {
            $callback($this);
        }
    }

    /**
     * Create increments column
     * @param $column
     */
    public function increments($column)
    {
        $this->addColumn('INT NOT NULL AUTO_INCREMENT PRIMARY KEY', $column);
    }

    /**
     * Create integer column
     * @param $column
     * @param int $length
     */
    public function integer($column, $length = 11)
    {
        $this->addColumn('INT', $column, compact('length'));
    }

    /**
     * Create string column
     * @param $column
     * @param int $length
     */
    public function string($column, $length = 255)
    {
        $this->addColumn('VARCHAR', $column, compact('length'));
    }

    /**
     * Create text column
     * @param $column
     */
    public function text($column)
    {
        $this->addColumn('TEXT', $column);
    }

    /**
     * Create boolean column
     * @param $column
     */
    public function boolean($column)
    {
        $this->addColumn('boolean', $column);
    }

    /**
     * Create timestamp columns :| created_at , updated_at
     */
    public function timestamps()
    {
        $this->timestamp('created_at');
        $this->timestamp('updated_at');
    }

    /**
     * Create timestamp columns
     * @param $column => created_at | updated_at
     */
    public function timestamp($column)
    {
        $this->addColumn('timestamp', $column);
    }

    /**
     * Add column method
     * @param $type
     * @param $column
     * @param array $parameters
     */
    public function addColumn($type, $column, array $parameters = [])
    {
        $columns = array_merge(compact('type', 'column'),$parameters);
        array_push($this->columns,$columns);
    }


    /**
     * Create table with columns
     */
    public function create()
    {
        $i = 0;
        $sql = 'CREATE TABLE '.$this->table." ( ";
        while ($i <= count($this->columns) - 1){
            if($i < count($this->columns) - 1){
                $a = ",";
            } else { $a = ""; }
            if(!empty($this->columns[$i]['length'])){
                $option = "(".$this->columns[$i]['length'].")";
            } else { $option = ''; }
            $sql .= $this->columns[$i]['column']." ".
                $this->columns[$i]['type'].$option.$a;
            $i++;
        }
        $sql .= ' );';
        $query = $this->connect->query($sql);
        if($query){
            echo "\e[32mMigrated : \e[0m".$this->table."\n";
        } else {
            echo "\e[31mdo nothing : \e[0m".$this->table."\n";
        }
    }

    /**
     * Drop table
     * @param $tableName
     */
    public function drop($tableName)
    {
        $sql = 'DROP TABLE ';
        $sql .= $tableName;
        $sql .= ';';
        $query = $this->connect->query($sql);
        if($query){
            echo "\e[32mRolled back : \e[0m".$tableName."\n";
        } else {
            echo "\e[31mdo nothing : \e[0m".$tableName."\n";
        }
    }

}
