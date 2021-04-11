<?php

namespace Medoo\Tests;

use Medoo\Medoo;
use InvalidArgumentException;

class QuoteTest extends MedooTestCase
{
    /**
     * @covers Medoo::quote()
     * @dataProvider typesProvider
     */
    public function testQuote($type)
    {
        $this->setType($type);

        $quotedString = $this->database->quote("Co'mpl''ex \"st'\"ring");

        $expected = [
            'mysql' => <<<EOD
                'Co\'mpl\'\'ex \"st\'\"ring'
                EOD,
            'mssql' => <<<EOD
                'Co''mpl''''ex "st''"ring'
                EOD,
            'sqlite' => <<<EOD
                'Co''mpl''''ex "st''"ring'
                EOD,
            'pgsql' => <<<EOD
                'Co''mpl''''ex "st''"ring'
                EOD,
            'oracle' => <<<EOD
                'Co''mpl''''ex "st''"ring'
                EOD
        ];

        $this->assertEquals($expected[$type], $quotedString);
    }

    /**
     * @covers Medoo::columnQuote()
     */
    public function testColumnQuote()
    {
        $this->assertEquals('"ColumnName"', $this->database->columnQuote("ColumnName"));
        $this->assertEquals('"Column"."name"', $this->database->columnQuote("Column.name"));
        $this->assertEquals('"Column"."Name"', $this->database->columnQuote("Column.Name"));
    }

    public function columnNamesProvider(): array
    {
        return [
            ["9ColumnName"],
            ["@ColumnName"],
            [".ColumnName"],
            ["ColumnName."],
            ["_ColumnName"],
            ["ColumnName (alias)"]
        ];
    }

    /**
     * @covers Medoo::columnQuote
     * @dataProvider columnNamesProvider
     */
    public function testIncorrectColumnQuote($column)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->database->columnQuote($column);
    }

    /**
     * @covers Medoo::tableQuote()
     */
    public function testTableQuote()
    {
        $this->assertEquals('"TableName"', $this->database->tableQuote("TableName"));
    }

    /**
     * @covers Medoo::tableQuote()
     */
    public function testPrefixTableQuote()
    {
        $database = new Medoo([
            'testMode' => true,
            'prefix' => 'PREFIX_'
        ]);

        $this->assertEquals('"PREFIX_TableName"', $database->tableQuote("TableName"));
    }

    public function tableNamesProvider(): array
    {
        return [
            ["9TableName"],
            ["@TableName"],
            [".TableName"],
            ["TableName."],
            ["_TableName"],
            ["Table.name"]
        ];
    }

    /**
     * @covers Medoo::tableQuote()
     * @dataProvider tableNamesProvider
     */
    public function testIncorrectTableQuote($table)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->database->tableQuote($table);
    }
}
