<?php

namespace Medoo\Tests;

use InvalidArgumentException;

#[\PHPUnit\Framework\Attributes\CoversClass(\Medoo\Medoo::class)]
class ReplaceTest extends MedooTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
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

    public function testReplaceEmptyColumns()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->database->replace("account", [], [
            "user_id[>]" => 1000
        ]);
    }
}
