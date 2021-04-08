<?php
namespace Medoo\Tests;

class WhereTest extends MedooTestCase
{
    /**
     * @covers Medoo::where()
     * @dataProvider typesProvider
     */
    public function testWhere($type)
    {
        $this->setType($type);
        
        $this->database->select("account", "*");

        $this->assertQuery(<<<EOD
            SELECT * FROM "account"
            EOD,
            $this->database->queryString
        );
    }
}