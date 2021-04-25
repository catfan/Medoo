<?php

namespace Medoo\Tests;

/**
 * @coversDefaultClass \Medoo\Medoo
 */
class DropTest extends MedooTestCase
{
    /**
     * @covers ::drop()
     * @dataProvider typesProvider
     */
    public function testDrop($type)
    {
        $this->setType($type);

        $this->database->drop("account");

        $this->assertQuery(
            <<<EOD
            DROP TABLE IF EXISTS "account"
            EOD,
            $this->database->queryString
        );
    }
}
