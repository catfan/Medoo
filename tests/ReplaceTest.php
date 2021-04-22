<?php

namespace Medoo\Tests;

use InvalidArgumentException;

/**
 * @coversDefaultClass \Medoo\Medoo
 */
class ReplaceTest extends MedooTestCase
{
    /**
     * @covers ::replace()
     * @dataProvider typesProvider
     */
    public function testReplace($type)
    {
        $this->setType($type);

        $this->database->replace("account", [
            "type" => [
                "user" => "new_user",
                "business" => "new_business"
            ],
            "column" => [
                "old_value" => "new_value"
            ]
        ], [
            "user_id[>]" => 1000
        ]);

        $this->assertQuery(
            <<<EOD
            UPDATE "account"
            SET "type" = REPLACE("type", 'user', 'new_user'),
            "type" = REPLACE("type", 'business', 'new_business'),
            "column" = REPLACE("column", 'old_value', 'new_value')
            WHERE "user_id" > 1000
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::replace()
     */
    public function testReplaceEmptyColumns()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->database->replace("account", [], [
            "user_id[>]" => 1000
        ]);
    }
}
