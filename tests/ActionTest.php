<?php

namespace Medoo\Tests;

use Medoo\Medoo;
use PDO;

/**
 * @coversDefaultClass \Medoo\Medoo
 */
class ActionTest extends MedooTestCase
{

    public function setUp(): void
    {
        parent::setUp();

        $this->database->pdo = new class extends PDO
        {
            public $testBeginTransaction = 0;
            public $testRollBack = 0;
            public $testCommit = 0;

            function __construct()
            {
            }

            function beginTransaction(): bool
            {
                $this->testBeginTransaction++;
                return true;
            }

            function rollBack(): bool
            {
                $this->testRollBack++;
                return true;
            }

            function commit(): bool
            {
                $this->testCommit++;
                return true;
            }
        };
    }

    public function commitReturnsProvider(): array
    {
        return [
            'return null' => [null],
            'return bool' => [true],
            'return string' => ['string'],
            'return object' => [new \stdClass],
            'return 1' => [1],
            'return 0' => [0],
            'return array' => [[]]
        ];
    }

    public function rollBackReturnsProvider(): array
    {
        return [
            'return bool' => [false],
            'throw exception' => [new \Exception]
        ];
    }

    /**
     * @covers ::action()
     * @covers ::onActionCommitted()
     * @covers ::onActionRolledBack()
     * @covers ::onActionFinished()
     * @covers ::callActionCommitted()
     * @dataProvider commitReturnsProvider
     */
    public function testActionCommit($return)
    {
        $onActionCommitted = 0;
        $onActionRolledBack = 0;
        $onActionFinished = 0;

        $this->database->action(function (Medoo $database) use ($return, &$onActionCommitted, &$onActionRolledBack, &$onActionFinished) {
            $database->onActionCommitted(function () use (&$onActionCommitted) {
                $onActionCommitted++;
            });
            $database->onActionCommitted(function () use (&$onActionCommitted) {
                $onActionCommitted++;
            });

            $database->onActionRolledBack(function () use (&$onActionRolledBack) {
                $onActionRolledBack++;
            });
            $database->onActionRolledBack(function () use (&$onActionRolledBack) {
                $onActionRolledBack++;
            });

            $database->onActionFinished(function () use (&$onActionFinished) {
                $onActionFinished++;
            });
            $database->onActionFinished(function () use (&$onActionFinished) {
                $onActionFinished++;
            });

            return $return;
        });

        $this->assertEquals($onActionCommitted, 2);
        $this->assertEquals($onActionRolledBack, 0);
        $this->assertEquals($onActionFinished, 2);

        $this->assertEquals($this->database->pdo->testBeginTransaction, 1);
        $this->assertEquals($this->database->pdo->testRollBack, 0);
        $this->assertEquals($this->database->pdo->testCommit, 1);
    }

    /**
     * @covers ::action()
     * @covers ::onActionCommitted()
     * @covers ::onActionRolledBack()
     * @covers ::onActionFinished()
     * @covers ::callActionRolledBack()
     * @dataProvider rollBackReturnsProvider
     */
    public function testActionRollBack($return)
    {
        $onActionCommitted = 0;
        $onActionRolledBack = 0;
        $onActionFinished = 0;
        $throwException = 0;

        try {
            $this->database->action(function (Medoo $database) use ($return, &$onActionCommitted, &$onActionRolledBack, &$onActionFinished) {
                $database->onActionCommitted(function () use (&$onActionCommitted) {
                    $onActionCommitted++;
                });
                $database->onActionCommitted(function () use (&$onActionCommitted) {
                    $onActionCommitted++;
                });

                $database->onActionRolledBack(function () use (&$onActionRolledBack) {
                    $onActionRolledBack++;
                });
                $database->onActionRolledBack(function () use (&$onActionRolledBack) {
                    $onActionRolledBack++;
                });

                $database->onActionFinished(function () use (&$onActionFinished) {
                    $onActionFinished++;
                });
                $database->onActionFinished(function () use (&$onActionFinished) {
                    $onActionFinished++;
                });

                if ($return instanceof \Throwable) {
                    throw $return;
                }
                return $return;
            });
        } catch (\Throwable $th) {
            $throwException++;
        }

        $this->assertEquals($onActionCommitted, 0);
        $this->assertEquals($onActionRolledBack, 2);
        $this->assertEquals($onActionFinished, 2);

        if ($return instanceof \Throwable) {
            $this->assertEquals($throwException, 1);
        } else {
            $this->assertEquals($throwException, 0);
        }

        $this->assertEquals($this->database->pdo->testBeginTransaction, 1);
        $this->assertEquals($this->database->pdo->testRollBack, 1);
        $this->assertEquals($this->database->pdo->testCommit, 0);
    }
}
