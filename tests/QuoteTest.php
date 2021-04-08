<?php
namespace Medoo\Tests;

class QuoteTest extends MedooTestCase
{
    /**
     * @covers Medoo::Quote()
     * @dataProvider typesProvider
     */
    public function testQuote($type)
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