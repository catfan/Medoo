<?php

namespace Medoo\Tests;

/**
 * @coversDefaultClass \Medoo\Medoo
 */
class RandTest extends MedooTestCase
{
    /**
     * @covers ::rand()
     * @dataProvider typesProvider
     */
    public function testRand($type)
    {
        $this->setType($type);

        $this->database->rand("account", [
            "user_name"
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                SELECT "user_name"
                FROM "account"
                ORDER BY RANDOM()
                EOD,
            'mysql' => <<<EOD
                SELECT `user_name`
                FROM `account`
                ORDER BY RAND()
                EOD,
            'mssql' => <<<EOD
                SELECT [user_name]
                FROM [account]
                ORDER BY NEWID()
                EOD
        ], $this->database->queryString);
    }

    /**
     * @covers ::rand()
     * @dataProvider typesProvider
     */
    public function testWhereRand($type)
    {
        $this->setType($type);

        $this->database->rand("account", [
            "user_name"
        ], [
            "location" => "Tokyo"
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                SELECT "user_name"
                FROM "account"
                WHERE "location" = 'Tokyo'
                ORDER BY RANDOM()
                EOD,
            'mysql' => <<<EOD
                SELECT `user_name`
                FROM `account`
                WHERE `location` = 'Tokyo'
                ORDER BY RAND()
                EOD,
            'mssql' => <<<EOD
                SELECT [user_name]
                FROM [account]
                WHERE [location] = 'Tokyo'
                ORDER BY NEWID()
                EOD
        ], $this->database->queryString);
    }

    /**
     * @covers ::rand()
     * @dataProvider typesProvider
     */
    public function testWhereWithJoinRand($type)
    {
        $this->setType($type);

        $this->database->rand("account", [
            "[>]album" => "user_id"
        ], [
            "account.user_name"
        ], [
            "album.location" => "Tokyo"
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                SELECT "account"."user_name"
                FROM "account"
                LEFT JOIN "album" USING ("user_id")
                WHERE "album"."location" = 'Tokyo'
                ORDER BY RANDOM()
                EOD,
            'mysql' => <<<EOD
                SELECT `account`.`user_name`
                FROM `account`
                LEFT JOIN `album` USING (`user_id`)
                WHERE `album`.`location` = 'Tokyo'
                ORDER BY RAND()
                EOD,
            'mssql' => <<<EOD
                SELECT [account].[user_name]
                FROM [account]
                LEFT JOIN [album] USING ([user_id])
                WHERE [album].[location] = 'Tokyo'
                ORDER BY NEWID()
                EOD
        ], $this->database->queryString);
    }

    /**
     * @covers ::rand()
     * @dataProvider typesProvider
     */
    public function testWithJoinRand($type)
    {
        $this->setType($type);

        $this->database->rand("account", [
            "[>]album" => "user_id"
        ], [
            "account.user_name"
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                SELECT "account"."user_name"
                FROM "account"
                LEFT JOIN "album" USING ("user_id")
                ORDER BY RANDOM()
                EOD,
            'mysql' => <<<EOD
                SELECT `account`.`user_name`
                FROM `account`
                LEFT JOIN `album` USING (`user_id`)
                ORDER BY RAND()
                EOD,
            'mssql' => <<<EOD
                SELECT [account].[user_name]
                FROM [account]
                LEFT JOIN [album] USING ([user_id])
                ORDER BY NEWID()
                EOD
        ], $this->database->queryString);
    }
}
