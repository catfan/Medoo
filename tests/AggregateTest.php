<?php

namespace Medoo\Tests;

#[\PHPUnit\Framework\Attributes\CoversClass(\Medoo\Medoo::class)]
class AggregateTest extends MedooTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
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

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(MedooTestCase::class, 'typesProvider')]
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
