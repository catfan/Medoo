<?php

namespace Medoo\Tests;

use Medoo\Medoo;

#[\PHPUnit\Framework\Attributes\CoversClass(\Medoo\Medoo::class)]
class WhereTest extends MedooTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testBasicWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testBetweenDateTimeWhere(string $type): void
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "birthday[<>]" => ['2015-01-01', '2045-01-01']
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testNotBetweenDateTimeWhere(string $type): void
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "birthday[><]" => ['2015-01-01', '2045-01-01']
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testBetweenStringWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testBetweenRawWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testGreaterDateTimeWhere(string $type): void
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "birthday[>]" => '2045-01-01'
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testArrayIntValuesWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testArrayStringValuesWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testEmptyArrayValuesWhere(string $type): void
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "user_id" => []
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE 1 = 0
            EOD,
            $this->database->queryString
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testEmptyNotInArrayValuesWhere(string $type): void
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "user_id[!]" => []
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT "user_name"
            FROM "account"
            WHERE 1 = 1
            EOD,
            $this->database->queryString
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testRawArrayValuesWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testRawNotInArrayValuesWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testNegativeWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testBasicAndRelativityWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testBasicSingleRelativityWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testBasicOrRelativityWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testCompoundRelativityWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testCompoundDuplicatedKeysWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testColumnsRelationshipWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testBasicLikeWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testGroupedLikeWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testNegativeLikeWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testNonEscapeLikeWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testEscapeLikeWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testCompoundLikeWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testWildcardLikeWhere(string $type): void
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

    /** @return array<string, array{string, string}> */
    public static function likePatternsProvider(): array
    {
        $plain = str_repeat('a', 8192);
        $brackets = str_repeat('[', 8192);

        return [
            'plain text' => ['word', '%word%'],
            'empty text' => ['', '%%'],
            'percent' => ['%', '%'],
            'asterisk' => ['a*b', 'a*b'],
            'question mark' => ['a?b', 'a?b'],
            'exclamation mark' => ['a!b', 'a!b'],
            'hash' => ['a#b', 'a#b'],
            'caret' => ['a^b', 'a^b'],
            'underscore' => ['a_b', 'a_b'],
            'escaped markers' => ['\\*\\?\\!\\#\\^\\_', '%\\*\\?\\!\\#\\^\\_%'],
            'double backslash' => ['\\\\_', '%\\\\_%'],
            'escaped percent' => ['a\\%b', 'a\\%b'],
            'bracket group' => ['[abc]', '[abc]'],
            'empty bracket group' => ['[]', '%[]%'],
            'multiple empty groups' => ['[][]', '[][]'],
            'escaped opening bracket' => ['\\[abc]', '%\\[abc]%'],
            'escaped closing bracket' => ['[abc\\]', '%[abc\\]%'],
            'later closing bracket' => ['[abc\\]]', '[abc\\]]'],
            'line break in group' => ["[a\nb]", "%[a\nb]%"],
            'group on later line' => ["[a\n[b]", "[a\n[b]"],
            'plain Unicode' => ['城市', '%城市%'],
            'Unicode bracket group' => ['[城市]', '[城市]'],
            'long plain text' => [$plain, '%' . $plain . '%'],
            'long unmatched brackets' => [$brackets, '%' . $brackets . '%'],
            'long escaped closing bracket' => [$brackets . '\\]', '%' . $brackets . '\\]%'],
            'long bracket group' => [$brackets . ']', $brackets . ']'],
            'late wildcard after plain text' => [$plain . '_', $plain . '_'],
            'late wildcard after brackets' => [$brackets . '_', $brackets . '_']
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('likePatternsProvider')]
    public function testLikePatternDetection(string $pattern, string $expected): void
    {
        foreach (['~' => 'LIKE', '!~' => 'NOT LIKE'] as $operator => $keyword) {
            $this->database->select('account', 'user_name', ["city[{$operator}]" => $pattern]);

            $this->assertSame(
                'SELECT "user_name" FROM "account" WHERE ("city" ' . $keyword . ' ' . $this->database->quote($expected) . ')',
                $this->database->queryString
            );
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testMultipleLikeWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testBasicOrderWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testMultipleOrderWhere(string $type): void
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

        $this->assertQuery([
            'default' => <<<EOD
                SELECT "user_name"
                FROM "account"
                ORDER BY CASE "user_id" WHEN 43 THEN 1 WHEN 12 THEN 2 WHEN 57 THEN 3 WHEN 98 THEN 4 WHEN 144 THEN 5 WHEN 1 THEN 6 ELSE 0 END,"register_date","profile_id" DESC,"date" ASC
                EOD,
            'mysql' => <<<EOD
                SELECT "user_name"
                FROM "account"
                ORDER BY FIELD("user_id", 43,12,57,98,144,1),"register_date","profile_id" DESC,"date" ASC
                EOD
        ], $this->database->queryString);
    }

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testMultipleOrderWhereWithStringValues(string $type): void
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            "ORDER" => [
                "status" => ["active", "pending", "disabled"]
            ]
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                SELECT "user_name"
                FROM "account"
                ORDER BY CASE "status" WHEN 'active' THEN 1 WHEN 'pending' THEN 2 WHEN 'disabled' THEN 3 ELSE 0 END
                EOD,
            'mysql' => <<<EOD
                SELECT "user_name"
                FROM "account"
                ORDER BY FIELD("status", 'active','pending','disabled')
                EOD
        ], $this->database->queryString);
    }

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testOrderWithRawWhere(string $type): void
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

    public function testFullTextSearchWhere(): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testRegularExpressionWhere(string $type): void
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            'user_name[REGEXP]' => '[a-z0-9]*'
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                SELECT "user_name"
                FROM "account"
                WHERE "user_name" REGEXP '[a-z0-9]*'
                EOD,
            'pgsql' => <<<EOD
                SELECT "user_name"
                FROM "account"
                WHERE "user_name" ~ '[a-z0-9]*'
                EOD,
            'oracle' => <<<EOD
                SELECT "user_name"
                FROM "account"
                WHERE REGEXP_LIKE("user_name", '[a-z0-9]*')
                EOD,
            'mssql' => <<<EOD
                SELECT "user_name"
                FROM "account"
                WHERE REGEXP_LIKE("user_name", '[a-z0-9]*')
                EOD
        ], $this->database->queryString);
    }

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testNegativeRegularExpressionWhere(string $type): void
    {
        $this->setType($type);

        $this->database->select("account", "user_name", [
            'user_name[!REGEXP]' => '[a-z0-9]*'
        ]);

        $this->assertQuery([
            'default' => <<<EOD
                SELECT "user_name"
                FROM "account"
                WHERE "user_name" NOT REGEXP '[a-z0-9]*'
                EOD,
            'pgsql' => <<<EOD
                SELECT "user_name"
                FROM "account"
                WHERE "user_name" !~ '[a-z0-9]*'
                EOD,
            'oracle' => <<<EOD
                SELECT "user_name"
                FROM "account"
                WHERE NOT REGEXP_LIKE("user_name", '[a-z0-9]*')
                EOD,
            'mssql' => <<<EOD
                SELECT "user_name"
                FROM "account"
                WHERE NOT REGEXP_LIKE("user_name", '[a-z0-9]*')
                EOD
        ], $this->database->queryString);
    }

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testRawWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testLimitWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testLimitOffsetWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testGroupWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testGroupWithArrayWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testGroupWithRawWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testHavingWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testHavingWithRawWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testHavingWithAggregateRawWhere(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testRawWhereClause(string $type): void
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
    public function testRawWhereWithJoinClause(string $type): void
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
