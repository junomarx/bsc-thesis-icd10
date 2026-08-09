<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Integration;

use Icd10Prototype\Bootstrap;
use Icd10Prototype\Config;
use Icd10Prototype\Db;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that exercise the real PHP repository/evaluator/API
 * stack against the live PROTOBASE-1.0 (MODELBASE-0.2) MySQL baseline
 * loaded by prototype_baseline/persistence_candidate/load_mysql_0_2.py.
 * Requires ICD_DB_* env vars.
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
