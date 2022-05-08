<?php

namespace Medoo\Tests;

use Medoo\Medoo;

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

    /**
     * @covers ::drop()
     */
    public function testDropWithPrefix()
    {
        $database = new Medoo([
            'testMode' => true,
            'prefix' => 'PREFIX_'
        ]);

        $database->type = "sqlite";

        $database->drop("account");

        $this->assertQuery(
            <<<EOD
            DROP TABLE IF EXISTS "PREFIX_account"
            EOD,
            $database->queryString
        );
    }
}
