<?php
namespace Medoo\Tests;

class CreateTest extends MedooTestCase
{
    /**
     * @covers Medoo::create()
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

        $this->assertQuery([
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
        ], $this->database->queryString
        );
    }
}