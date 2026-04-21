<?php

namespace Medoo\Tests;

use Medoo\Medoo;

#[\PHPUnit\Framework\Attributes\CoversClass(\Medoo\Medoo::class)]
class RawTest extends MedooTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testRawWithPlaceholder($type)
    {
        $this->setType($type);

        $this->database->select('account', [
            'score' => Medoo::raw('SUM(<age> + <experience>)')
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT SUM("age" + "experience") AS "score"
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testRawWithSamePlaceholderName($type)
    {
        $this->setType($type);

        $this->database->select('account', [
            'system' => Medoo::raw("COUNT(<system> = 'window' OR <system> = 'mac')")
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT COUNT("system" = 'window' OR "system" = 'mac') AS "system"
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }
}
