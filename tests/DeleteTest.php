<?php

namespace Medoo\Tests;

use Medoo\Medoo;

/**
 * @coversDefaultClass \Medoo\Medoo
 */
class DeleteTest extends MedooTestCase
{
    /**
     * @covers ::delete()
     * @dataProvider typesProvider
     */
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

    /**
     * @covers ::delete()
     * @dataProvider typesProvider
     */
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
