<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * TEST-RC-01: reference-response conformance against the `RCBASE-0.3`
 * candidate oracle.
 *
 * This test harness reads
 * `prototype_baseline_0_2_design/verification/reference_responses_0_3_candidate.csv`
 * directly; the running application never does (TEST-ARC-01). 143 rows: 18
 * carry a `legacy_rc_id` (the historical `RCBASE-0.2` regression
 * obligations, `REQ-VER-09` - mapped onto the 8 hidden `verification_only`
 * `VQ-*` questions) and 125 are new forward-model expectations spanning all
 * 25 learner questions plus their `none_of_above` cases.
 *
 * **Provenance caveat, stated honestly rather than glossed over
 * (`REQ-VER-08`):** every row's own `provenance_status` column currently
 * reads `forward_specification_derived_pending_human_oracle_audit` - these
 * expectations were derived from the rule/source baseline (`QSAUDIT-0.1`),
 * not copied from this implementation's output, but have not yet been
 * independently checked against the original Austrian source pages
 * (implementation-order step 9). Running them here anyway is deliberate:
 * a failure is useful signal *now* (either an evaluator bug or an
 * oracle-authoring bug), not something to wait on step 9 to discover. A
 * pass is evidence the implementation matches the specification as
 * currently understood; it is not yet the frozen conformance claim step 9
 * produces.
 */
final class ReferenceResponseTest extends DatabaseTestCase
{
    #[DataProvider('referenceResponses')]
    public function testReferenceResponseConforms(
        string $rcId,
        string $questionId,
        array $response,
        string $expectedStatus,
        string $expectedClass,
        string $determiningRule,
        string $criterion,
        ?string $improvementCode,
        array $requiredElements,
    ): void {
        $result = static::$app->evaluationController->evaluate($questionId, ['response' => $response]);
        $body = $result->body;

        self::assertSame($expectedStatus, $body['evaluation_status'], "$rcId: evaluation_status");
        self::assertSame($expectedClass, $body['classification'], "$rcId: classification");
        self::assertSame($determiningRule, $body['determining_rule'], "$rcId: determining_rule");
        self::assertSame($criterion, $body['criterion'], "$rcId: criterion");

        if ($improvementCode !== null) {
            self::assertSame($improvementCode, $body['improvement_code'], "$rcId: improvement_code");
        }

        self::assertNotEmpty($body['explanation'], "$rcId: non-empty learner explanation");
        self::assertNotEmpty($body['explanation_de'], "$rcId: non-empty German explanation");

        foreach ($requiredElements as $element) {
            self::assertArrayHasKey($element, $body['explanation_elements'], "$rcId: missing explanation element $element");
            self::assertNotNull($body['explanation_elements'][$element], "$rcId: explanation element $element must not be null");
            self::assertNotSame('', $body['explanation_elements'][$element], "$rcId: explanation element $element must not be empty");
        }
    }

    public static function referenceResponses(): array
    {
        $path = dirname(__DIR__, 3) . '/prototype_baseline_0_2_design/verification/reference_responses_0_3_candidate.csv';
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '\\');
        $vectors = [];

        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            $record = array_combine($header, $row);
            $response = $record['response_kind'] === 'none_of_above'
                ? ['type' => 'none_of_above']
                : ['type' => 'code', 'code' => $record['submitted_code']];
            $improvementCode = $record['expected_improvement_code'] !== '' ? $record['expected_improvement_code'] : null;
            $requiredElements = array_filter(explode('|', $record['required_explanation_elements']));

            $vectors[$record['rc_id']] = [
                $record['rc_id'],
                $record['question_id'],
                $response,
                $record['expected_evaluation_status'],
                $record['expected_class'],
                $record['expected_determining_rule'],
                $record['expected_criterion'],
                $improvementCode,
                $requiredElements,
            ];
        }

        fclose($handle);

        return $vectors;
    }
}
