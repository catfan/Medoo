<?php

namespace Medoo\Tests;

use Medoo\Medoo;
use InvalidArgumentException;

/**
 * @coversDefaultClass \Medoo\Medoo
 */
class SelectTest extends MedooTestCase
{
    /**
     * @covers ::select()
     * @covers ::selectContext()
     * @covers ::isJoin()
     * @covers ::columnMap()
     * @covers ::columnPush()
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
     * @covers ::select()
     * @covers ::selectContext()
     * @dataProvider typesProvider
     */
    public function testSelectTableWithAlias($type)
    {
        $this->setType($type);

        $this->database->select("account (user)", "name");

        $this->assertQuery(
            <<<EOD
            SELECT "name"
            FROM "account" AS "user"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::columnMap()
     * @covers ::columnPush()
     * @dataProvider typesProvider
     */
    public function testSelectSingleColumn($type)
    {
        $this->setType($type);

        $this->database->select("account", "name");

        $this->assertQuery(
            <<<EOD
            SELECT "name"
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::columnMap()
     * @covers ::columnPush()
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
     * @covers ::columnMap()
     * @covers ::columnPush()
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
     * @covers ::columnMap()
     * @covers ::columnPush()
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
     * @covers ::columnMap()
     * @covers ::columnPush()
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
     * @covers ::columnMap()
     * @covers ::columnPush()
     * @dataProvider typesProvider
     */
    public function testSelectColumnsWithRaw($type)
    {
        $this->setType($type);

        $this->database->select("account", [
            "id [String]" => Medoo::raw("UUID()")
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT UUID() AS "id"
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::selectContext()
     * @covers ::isJoin()
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
     * @covers ::select()
     * @covers ::selectContext()
     * @covers ::isJoin()
     * @covers ::buildJoin()
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
            LEFT JOIN "post"
            USING ("user_id")
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::isJoin()
     * @covers ::buildJoin()
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
            RIGHT JOIN "post"
            USING ("user_id")
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::isJoin()
     * @covers ::buildJoin()
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
            FULL JOIN "post"
            USING ("user_id")
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::isJoin()
     * @covers ::buildJoin()
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
            INNER JOIN "post"
            USING ("user_id")
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::isJoin()
     * @covers ::buildJoin()
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
     * @covers ::isJoin()
     * @covers ::buildJoin()
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
     * @covers ::isJoin()
     * @covers ::buildJoin()
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
     * @covers ::isJoin()
     * @covers ::buildJoin()
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
     * @covers ::isJoin()
     * @covers ::buildJoin()
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
     * @covers ::isJoin()
     * @covers ::buildJoin()
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

    /**
     * @covers ::isJoin()
     * @covers ::buildJoin()
     * @dataProvider typesProvider
     */
    public function testSelectRawJoin($type)
    {
        $this->setType($type);

        $this->database->select("account", [
            "[>]post" => Medoo::raw("ON <account.user_id> = <post.author_id>")
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
     * @covers ::columnMap()
     * @covers ::columnPush()
     * @dataProvider typesProvider
     */
    public function testSelectAllWithJoin($type)
    {
        $this->setType($type);

        $this->expectException(InvalidArgumentException::class);

        $this->database->select("account", [
            "[>]post" => "user_id"
        ], [
            "account.*"
        ]);
    }

    /**
     * @covers ::columnMap()
     * @covers ::columnPush()
     * @dataProvider typesProvider
     */
    public function testSelectWithDataMapping($type)
    {
        $this->setType($type);

        $this->database->select("post", [
            "[>]account" => ["user_id"]
        ], [
            "post.content",

            "userData" => [
                "account.user_id",
                "account.email",

                "meta" => [
                    "account.location",
                    "account.gender"
                ]
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "post"."content","account"."user_id","account"."email","account"."location","account"."gender"
            FROM "post"
            LEFT JOIN "account"
            USING ("user_id")
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::columnMap()
     * @covers ::columnPush()
     * @dataProvider typesProvider
     */
    public function testSelectWithIndexMapping($type)
    {
        $this->setType($type);

        $this->database->select("account", [
            "user_id" => [
                "name (nickname)",
                "location"
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_id","name" AS "nickname","location"
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::columnMap()
     * @covers ::columnPush()
     * @dataProvider typesProvider
     */
    public function testSelectWithDistinct($type)
    {
        $this->setType($type);

        $this->database->select("account", [
            "@location",
            "nickname"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT DISTINCT "location","nickname"
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::columnMap()
     * @covers ::columnPush()
     * @dataProvider typesProvider
     */
    public function testSelectWithDistinctDiffOrder($type)
    {
        $this->setType($type);

        $this->database->select("account", [
            "location",
            "@nickname"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT DISTINCT "nickname","location"
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::columnMap()
     * @covers ::columnPush()
     * @dataProvider typesProvider
     */
    public function testSelectWithUnicodeCharacter($type)
    {
        $this->setType($type);

        $this->database->select("considérer", [
            "name (名前)",
            "положение (ロケーション)"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "name" AS "名前","положение" AS "ロケーション"
            FROM "considérer"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::columnMap()
     * @covers ::columnPush()
     * @dataProvider typesProvider
     */
    public function testSelectWithHyphenCharacter($type)
    {
        $this->setType($type);

        $this->database->select("account", [
            "nick-name"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "nick-name"
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::columnMap()
     * @covers ::columnPush()
     * @dataProvider typesProvider
     */
    public function testSelectWithSingleCharacter($type)
    {
        $this->setType($type);

        $this->database->select("a", [
            "[>]e" => ["f"]
        ], [
            "b (c)"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "b" AS "c"
            FROM "a"
            LEFT JOIN "e" USING ("f")
            EOD,
            $this->database->queryString
        );
    }
}
