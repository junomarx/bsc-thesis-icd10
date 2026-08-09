<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * TEST-DET-01: deterministic repeatability under an unchanged baseline.
 * Covers one correct, one suboptimal, one incorrect, one `none_of_above`,
 * and one gate-failure terminal path.
 */
final class DeterminismTest extends DatabaseTestCase
{
    #[DataProvider('representativeRequests')]
    public function testRepeatedRequestsAreIdentical(string $questionId, array $response): void
    {
        $decodedBody = ['response' => $response];
        $first = static::$app->evaluationController->evaluate($questionId, $decodedBody);
        $second = static::$app->evaluationController->evaluate($questionId, $decodedBody);

        self::assertSame($first->status, $second->status);
        self::assertSame($first->body, $second->body);
    }

    public static function representativeRequests(): array
    {
        return [
            'correct' => ['Q-001-01', ['type' => 'code', 'code' => 'J44.02']],
            'suboptimal' => ['Q-001-01', ['type' => 'code', 'code' => 'J44.09']],
            'incorrect' => ['Q-001-01', ['type' => 'code', 'code' => 'J44.01']],
            'none_of_above' => ['Q-002-01', ['type' => 'none_of_above']],
            'gate failure' => ['Q-001-01', ['type' => 'code', 'code' => 'A00.0']],
        ];
    }
}
