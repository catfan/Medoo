<?php
namespace Medoo\Tests;

class GetTest extends MedooTestCase
{
    /**
     * @covers Medoo::get()
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
     * @covers Medoo::get()
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
     * @covers Medoo::get()
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
        ], [
            "ORDER" => "post.rate"
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                SELECT "post"."content","account"."user_name"
                FROM "post"
                LEFT JOIN "account" USING ("user_id")
                ORDER BY "post"."rate"
                LIMIT 1
                EOD,
            'mssql' => <<<EOD
                SELECT [post].[content],[account].[user_name]
                FROM [post]
                LEFT JOIN [account] USING ([user_id])
                ORDER BY [post].[rate]
                OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY
                EOD,
            'oracle' => <<<EOD
                SELECT "post"."content","account"."user_name"
                FROM "post"
                LEFT JOIN "account" USING ("user_id")
                ORDER BY "post"."rate"
                OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY
                EOD,
        ], $this->database->queryString);
    }
}