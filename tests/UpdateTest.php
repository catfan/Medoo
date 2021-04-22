<?php

namespace Medoo\Tests;

use Medoo\Medoo;

/**
 * @coversDefaultClass \Medoo\Medoo
 */
class UpdateTest extends MedooTestCase
{
    /**
     * @covers \Medoo\Medoo::update()
     * @dataProvider typesProvider
     */
    public function testUpdate($type)
    {
        $this->setType($type);

        $objectData = new Foo();

        $this->database->update("account", [
            "type" => "user",
            "age[+]" => 1,
            "level[-]" => 5,
            "score[*]" => 2,
            "lang" => ["en", "fr"],
            "lang [JSON]" => ["en", "fr"],
            "is_locked" => true,
            "uuid" => Medoo::raw("UUID()"),
            "object" => $objectData
        ], [
            "user_id[<]" => 1000
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                UPDATE "account"
                SET "type" = 'user',
                "age" = "age" + 1,
                "level" = "level" - 5,
                "score" = "score" * 2,
                "lang" = 'a:2:{i:0;s:2:"en";i:1;s:2:"fr";}',
                "lang" = '["en","fr"]',
                "is_locked" = 1,
                "uuid" = UUID(),
                "object" = :MeD7_mK
                WHERE "user_id" < 1000
                EOD,
            'mysql' => <<<EOD
                UPDATE "account"
                SET "type" = 'user',
                "age" = "age" + 1,
                "level" = "level" - 5,
                "score" = "score" * 2,
                "lang" = 'a:2:{i:0;s:2:\"en\";i:1;s:2:\"fr\";}',
                "lang" = '[\"en\",\"fr\"]',
                "is_locked" = 1,
                "uuid" = UUID(),
                "object" = :MeD7_mK
                WHERE "user_id" < 1000
                EOD,
        ], $this->database->queryString);
    }
}
