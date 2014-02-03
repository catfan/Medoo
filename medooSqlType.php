<?php
/**
 * Created by PhpStorm.
 * User: maflorez
 * Date: 2/02/14
 * Time: 11:32 PM
 */

class medooSqlType
{

    protected $_sqlType = '';

    public function __construct($strSqlType)
    {
        $this->_sqlType = $strSqlType;
    }

    public function getValue()
    {
        return $this->_sqlType;
    }

    public function __toString()
    {
        return $this->getValue();
    }
}