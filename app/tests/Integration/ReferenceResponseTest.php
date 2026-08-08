<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * TEST-RC-01: complete reference-response conformance.
 *
 * This test harness reads verification/reference_responses_0_2.csv directly;
 * the running application never does (TEST-ARC-01). For each of the 18
 * RC-* rows (14 original plus 4 added by the pre-freeze coverage review,
 * see chapter3_reference_case_coverage_plan.md CASEPLAN-0.2) it sends only
 * case_id and submitted_code through the real evaluation boundary and
 * compares the result with the external oracle.
 */
final class ReferenceResponseTest extends DatabaseTestCase
{
    #[DataProvider('referenceResponses')]
    public function testReferenceResponseConforms(
        string $rcId,
        string $caseId,
        string $submittedCode,
        string $expectedStatus,
        string $expectedClass,
        string $determiningRule,
        string $criterion,
        ?string $improvementCode,
        array $requiredElements,
    ): void {
        $result = static::$app->evaluationController->evaluate($caseId, ['submitted_code' => $submittedCode]);
        $body = $result->body;

        self::assertSame($expectedStatus, $body['evaluation_status'], "$rcId: evaluation_status");
        self::assertSame($expectedClass, $body['classification'], "$rcId: classification");
        self::assertSame($determiningRule, $body['determining_rule'], "$rcId: determining_rule");
        self::assertSame($criterion, $body['criterion'], "$rcId: criterion");

        if ($improvementCode !== null) {
            self::assertSame($improvementCode, $body['improvement_code'], "$rcId: improvement_code");
        }

        self::assertNotEmpty($body['explanation'], "$rcId: non-empty learner explanation");

        foreach ($requiredElements as $element) {
            self::assertArrayHasKey($element, $body['explanation_elements'], "$rcId: missing explanation element $element");
            self::assertNotNull($body['explanation_elements'][$element], "$rcId: explanation element $element must not be null");
            self::assertNotSame('', $body['explanation_elements'][$element], "$rcId: explanation element $element must not be empty");
        }
    }

    public static function referenceResponses(): array
    {
        $path = dirname(__DIR__, 3) . '/prototype_baseline_0_1/verification/reference_responses_0_2.csv';
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '\\');
        $vectors = [];

        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            $record = array_combine($header, $row);
            $improvementCode = $record['improvement_code'] !== '' ? $record['improvement_code'] : null;
            $requiredElements = array_filter(explode('|', $record['required_explanation_elements']));

            $vectors[$record['rc_id']] = [
                $record['rc_id'],
                $record['case_id'],
                $record['submitted_code'],
                $record['expected_evaluation_status'],
                $record['expected_class'],
                $record['determining_rule'],
                $record['criterion'],
                $improvementCode,
                $requiredElements,
            ];
        }

        fclose($handle);

        return $vectors;
    }
}
