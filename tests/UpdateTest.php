<?php

namespace Medoo\Tests;

class UpdateTest extends MedooTestCase
{
    /**
     * @covers Medoo::update()
     * @dataProvider typesProvider
     */
    public function testUpdate($type)
    {
        $this->setType($type);

        $this->database->update("account", [
            "type" => "user",

            // All age plus one
            "age[+]" => 1,

            // All level subtract 5
            "level[-]" => 5,

            // All score multiplied by 2
            "score[*]" => 2,

            // Array value
            "lang" => ["en", "fr"],

            // Array value encoded as JSON
            "lang [JSON]" => ["en", "fr"],

            // Boolean value
            "is_locked" => true
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
                "is_locked" = 1
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
                "is_locked" = 1
                WHERE "user_id" < 1000
                EOD,
        ], $this->database->queryString);
    }
}
