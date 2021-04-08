<?php
namespace Medoo\Tests;

class SelectTest extends MedooTestCase
{
    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectAll($type)
    {
        $this->setType($type);
        
        $this->database->select("account", "*");

        $this->assertQuery(<<<EOD
            SELECT * FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectColumns($type)
    {
        $this->setType($type);
        
        $this->database->select("account", ["name", "id"]);

        $this->assertQuery(<<<EOD
            SELECT "name","id"
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectColumnsWithAlias($type)
    {
        $this->setType($type);
        
        $this->database->select("account", ["name(nickname)", "id"]);

        $this->assertQuery(<<<EOD
            SELECT "name" AS "nickname","id"
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectWithWhere($type)
    {
        $this->setType($type);
        
        $this->database->select("account", [
            "name",
            "id"
        ], [
            "LIMIT" => [10, 20]
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                SELECT "name","id"
                FROM "account"
                LIMIT 20 OFFSET 10
                EOD,
            'mssql' => <<<EOD
                SELECT "name","id"
                FROM "account"
                ORDER BY (SELECT 0)
                OFFSET 10 ROWS FETCH NEXT 20 ROWS ONLY
                EOD
        ], $this->database->queryString);
    }
}
