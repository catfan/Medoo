<?php

namespace Medoo\Tests;

class SelectTest extends MedooTestCase
{
    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectAll($type)
    {
        $this->setType($type);

        $this->database->select("account", "*");

        $this->assertQuery(
            <<<EOD
            SELECT * FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectColumns($type)
    {
        $this->setType($type);

        $this->database->select("account", ["name", "id"]);

        $this->assertQuery(
            <<<EOD
            SELECT "name","id"
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectColumnsWithAlias($type)
    {
        $this->setType($type);

        $this->database->select("account", ["name(nickname)", "id"]);

        $this->assertQuery(
            <<<EOD
            SELECT "name" AS "nickname","id"
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectColumnsWithType($type)
    {
        $this->setType($type);

        $this->database->select("account", ["name[String]", "data [JSON]"]);

        $this->assertQuery(
            <<<EOD
            SELECT "name","data"
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectColumnsWithAliasAndType($type)
    {
        $this->setType($type);

        $this->database->select("account", ["name (nickname) [String]", "data [JSON]"]);

        $this->assertQuery(
            <<<EOD
            SELECT "name" AS "nickname","data"
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectWithWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", [
            "name",
            "id"
        ], [
            "ORDER" => "age"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "name","id"
            FROM "account"
            ORDER BY "age"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectWithLeftJoin($type)
    {
        $this->setType($type);

        $this->database->select("account", [
           "[>]post" => "user_id" 
        ], [
            "account.name",
            "post.title"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "account"."name","post"."title"
            FROM "account"
            LEFT JOIN "post" USING ("user_id")
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectWithRightJoin($type)
    {
        $this->setType($type);

        $this->database->select("account", [
           "[<]post" => "user_id" 
        ], [
            "account.name",
            "post.title"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "account"."name","post"."title"
            FROM "account"
            RIGHT JOIN "post" USING ("user_id")
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectWithFullJoin($type)
    {
        $this->setType($type);

        $this->database->select("account", [
           "[<>]post" => "user_id" 
        ], [
            "account.name",
            "post.title"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "account"."name","post"."title"
            FROM "account"
            FULL JOIN "post" USING ("user_id")
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectWithInnerJoin($type)
    {
        $this->setType($type);

        $this->database->select("account", [
           "[><]post" => "user_id" 
        ], [
            "account.name",
            "post.title"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "account"."name","post"."title"
            FROM "account"
            INNER JOIN "post" USING ("user_id")
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectWithSameKeysJoin($type)
    {
        $this->setType($type);

        $this->database->select("account", [
            "[>]photo" => ["user_id", "avatar_id"],
        ], [
            "account.name",
            "photo.link"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "account"."name","photo"."link"
            FROM "account"
            LEFT JOIN "photo"
            USING ("user_id", "avatar_id")
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectWithKeyJoin($type)
    {
        $this->setType($type);

        $this->database->select("account", [
           "[>]post" => ["user_id" => "author_id"],
        ], [
            "account.name",
            "post.title"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "account"."name","post"."title"
            FROM "account"
            LEFT JOIN "post"
            ON "account"."user_id" = "post"."author_id"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectWithAliasJoin($type)
    {
        $this->setType($type);

        $this->database->select("account", [
            "[>]post (main_post)" => ["user_id" => "author_id"],
        ], [
            "account.name",
            "main_post.title"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "account"."name","main_post"."title"
            FROM "account"
            LEFT JOIN "post" AS "main_post"
            ON "account"."user_id" = "main_post"."author_id"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectWithReferJoin($type)
    {
        $this->setType($type);

        $this->database->select("account", [
            "[>]post" => ["user_id" => "author_id"],
            "[>]album" => ["post.author_id" => "user_id"],
        ], [
            "account.name",
            "post.title",
            "album.link"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "account"."name","post"."title","album"."link"
            FROM "account"
            LEFT JOIN "post"
            ON "account"."user_id" = "post"."author_id"
            LEFT JOIN "album"
            ON "post"."author_id" = "album"."user_id"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectWithMultipleConditionJoin($type)
    {
        $this->setType($type);

        $this->database->select("account", [
            "[>]album" => ["author_id" => "user_id"],
            "[>]post" => [
                "user_id" => "author_id",
                "album.user_id" => "owner_id"
            ]
        ], [
            "account.name",
            "post.title",
            "album.link"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "account"."name","post"."title","album"."link"
            FROM "account"
            LEFT JOIN "album"
            ON "account"."author_id" = "album"."user_id"
            LEFT JOIN "post"
            ON "account"."user_id" = "post"."author_id"
            AND "album"."user_id" = "post"."owner_id"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::select()
     * @dataProvider typesProvider
     */
    public function testSelectWithAdditionalConditionJoin($type)
    {
        $this->setType($type);

        $this->database->select("account", [
            "[>]post" => [
                "user_id" => "author_id",
                "AND" => [
                    "post.id[>]" => 10
                ]
            ]
        ], [
            "account.name",
            "post.title"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "account"."name","post"."title"
            FROM "account"
            LEFT JOIN "post"
            ON "account"."user_id" = "post"."author_id"
            AND "post"."id" > 10
            EOD,
            $this->database->queryString
        );
    }
}
