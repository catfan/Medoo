<?php
namespace Medoo\Tests;

class DropTest extends MedooTestCase
{
    /**
     * @covers Medoo::drop()
     * @dataProvider typesProvider
     */
    public function testDrop($type)
    {
        $this->setType($type);
        
        $this->database->drop("account");

        $this->assertQuery(<<<EOD
            DROP TABLE IF EXISTS account
            EOD,
            $this->database->queryString
        );
    }
}