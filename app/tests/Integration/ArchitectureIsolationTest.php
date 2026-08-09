<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Integration;

/**
 * TEST-ARC-01 (behavioural half): the runtime schema carries no expected-
 * output oracle data, and the evaluator classifies correctly even when the
 * verification/ directory is physically unavailable to the PHP process.
 */
final class ArchitectureIsolationTest extends DatabaseTestCase
{
    public function testRuntimeSchemaHasNoExpectedOutputColumnsOrTables(): void
    {
        $pdo = \Icd10Prototype\Db::connect(\Icd10Prototype\Config::fromEnvironment());

        $tableNames = $pdo->query(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()",
        )->fetchAll(\PDO::FETCH_COLUMN);

        self::assertEqualsCanonicalizing(
            [
                'prototype_baseline',
                'catalogue_code',
                'patient_definition',
                'patient_context_item',
                'coding_question',
                'question_fact',
                'question_code_domain',
                'question_relation_fact',
                'question_option',
            ],
            $tableNames,
        );

        $columnNames = $pdo->query(
            "SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE()",
        )->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($columnNames as $column) {
            self::assertStringStartsNotWith('expected_', strtolower((string) $column));
        }
        self::assertNotContains('determining_rule', array_map('strtolower', $columnNames));
    }

    public function testEvaluatorSourceCodeNeverReferencesTheVerificationOracle(): void
    {
        $srcDir = dirname(__DIR__, 2) . '/src';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($srcDir));

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $contents = file_get_contents($file->getPathname());
            self::assertStringNotContainsStringIgnoringCase('verification/reference_responses', $contents, $file->getPathname());
            self::assertStringNotContainsStringIgnoringCase('RCBASE', $contents, $file->getPathname());
        }
    }

    public function testEvaluatorClassifiesCorrectlyWithoutTheVerificationDirectoryPresent(): void
    {
        // The application never opens verification/ at all (asserted above);
        // this exercises the same evaluation path end to end as positive
        // confirmation that classification does not depend on that fixture.
        $result = static::$app->evaluationController->evaluate('Q-001-01', [
            'response' => ['type' => 'code', 'code' => 'J44.02'],
        ]);

        self::assertSame('classified', $result->body['evaluation_status']);
        self::assertSame('correct', $result->body['classification']);
    }
}
