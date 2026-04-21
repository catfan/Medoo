<?php

namespace Medoo\Tests;

use Medoo\Medoo;

#[\PHPUnit\Framework\Attributes\CoversClass(\Medoo\Medoo::class)]
class DeleteTest extends MedooTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testDelete($type)
    {
        $this->setType($type);

        $this->database->delete("account", [
            "AND" => [
                "type" => "business",
                "age[<]" => 18
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            DELETE FROM "account"
            WHERE ("type" = 'business' AND "age" < 18)
            EOD,
            $this->database->queryString
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testDeleteRaw($type)
    {
        $this->setType($type);

        $whereClause = Medoo::raw("WHERE (<type> = :type AND <age> < :age)", [
            ':type' => 'business',
            ':age' => 18,
        ]);

        $this->database->delete("account", $whereClause);

        $this->assertQuery(
            <<<EOD
            DELETE FROM "account"
            WHERE ("type" = 'business' AND "age" < 18)
            EOD,
            $this->database->queryString
        );
    }
}
