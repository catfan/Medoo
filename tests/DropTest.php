<?php

namespace Medoo\Tests;

use Medoo\Medoo;

#[\PHPUnit\Framework\Attributes\CoversClass(\Medoo\Medoo::class)]
class DropTest extends MedooTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
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
