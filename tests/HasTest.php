<?php

namespace Medoo\Tests;

/**
 * @coversDefaultClass \Medoo\Medoo
 */
class HasTest extends MedooTestCase
{
    /**
     * @covers ::has()
     * @covers ::selectContext()
     * @dataProvider typesProvider
     */
    public function testHas($type)
    {
        $this->setType($type);

        $this->database->has("account", [
            "user_name" => "foo"
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                SELECT EXISTS(SELECT 1 FROM "account" WHERE "user_name" = 'foo')
                EOD,
            'mssql' => <<<EOD
                SELECT TOP 1 1 FROM [account] WHERE [user_name] = 'foo'
                EOD
        ], $this->database->queryString);
    }
}
