<?php

namespace Medoo\Tests;

use Medoo\Medoo;

/**
 * @coversDefaultClass \Medoo\Medoo
 */
class WhereTest extends MedooTestCase
{
    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::typeMap()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testBasicWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "email" => "foo@bar.com",
            "user_id" => 200,
            "user_id[>]" => 200,
            "user_id[>=]" => 200,
            "user_id[!]" => 200,
            "age[<>]" => [200, 500],
            "age[><]" => [200, 500],
            "income[>]" => Medoo::raw("COUNT(<average>)"),
            "remote_id" => Medoo::raw("UUID()"),
            "location" => null,
            "is_selected" => true
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            "email" = 'foo@bar.com' AND
            "user_id" = 200 AND
            "user_id" > 200 AND
            "user_id" >= 200 AND
            "user_id" != 200 AND
            ("age" BETWEEN 200 AND 500) AND
            ("age" NOT BETWEEN 200 AND 500) AND
            "income" > COUNT("average") AND
            "remote_id" = UUID() AND
            "location" IS NULL AND
            "is_selected" = 1
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testBetweenDateTimeWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "birthday[<>]" => [date("Y-m-d", mktime(0, 0, 0, 1, 1, 2015)), date("Y-m-d", mktime(0, 0, 0, 1, 1, 2045))]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            ("birthday" BETWEEN '2015-01-01' AND '2045-01-01')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testNotBetweenDateTimeWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "birthday[><]" => [date("Y-m-d", mktime(0, 0, 0, 1, 1, 2015)), date("Y-m-d", mktime(0, 0, 0, 1, 1, 2045))]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            ("birthday" NOT BETWEEN '2015-01-01' AND '2045-01-01')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testBetweenStringWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "location[<>]" => ['New York', 'Santo']
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            ("location" BETWEEN 'New York' AND 'Santo')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testBetweenRawWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "birthday[<>]" => [
                Medoo::raw("to_date(:from, 'YYYY-MM-DD')", [":from" => '2015/05/15']),
                Medoo::raw("to_date(:to, 'YYYY-MM-DD')", [":to" => '2025/05/15'])
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            ("birthday" BETWEEN to_date('2015/05/15', 'YYYY-MM-DD') AND to_date('2025/05/15', 'YYYY-MM-DD'))
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testGreaterDateTimeWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "birthday[>]" => date("Y-m-d", mktime(0, 0, 0, 1, 1, 2045))
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE "birthday" > '2045-01-01'
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testArrayIntValuesWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "user_id" => [2, 123, 234, 54]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            "user_id" IN (2, 123, 234, 54)
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testArrayStringValuesWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "email" => ["foo@bar.com", "cat@dog.com", "admin@medoo.in"]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            "email" IN ('foo@bar.com', 'cat@dog.com', 'admin@medoo.in')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testRawArrayValuesWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            'id' => [
                Medoo::raw('LOWER("FOO")'),
                Medoo::raw('LOWER("BAR")')
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            "id" IN (LOWER("FOO"), LOWER("BAR"))
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testRawNotInArrayValuesWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            'id[!]' => [
                Medoo::raw('LOWER("FOO")'),
                Medoo::raw('LOWER("BAR")')
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            "id" NOT IN (LOWER("FOO"), LOWER("BAR"))
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testNegativeWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "AND" => [
                "user_name[!]" => "foo",
                "user_id[!]" => 1024,
                "email[!]" => ["foo@bar.com", "admin@medoo.in"],
                "city[!]" => null,
                "promoted[!]" => true,
                "location[!]" => Medoo::raw('LOWER("New York")')
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            ("user_name" != 'foo' AND
            "user_id" != 1024 AND
            "email" NOT IN ('foo@bar.com', 'admin@medoo.in') AND
            "city" IS NOT NULL AND
            "promoted" != 1 AND
            "location" != LOWER("New York"))
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testBasicAndRelativityWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "AND" => [
                "user_id[>]" => 200,
                "gender" => "female"
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            ("user_id" > 200 AND "gender" = 'female')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testBasicSingleRelativityWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "user_id[>]" => 200,
            "gender" => "female"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            "user_id" > 200 AND "gender" = 'female'
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testBasicOrRelativityWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "OR" => [
                "user_id[>]" => 200,
                "age[<>]" => [18, 25],
                "gender" => "female"
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            ("user_id" > 200 OR
            ("age" BETWEEN 18 AND 25) OR
            "gender" = 'female')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testCompoundRelativityWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "AND" => [
                "OR" => [
                    "user_name" => "foo",
                    "email" => "foo@bar.com"
                ],
                "password" => "12345"
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            (("user_name" = 'foo' OR "email" = 'foo@bar.com') AND "password" = '12345')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testCompoundDuplicatedKeysWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "AND #comment" => [
                "OR #first comment" => [
                    "user_name" => "foo",
                    "email" => "foo@bar.com"
                ],
                "OR #sencond comment" => [
                    "user_name" => "bar",
                    "email" => "bar@foo.com"
                ]
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            (("user_name" = 'foo' OR "email" = 'foo@bar.com') AND
            ("user_name" = 'bar' OR "email" = 'bar@foo.com'))
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testColumnsRelationshipWhere($type)
    {
        $this->setType($type);

        $this->database->select("post", [
            "[>]account" => "user_id",
        ], [
            "post.content"
        ], [
            "post.restrict[<]account.age",
            "post.type[=]account.type"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "post"."content"
            FROM "post"
            LEFT JOIN "account"
            USING ("user_id")
            WHERE
            "post"."restrict" < "account"."age" AND
            "post"."type" = "account"."type"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testBasicLikeWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "city[~]" => "lon",
            "name[~]" => "some-name"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            ("city" LIKE '%lon%') AND
            ("name" LIKE '%some-name%')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testGroupedLikeWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "city[~]" => ["lon", "foo", "bar"]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            ("city" LIKE '%lon%' OR
            "city" LIKE '%foo%' OR
            "city" LIKE '%bar%')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testNegativeLikeWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "city[!~]" => "lon"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            ("city" NOT LIKE '%lon%')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testNonEscapeLikeWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "city[~]" => "some_where",
            "county[~]" => "[a-f]stan"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            ("city" LIKE 'some_where') AND
            ("county" LIKE '[a-f]stan')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testEscapeLikeWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "city[~]" => "some\_where"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            ("city" LIKE '%some\_where%')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testCompoundLikeWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "content[~]" => ["AND" => ["lon", "on"]],
            "city[~]" => ["OR" => ["lon", "on"]]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            ("content" LIKE '%lon%' AND "content" LIKE '%on%') AND
            ("city" LIKE '%lon%' OR "city" LIKE '%on%')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testWildcardLikeWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "city[~]" => "%stan",
            "company[~]" => "Goo%",
            "location[~]" => "Londo_",
            "name[~]" => "[BCR]at",
            "nickname[~]" => "[!BCR]at"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            ("city" LIKE '%stan') AND
            ("company" LIKE 'Goo%') AND
            ("location" LIKE 'Londo_') AND
            ("name" LIKE '[BCR]at') AND
            ("nickname" LIKE '[!BCR]at')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testMultipleLikeWhere($type)
    {
        $this->setType($type);

        $words = [
            "one",
            "two",
            "three",
            "four",
            "five",
            "six",
            "seven",
            "eight",
            "nine",
            "ten",
            "eleven",
            "twelve"
        ];

        $this->database->select("account", ["title"], ["title[~]" => $words]);

        $this->assertQuery(
            <<<EOD
            SELECT "title"
            FROM "account"
            WHERE
            ("title" LIKE '%one%' OR "title" LIKE '%two%' OR "title" LIKE '%three%' OR "title" LIKE '%four%' OR "title" LIKE '%five%' OR "title" LIKE '%six%' OR "title" LIKE '%seven%' OR "title" LIKE '%eight%' OR "title" LIKE '%nine%' OR "title" LIKE '%ten%' OR "title" LIKE '%eleven%' OR "title" LIKE '%twelve%')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testBasicOrderWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "ORDER" => "user_id"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            ORDER BY "user_id"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testMultipleOrderWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "ORDER" => [
                // Order by column with sorting by customized order.
                "user_id" => [43, 12, 57, 98, 144, 1],

                // Order by column.
                "register_date",

                // Order by column with descending sorting.
                "profile_id" => "DESC",

                // Order by column with ascending sorting.
                "date" => "ASC"
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            ORDER BY FIELD("user_id", 43,12,57,98,144,1),"register_date","profile_id" DESC,"date" ASC
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testOrderWithRawWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "ORDER" => Medoo::raw("<location>, <gender>")
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            ORDER BY "location", "gender"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     */
    public function testFullTextSearchWhere()
    {
        $this->setType("mysql");

        $this->database->select("account", "user_name", [
            "MATCH" => [
                "columns" => ["content", "title"],
                "keyword" => "foo",
                "mode" => "natural"
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE MATCH (`content`, `title`) AGAINST ('foo' IN NATURAL LANGUAGE MODE)
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testRegularExpressionWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            'user_name[REGEXP]' => '[a-z0-9]*'
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE "user_name" REGEXP '[a-z0-9]*'
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testRawWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            'datetime' => Medoo::raw('NOW()')
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE "datetime" = NOW()
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testLimitWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            'LIMIT' => 100
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                SELECT "user_name"
                FROM "account"
                LIMIT 100
                EOD,
            'mssql' => <<<EOD
                SELECT [user_name]
                FROM [account]
                ORDER BY (SELECT 0)
                OFFSET 0 ROWS FETCH NEXT 100 ROWS ONLY
                EOD,
            'oracle' => <<<EOD
                SELECT "user_name"
                FROM "account"
                OFFSET 0 ROWS FETCH NEXT 100 ROWS ONLY
                EOD,
        ], $this->database->queryString);
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testLimitOffsetWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            'LIMIT' => [20, 100]
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                SELECT "user_name"
                FROM "account"
                LIMIT 100 OFFSET 20
                EOD,
            'mssql' => <<<EOD
                SELECT [user_name]
                FROM [account]
                ORDER BY (SELECT 0)
                OFFSET 20 ROWS FETCH NEXT 100 ROWS ONLY
                EOD,
            'oracle' => <<<EOD
                SELECT "user_name"
                FROM "account"
                OFFSET 20 ROWS FETCH NEXT 100 ROWS ONLY
                EOD,
        ], $this->database->queryString);
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testGroupWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            'GROUP' => 'type',
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            GROUP BY "type"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testGroupWithArrayWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            'GROUP' => [
                'type',
                'age',
                'gender'
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            GROUP BY "type","age","gender"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testGroupWithRawWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            'GROUP' => Medoo::raw("<location>, <gender>")
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            GROUP BY "location", "gender"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testHavingWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            'HAVING' => [
                'user_id[>]' => 500
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            HAVING "user_id" > 500
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testHavingWithRawWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            'HAVING' => Medoo::raw('<location> = LOWER("NEW YORK")')
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            HAVING "location" = LOWER("NEW YORK")
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testHavingWithAggregateRawWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", [
            "total" => Medoo::raw('SUM(<salary>)')
        ], [
            'HAVING' => Medoo::raw('SUM(<salary>) > 1000')
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT SUM("salary") AS "total"
            FROM "account"
            HAVING SUM("salary") > 1000
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testRawWhereClause($type)
    {
        $this->setType($type);

        $this->database->select(
            "account",
            "user_name",
            Medoo::raw("WHERE <id> => 10")
        );

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE "id" => 10
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::select()
     * @covers ::dataImplode()
     * @covers ::whereClause()
     * @dataProvider typesProvider
     */
    public function testRawWhereWithJoinClause($type)
    {
        $this->setType($type);

        $this->database->select(
            "post",
            [
                "[>]account" => "user_id",
            ],
            [
                "post.content"
            ],
            Medoo::raw("WHERE <id> => 10")
        );

        $this->assertQuery(
            <<<EOD
            SELECT "post"."content"
            FROM "post"
            LEFT JOIN "account" USING ("user_id")
            WHERE "id" => 10
            EOD,
            $this->database->queryString
        );
    }
}
