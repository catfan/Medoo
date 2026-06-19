<?php

namespace Medoo\Tests;

#[\PHPUnit\Framework\Attributes\CoversClass(\Medoo\Medoo::class)]
class IdTest extends MedooTestCase
{
    public function testOracleIdReturnsCachedValue()
    {
        $this->setType('oracle');
        $this->database->returnId = '42';

        $this->assertSame('42', $this->database->id());
    }

    public function testIdReturnsNullWithoutConnection()
    {
        $this->setType('mysql');

        $this->assertNull($this->database->id());
    }

    public function testPostgreSQLIdUsesLastvalWithoutSequenceName()
    {
        $this->setType('pgsql');
        $pdo = new IdTestPDO('12', '34');
        $this->database->pdo = $pdo;

        $this->assertSame('34', $this->database->id());
        $this->assertSame(['SELECT LASTVAL()'], $pdo->queries);
        $this->assertSame([], $pdo->lastInsertIdNames);
    }

    public function testPostgreSQLIdUsesSequenceName()
    {
        $this->setType('pgsql');
        $pdo = new IdTestPDO('56', '78');
        $this->database->pdo = $pdo;

        $this->assertSame('56', $this->database->id('account_id_seq'));
        $this->assertSame([], $pdo->queries);
        $this->assertSame(['account_id_seq'], $pdo->lastInsertIdNames);
    }

    public function testPostgreSQLIdKeepsZeroValue()
    {
        $this->setType('pgsql');
        $this->database->pdo = new IdTestPDO('12', '0');

        $this->assertSame('0', $this->database->id());
    }

    public function testSybaseIdUsesIdentity()
    {
        $this->setType('sybase');
        $pdo = new IdTestPDO('12', '90');
        $this->database->pdo = $pdo;

        $this->assertSame('90', $this->database->id());
        $this->assertSame(['SELECT @@IDENTITY'], $pdo->queries);
        $this->assertSame([], $pdo->lastInsertIdNames);
    }

    public function testDriverFalseIdReturnsNull()
    {
        $this->setType('mysql');
        $this->database->pdo = new IdTestPDO(false, '12');

        $this->assertNull($this->database->id());
    }

    public function testDefaultDriverIdUsesPdoLastInsertId()
    {
        $this->setType('mysql');
        $pdo = new IdTestPDO('123', '12');
        $this->database->pdo = $pdo;

        $this->assertSame('123', $this->database->id());
        $this->assertSame([], $pdo->queries);
        $this->assertSame([null], $pdo->lastInsertIdNames);
    }
}

class IdTestPDO
{
    public $queries = [];
    public $lastInsertIdNames = [];

    protected $lastInsertId;
    protected $queryResult;

    public function __construct($lastInsertId, $queryResult)
    {
        $this->lastInsertId = $lastInsertId;
        $this->queryResult = $queryResult;
    }

    public function lastInsertId(?string $name = null)
    {
        $this->lastInsertIdNames[] = $name;

        return $this->lastInsertId;
    }

    public function query(string $query)
    {
        $this->queries[] = $query;

        return new IdTestStatement($this->queryResult);
    }
}

class IdTestStatement
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function fetchColumn()
    {
        return $this->value;
    }
}
