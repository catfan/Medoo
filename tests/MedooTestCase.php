<?php

namespace Medoo\Tests;

use Medoo\Medoo;
use PHPUnit\Framework\TestCase;

class MedooTestCase extends TestCase
{
    protected $database;

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
        $this->database->type = $type;
    }

    public function expectedQuery($expected): string
    {
        $identifier = [
            'mysql' => '`$1`',
            'mssql' => '[$1]'
        ];

        return preg_replace(
            '/(?!\'[^\s]+\s?)"([\p{L}_][\p{L}\p{N}@$#\-_]*)"(?!\s?[^\s]+\')/u',
            $identifier[$this->database->type] ?? '"$1"',
            str_replace("\n", " ", $expected)
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
