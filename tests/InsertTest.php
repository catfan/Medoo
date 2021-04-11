<?php

namespace Medoo\Tests;

class InsertTest extends MedooTestCase
{
    /**
     * @covers Medoo::insert()
     * @dataProvider typesProvider
     */
    public function testInsert($type)
    {
        $this->setType($type);

        $this->database->insert("account", [
            "user_name" => "foo",
            "email" => "foo@bar.com"
        ]);

        $this->assertQuery(
            <<<EOD
            INSERT INTO "account" ("user_name", "email")
            VALUES ('foo', 'foo@bar.com')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::insert()
     * @dataProvider typesProvider
     */
    public function testInsertWithArray($type)
    {
        $this->setType($type);

        $this->database->insert("account", [
            "user_name" => "foo",
            "lang" => ["en", "fr"]
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                INSERT INTO "account" ("user_name", "lang")
                VALUES ('foo', 'a:2:{i:0;s:2:"en";i:1;s:2:"fr";}')
                EOD,
            'mysql' => <<<EOD
                INSERT INTO "account" ("user_name", "lang")
                VALUES ('foo', 'a:2:{i:0;s:2:\"en\";i:1;s:2:\"fr\";}')
                EOD
        ], $this->database->queryString);
    }

    /**
     * @covers Medoo::insert()
     * @dataProvider typesProvider
     */
    public function testInsertWithJSON($type)
    {
        $this->setType($type);

        $this->database->insert("account", [
            "user_name" => "foo",
            "lang [JSON]" => ["en", "fr"]
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                INSERT INTO "account" ("user_name", "lang")
                VALUES ('foo', '["en","fr"]')
                EOD,
            'mysql' => <<<EOD
                INSERT INTO `account` (`user_name`, `lang`)
                VALUES ('foo', '[\"en\",\"fr\"]')
                EOD
        ], $this->database->queryString);
    }

    /**
     * @covers Medoo::insert()
     * @dataProvider typesProvider
     */
    public function testMultiInsert($type)
    {
        $this->setType($type);

        $this->database->insert("account", [
            [
                "user_name" => "foo",
                "email" => "foo@bar.com"
            ],
            [
                "user_name" => "bar",
                "email" => "bar@foo.com"
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            INSERT INTO "account" ("user_name", "email")
            VALUES ('foo', 'foo@bar.com'), ('bar', 'bar@foo.com')
            EOD,
            $this->database->queryString
        );
    }
}
