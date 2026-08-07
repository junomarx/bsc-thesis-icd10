<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Integration;

use Icd10Prototype\Bootstrap;
use Icd10Prototype\Config;
use Icd10Prototype\Db;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that exercise the real PHP repository/evaluator/API
 * stack against the live PROTOBASE-0.1 MySQL baseline loaded by
 * prototype_baseline_0_1/scripts/load_mysql.py. Requires ICD_DB_* env vars.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected static Bootstrap $app;

    public static function setUpBeforeClass(): void
    {
        $pdo = Db::connect(Config::fromEnvironment());
        static::$app = new Bootstrap($pdo);
    }
}
