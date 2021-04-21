<?php

namespace Medoo\Tests;

/**
 * @coversDefaultClass \Medoo\Medoo
 */
class GetTest extends MedooTestCase
{
    /**
     * @covers ::get()
     * @dataProvider typesProvider
     */
    public function testGet($type)
    {
        $this->setType($type);

        $this->database->get("account", "email", [
            "user_id" => 1234
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                SELECT "email"
                FROM "account"
                WHERE "user_id" = 1234
                LIMIT 1
                EOD,
            'mssql' => <<<EOD
                SELECT [email]
                FROM [account]
                WHERE [user_id] = 1234
                ORDER BY (SELECT 0)
                OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY
                EOD,
            'oracle' => <<<EOD
                SELECT "email"
                FROM "account"
                WHERE "user_id" = 1234
                OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY
                EOD,
        ], $this->database->queryString);
    }

    /**
     * @covers ::get()
     * @dataProvider typesProvider
     */
    public function testGetWithColumns($type)
    {
        $this->setType($type);

        $this->database->get("account", [
            "email",
            "location"
        ], [
            "user_id" => 1234
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                SELECT "email","location"
                FROM "account"
                WHERE "user_id" = 1234
                LIMIT 1
                EOD,
            'mssql' => <<<EOD
                SELECT [email],[location]
                FROM [account]
                WHERE [user_id] = 1234
                ORDER BY (SELECT 0)
                OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY
                EOD,
            'oracle' => <<<EOD
                SELECT "email","location"
                FROM "account"
                WHERE "user_id" = 1234
                OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY
                EOD,
        ], $this->database->queryString);
    }

    /**
     * @covers ::get()
     * @dataProvider typesProvider
     */
    public function testGetWithJoin($type)
    {
        $this->setType($type);

        $this->database->get("post", [
            "[>]account" => "user_id"
        ], [
            "post.content",
            "account.user_name"
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                SELECT "post"."content","account"."user_name"
                FROM "post"
                LEFT JOIN "account" USING ("user_id")
                LIMIT 1
                EOD,
            'mssql' => <<<EOD
                SELECT [post].[content],[account].[user_name]
                FROM [post]
                LEFT JOIN [account] USING ([user_id])
                ORDER BY (SELECT 0)
                OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY
                EOD,
            'oracle' => <<<EOD
                SELECT "post"."content","account"."user_name"
                FROM "post"
                LEFT JOIN "account" USING ("user_id")
                OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY
                EOD,
        ], $this->database->queryString);
    }

    /**
     * @covers ::get()
     * @dataProvider typesProvider
     */
    public function testGetWithJoinAndWhere($type)
    {
        $this->setType($type);

        $this->database->get("post", [
            "[>]account" => "user_id"
        ], [
            "post.content",
            "account.user_name"
        ], [
            'account.age[>]' => 18
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                SELECT "post"."content","account"."user_name"
                FROM "post"
                LEFT JOIN "account" USING ("user_id")
                WHERE "account"."age" > 18
                LIMIT 1
                EOD,
            'mssql' => <<<EOD
                SELECT [post].[content],[account].[user_name]
                FROM [post]
                LEFT JOIN [account] USING ([user_id])
                WHERE [account].[age] > 18
                ORDER BY (SELECT 0)
                OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY
                EOD,
            'oracle' => <<<EOD
                SELECT "post"."content","account"."user_name"
                FROM "post"
                LEFT JOIN "account" USING ("user_id")
                WHERE "account"."age" > 18
                OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY
                EOD,
        ], $this->database->queryString);
    }
}
