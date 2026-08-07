<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Integration;

/**
 * TEST-API-01: one-code interaction and validation contract, exercised
 * through the real repository/evaluator stack against the live baseline.
 */
final class EvaluationApiTest extends DatabaseTestCase
{
    public function testSingleNonEmptyCodeEntersNormalEvaluation(): void
    {
        $result = static::$app->evaluationController->evaluate('CASE-001', ['submitted_code' => 'J44.02']);

        self::assertSame(200, $result->status);
        self::assertSame('classified', $result->body['evaluation_status']);
        self::assertSame('correct', $result->body['classification']);
    }

    public function testMissingSubmissionIsRejectedBeforeClassification(): void
    {
        $result = static::$app->evaluationController->evaluate('CASE-001', []);

        self::assertSame(400, $result->status);
        self::assertSame('not_evaluated', $result->body['evaluation_status']);
        self::assertNull($result->body['classification']);
        self::assertSame('malformed_input', $result->body['reason']);
    }

    public function testEmptyWhitespaceOnlySubmissionIsMalformed(): void
    {
        $result = static::$app->evaluationController->evaluate('CASE-001', ['submitted_code' => '   ']);

        self::assertSame(400, $result->status);
        self::assertSame('malformed_input', $result->body['reason']);
    }

    public function testArrayOfCodesIsRejectedNotAggregated(): void
    {
        $result = static::$app->evaluationController->evaluate('CASE-001', ['submitted_code' => ['J44.02', 'J44.09']]);

        self::assertSame(400, $result->status);
        self::assertSame('malformed_input', $result->body['reason']);
    }

    public function testGateFailureReturnsNonClassifiedScopeResult(): void
    {
        $result = static::$app->evaluationController->evaluate('CASE-001', ['submitted_code' => 'Z01.8']);

        self::assertSame(200, $result->status);
        self::assertSame('not_evaluated', $result->body['evaluation_status']);
        self::assertNull($result->body['classification']);
        self::assertSame('outside_active_subset', $result->body['reason']);
    }

    public function testUnknownCaseIdIsNotFound(): void
    {
        $result = static::$app->evaluationController->evaluate('CASE-999', ['submitted_code' => 'J44.02']);

        self::assertSame(404, $result->status);
    }
}
