<?php

namespace Medoo\Tests;

use Medoo\Medoo;

class WhereTest extends MedooTestCase
{
    /**
     * @covers Medoo::where()
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
            "age[><]" => [200, 500]
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
            ("age" NOT BETWEEN 200 AND 500)
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
                "promoted[!]" => true
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
            "promoted" != 1)
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
     * @dataProvider typesProvider
     */
    public function testBasicLikeWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "city[~]" => "lon"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE
            ("city" LIKE '%lon%')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
     * @dataProvider typesProvider
     */
    public function testWildcardLikeWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "city[~]" => "%stan",
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
            ("location" LIKE 'Londo_') AND
            ("name" LIKE '[BCR]at') AND
            ("nickname" LIKE '[!BCR]at')
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
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
     * @covers Medoo::where()
     * @dataProvider typesProvider
     */
    public function testHavingWhere($type)
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            'GROUP' => 'type',

            'HAVING' => [
                'user_id[>]' => 500
            ]
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            GROUP BY "type"
            HAVING "user_id" > 500
            EOD,
            $this->database->queryString
        );
    }
}
