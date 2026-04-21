<?php

namespace Medoo\Tests;

#[\PHPUnit\Framework\Attributes\CoversClass(\Medoo\Medoo::class)]
class CreateTest extends MedooTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
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
