<?php

namespace Medoo\Tests;

/**
 * @coversDefaultClass \Medoo\Medoo
 */
class AggregateTest extends MedooTestCase
{
    /**
     * @covers ::count()
     * @dataProvider typesProvider
     */
    public function testCount($type)
    {
        $this->setType($type);

        $this->database->count("account", [
            "gender" => "female"
        ]);

        $this->assertQuery(
            <<<EOD
            SELECT COUNT(*)
            FROM "account"
            WHERE "gender" = 'female'
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::max()
     * @dataProvider typesProvider
     */
    public function testMax($type)
    {
        $this->setType($type);

        $this->database->max("account", "age");

        $this->assertQuery(
            <<<EOD
            SELECT MAX("age")
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::min()
     * @dataProvider typesProvider
     */
    public function testMin($type)
    {
        $this->setType($type);

        $this->database->min("account", "age");

        $this->assertQuery(
            <<<EOD
            SELECT MIN("age")
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::avg()
     * @dataProvider typesProvider
     */
    public function testAvg($type)
    {
        $this->setType($type);

        $this->database->avg("account", "age");

        $this->assertQuery(
            <<<EOD
            SELECT AVG("age")
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }

    /**
     * @covers ::sum()
     * @dataProvider typesProvider
     */
    public function testSum($type)
    {
        $this->setType($type);

        $this->database->sum("account", "money");

        $this->assertQuery(
            <<<EOD
            SELECT SUM("money")
            FROM "account"
            EOD,
            $this->database->queryString
        );
    }
}
