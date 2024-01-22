<?php

namespace Medoo\Tests;

use Medoo\Medoo;

/**
 * @coversDefaultClass \Medoo\Medoo
 */
class RawTest extends MedooTestCase
{
    /**
     * @covers ::raw()
     * @covers ::isRaw()
     * @covers ::buildRaw()
     * @dataProvider typesProvider
     */
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

    /**
     * @covers ::raw()
     * @covers ::isRaw()
     * @covers ::buildRaw()
     * @dataProvider typesProvider
     */
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
