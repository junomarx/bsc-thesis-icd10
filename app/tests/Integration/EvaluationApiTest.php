<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Integration;

/**
 * TEST-API-01: tagged-response interaction and validation contract
 * (`APIBASE-0.1`), exercised through the real repository/evaluator stack
 * against the live baseline.
 */
final class EvaluationApiTest extends DatabaseTestCase
{
    public function testCodeResponseEntersNormalEvaluation(): void
    {
        $result = static::$app->evaluationController->evaluate('Q-001-01', [
            'response' => ['type' => 'code', 'code' => 'J44.02'],
        ]);

        self::assertSame(200, $result->status);
        self::assertSame('classified', $result->body['evaluation_status']);
        self::assertSame('correct', $result->body['classification']);
    }

    public function testNoneOfAboveResponseEntersNormalEvaluation(): void
    {
        $result = static::$app->evaluationController->evaluate('Q-002-01', [
            'response' => ['type' => 'none_of_above'],
        ]);

        self::assertSame(200, $result->status);
        self::assertSame('classified', $result->body['evaluation_status']);
        self::assertSame('RULE-NOA-01', $result->body['determining_rule']);
    }

    public function testMissingResponseIsRejectedBeforeClassification(): void
    {
        $result = static::$app->evaluationController->evaluate('Q-001-01', []);

        self::assertSame(400, $result->status);
        self::assertSame('not_evaluated', $result->body['evaluation_status']);
        self::assertNull($result->body['classification']);
        self::assertSame('malformed_input', $result->body['reason']);
    }

    public function testEmptyWhitespaceOnlyCodeIsMalformed(): void
    {
        $result = static::$app->evaluationController->evaluate('Q-001-01', [
            'response' => ['type' => 'code', 'code' => '   '],
        ]);

        self::assertSame(400, $result->status);
        self::assertSame('malformed_input', $result->body['reason']);
    }

    public function testArrayOfCodesIsRejectedNotAggregated(): void
    {
        $result = static::$app->evaluationController->evaluate('Q-001-01', [
            'response' => ['type' => 'code', 'code' => ['J44.02', 'J44.09']],
        ]);

        self::assertSame(400, $result->status);
        self::assertSame('malformed_input', $result->body['reason']);
    }

    public function testUnsupportedResponseTypeIsRejectedAtTheHttpBoundary(): void
    {
        $result = static::$app->evaluationController->evaluate('Q-001-01', [
            'response' => ['type' => 'free_text', 'text' => 'J44.02'],
        ]);

        self::assertSame(400, $result->status);
        self::assertSame('not_evaluated', $result->body['evaluation_status']);
        self::assertSame('unsupported_response_kind', $result->body['reason']);
    }

    public function testGateFailureReturnsNonClassifiedScopeResult(): void
    {
        $result = static::$app->evaluationController->evaluate('Q-001-01', [
            'response' => ['type' => 'code', 'code' => 'A00.0'],
        ]);

        self::assertSame(200, $result->status);
        self::assertSame('not_evaluated', $result->body['evaluation_status']);
        self::assertNull($result->body['classification']);
        self::assertSame('outside_active_subset', $result->body['reason']);
    }

    public function testUnknownQuestionIdIsNotFound(): void
    {
        $result = static::$app->evaluationController->evaluate('Q-999-99', [
            'response' => ['type' => 'code', 'code' => 'J44.02'],
        ]);

        self::assertSame(404, $result->status);
    }

    public function testVerificationOnlyQuestionIsStillEvaluableById(): void
    {
        // REQ-VER-09: the 8 hidden legacy fixtures must remain reachable
        // through the evaluate endpoint even though they 404 on the
        // learner-facing detail read (QuestionController::show()).
        $result = static::$app->evaluationController->evaluate('VQ-001', [
            'response' => ['type' => 'code', 'code' => 'J44.02'],
        ]);

        self::assertSame(200, $result->status);
        self::assertSame('classified', $result->body['evaluation_status']);
        self::assertSame('correct', $result->body['classification']);
    }
}
