<?php

namespace Medoo\Tests;

use Medoo\Medoo;

/**
 * @coversDefaultClass \Medoo\Medoo
 */
class QueryTest extends MedooTestCase
{
    /**
     * @covers ::query()
     * @covers ::isRaw()
     * @covers ::buildRaw()
     * @dataProvider typesProvider
     */
    public function testQuery($type)
    {
        $this->setType($type);

        $this->database->query("SELECT <account.email>,<account.nickname> FROM <account> WHERE <id> != 100");

        $this->assertQuery(
            <<<EOD
            SELECT "account"."email","account"."nickname"
            FROM "account"
            WHERE "id" != 100
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::query()
     * @covers ::isRaw()
     * @covers ::buildRaw()
     */
    public function testQueryWithPrefix()
    {
        $database = new Medoo([
            'testMode' => true,
            'prefix' => 'PREFIX_'
        ]);

        $database->type = "sqlite";

        $database->query("SELECT <account.name> FROM <account>");

        $this->assertQuery(
            <<<EOD
            SELECT "PREFIX_account"."name"
            FROM "PREFIX_account"
            EOD,
            $database->queryString
        );
    }

    /**
     * @covers ::query()
     * @covers ::isRaw()
     * @covers ::buildRaw()
     */
    public function testQueryTableWithPrefix()
    {
        $database = new Medoo([
            'testMode' => true,
            'prefix' => 'PREFIX_'
        ]);

        $database->type = "sqlite";

        $database->query("DROP TABLE IF EXISTS <account>");

        $this->assertQuery(
            <<<EOD
            DROP TABLE IF EXISTS "PREFIX_account"
            EOD,
            $database->queryString
        );
    }

    /**
     * @covers ::query()
     * @covers ::isRaw()
     * @covers ::buildRaw()
     * @dataProvider typesProvider
     */
    public function testPreparedStatementQuery($type)
    {
        $this->setType($type);

        $this->database->query(
            "SELECT * FROM <account> WHERE <user_name> = :user_name AND <age> = :age",
            [
                ":user_name" => "John Smite",
                ":age" => 20
            ]
        );

        $this->assertQuery(
            <<<EOD
            SELECT *
            FROM "account"
            WHERE "user_name" = 'John Smite' AND "age" = 20
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::query()
     * @covers ::isRaw()
     * @covers ::buildRaw()
     */
    public function testQueryEscape()
    {
        $database = new Medoo([
            'testMode' => true,
            'prefix' => 'PREFIX_'
        ]);

        $database->type = "sqlite";

        $database->query("SELECT * FROM <account> WHERE <name> = '<John>'");

        $this->assertQuery(
            <<<EOD
            SELECT *
            FROM "PREFIX_account"
            WHERE "name" = '<John>'
            EOD,
            $database->queryString
        );
    }
}
