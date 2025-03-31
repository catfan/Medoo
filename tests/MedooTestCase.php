<?php

namespace Medoo\Tests;

use Medoo\Medoo;
use PHPUnit\Framework\TestCase;

class MedooTestCase extends TestCase
{
    protected $database;

    public $tableAliasConnector = ' AS ';
    public $quotePattern = '"$1"';

    public function setUp(): void
    {
        $this->database = new Medoo([
            'testMode' => true
        ]);
    }

    public function typesProvider(): array
    {
        return [
            'MySQL' => ['mysql'],
            'MSSQL' => ['mssql'],
            'SQLite' => ['sqlite'],
            'PostgreSQL' => ['pgsql'],
            'Oracle' => ['oracle']
        ];
    }

    public function setType($type): void
    {
        $this->database->setupType($type);

        if ($type === 'oracle') {
            $this->tableAliasConnector = ' ';
        } elseif ($type === 'mysql') {
            $this->quotePattern = '`$1`';
        } elseif ($type === 'mssql') {
            $this->quotePattern = '[$1]';
        }
    }

    public function expectedQuery($expected): string
    {
        $result = preg_replace(
            '/(?!\'[^\s]+\s?)"([\p{L}_][\p{L}\p{N}@$#\-_]*)"(?!\s?[^\s]+\')/u',
            $this->quotePattern,
            str_replace("\n", " ", $expected)
        );

        return str_replace(
            ' @AS ',
            $this->tableAliasConnector,
            $result
        );
    }

    public function assertQuery($expected, $query): void
    {
        if (is_array($expected)) {
            $this->assertEquals(
                $this->expectedQuery($expected[$this->database->type] ?? $expected['default']),
                $query
            );
        } else {
            $this->assertEquals($this->expectedQuery($expected), $query);
        }
    }
}

class Foo
{
    public $bar = "cat";

    public function __wakeup()
    {
        $this->bar = "dog";
    }
}
