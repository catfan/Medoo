<?php
namespace Medoo\Tests;

class RandTest extends MedooTestCase
{
    /**
     * @covers Medoo::rand()
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
                ORDER BY NEWID()
                EOD,
            'mssql' => <<<EOD
                SELECT [user_name]
                FROM [account]
                ORDER BY NEWID()
                EOD
        ], $this->database->queryString);
    }
}