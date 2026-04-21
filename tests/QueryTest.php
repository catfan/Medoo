<?php

namespace Medoo\Tests;

use Medoo\Medoo;

#[\PHPUnit\Framework\Attributes\CoversClass(\Medoo\Medoo::class)]
class QueryTest extends MedooTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
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

    public function testQueryWithPrefix()
    {
        $database = new Medoo([
            'testMode' => true,
            'prefix' => 'PREFIX_'
        ]);

        $database->query("SELECT <account.name> FROM <account>");

        $this->assertQuery(
            <<<EOD
            SELECT "PREFIX_account"."name"
            FROM "PREFIX_account"
            EOD,
            $database->queryString
        );
    }

    public function testQueryTableWithPrefix()
    {
        $database = new Medoo([
            'testMode' => true,
            'prefix' => 'PREFIX_'
        ]);

        $database->query("DROP TABLE IF EXISTS <account>");

        $this->assertQuery(
            <<<EOD
            DROP TABLE IF EXISTS "PREFIX_account"
            EOD,
            $database->queryString
        );
    }

    public function testQueryShowTableWithPrefix()
    {
        $database = new Medoo([
            'testMode' => true,
            'prefix' => 'PREFIX_'
        ]);

        $database->query("SHOW TABLES LIKE <account>");

        $this->assertQuery(
            <<<EOD
            SHOW TABLES LIKE "PREFIX_account"
            EOD,
            $database->queryString
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
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

    public function testQueryEscape()
    {
        $database = new Medoo([
            'testMode' => true,
            'prefix' => 'PREFIX_'
        ]);

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
