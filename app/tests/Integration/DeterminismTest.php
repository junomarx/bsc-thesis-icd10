<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * TEST-DET-01: deterministic repeatability under an unchanged baseline.
 * Covers one correct, one suboptimal, one incorrect, and one gate-failure
 * terminal path.
 */
final class DeterminismTest extends DatabaseTestCase
{
    #[DataProvider('representativeRequests')]
    public function testRepeatedRequestsAreIdentical(string $caseId, string $submittedCode): void
    {
        $first = static::$app->evaluationController->evaluate($caseId, ['submitted_code' => $submittedCode]);
        $second = static::$app->evaluationController->evaluate($caseId, ['submitted_code' => $submittedCode]);

        self::assertSame($first->status, $second->status);
        self::assertSame($first->body, $second->body);
    }

    public static function representativeRequests(): array
    {
        return [
            'correct' => ['CASE-001', 'J44.02'],
            'suboptimal' => ['CASE-001', 'J44.09'],
            'incorrect' => ['CASE-001', 'J44.01'],
            'gate failure' => ['CASE-001', 'Z01.8'],
        ];
    }
}
