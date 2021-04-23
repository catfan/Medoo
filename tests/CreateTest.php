<?php

namespace Medoo\Tests;

/**
 * @coversDefaultClass \Medoo\Medoo
 */
class CreateTest extends MedooTestCase
{
    /**
     * @covers ::create()
     * @dataProvider typesProvider
     */
    public function testCreate($type)
    {
        $this->setType($type);

        $this->database->create("account", [
            "id" => [
                "INT",
                "NOT NULL",
                "AUTO_INCREMENT"
            ],
            "email" => [
                "VARCHAR(70)",
                "NOT NULL",
                "UNIQUE"
            ],
            "PRIMARY KEY (<id>)"
        ], [
            "AUTO_INCREMENT" => 200
        ]);

        $this->assertQuery(
            [
            'default' => <<<EOD
                CREATE TABLE IF NOT EXISTS "account"
                ("id" INT NOT NULL AUTO_INCREMENT,
                "email" VARCHAR(70) NOT NULL UNIQUE,
                PRIMARY KEY ("id"))
                AUTO_INCREMENT = 200
                EOD,
            'mssql' => <<<EOD
                CREATE TABLE [account]
                ([id] INT NOT NULL AUTO_INCREMENT,
                [email] VARCHAR(70) NOT NULL UNIQUE,
                PRIMARY KEY ([id]))
                AUTO_INCREMENT = 200
                EOD,
            'oracle' => <<<EOD
                CREATE TABLE "account"
                ("id" INT NOT NULL AUTO_INCREMENT,
                "email" VARCHAR(70) NOT NULL UNIQUE,
                PRIMARY KEY ("id"))
                AUTO_INCREMENT = 200
                EOD
        ],
            $this->database->queryString
        );
    }

    /**
     * @covers ::create()
     * @dataProvider typesProvider
     */
    public function testCreateWithStringDefinition($type)
    {
        $this->setType($type);

        $this->database->create("account", [
            "id" => "INT NOT NULL AUTO_INCREMENT",
            "email" => "VARCHAR(70) NOT NULL UNIQUE"
        ]);

        $this->assertQuery(
            [
            'default' => <<<EOD
                CREATE TABLE IF NOT EXISTS "account"
                ("id" INT NOT NULL AUTO_INCREMENT,
                "email" VARCHAR(70) NOT NULL UNIQUE)
                EOD,
            'mssql' => <<<EOD
                CREATE TABLE [account]
                ([id] INT NOT NULL AUTO_INCREMENT,
                [email] VARCHAR(70) NOT NULL UNIQUE)
                EOD,
            'oracle' => <<<EOD
                CREATE TABLE "account"
                ("id" INT NOT NULL AUTO_INCREMENT,
                "email" VARCHAR(70) NOT NULL UNIQUE)
                EOD
        ],
            $this->database->queryString
        );
    }

    /**
     * @covers ::create()
     * @dataProvider typesProvider
     */
    public function testCreateWithSingleOption($type)
    {
        $this->setType($type);

        $this->database->create("account", [
            "id" => [
                "INT",
                "NOT NULL",
                "AUTO_INCREMENT"
            ],
            "email" => [
                "VARCHAR(70)",
                "NOT NULL",
                "UNIQUE"
            ]
        ], "TABLESPACE tablespace_name");

        $this->assertQuery(
            [
            'default' => <<<EOD
                CREATE TABLE IF NOT EXISTS "account"
                ("id" INT NOT NULL AUTO_INCREMENT,
                "email" VARCHAR(70) NOT NULL UNIQUE)
                TABLESPACE tablespace_name
                EOD,
            'mssql' => <<<EOD
                CREATE TABLE [account]
                ([id] INT NOT NULL AUTO_INCREMENT,
                [email] VARCHAR(70) NOT NULL UNIQUE)
                TABLESPACE tablespace_name
                EOD,
            'oracle' => <<<EOD
                CREATE TABLE "account"
                ("id" INT NOT NULL AUTO_INCREMENT,
                "email" VARCHAR(70) NOT NULL UNIQUE)
                TABLESPACE tablespace_name
                EOD
        ],
            $this->database->queryString
        );
    }
}
