<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * TEST-RC-01: reference-response conformance against the `RCBASE-0.3`
 * oracle, frozen at `PROTOBASE-1.0` (`docs/CONFORMANCE_REPORT.md`).
 *
 * This test harness reads
 * `prototype_baseline/verification/reference_responses_0_3.csv`
 * directly; the running application never does (TEST-ARC-01). 143 rows: 18
 * carry a `legacy_rc_id` (the historical `RCBASE-0.2` regression
 * obligations, `REQ-VER-09` - mapped onto the 8 hidden `verification_only`
 * `VQ-*` questions) and 125 are new forward-model expectations spanning all
 * 25 learner questions plus their `none_of_above` cases.
 *
 * **Provenance (`REQ-VER-08`/`09`):** every row's `provenance_status`
 * column reads `..._human_oracle_audit_confirmed_against_qsaudit_0_1` (125
 * rows, cross-checked against `chapter3_question_bank_source_audit.md`
 * §4.1-4.6) or `exact_semantic_carry_forward_confirmed_against_rcbase_0_2`
 * (4 legacy `VQ-005..008` rows - confirmed both by running their
 * documented case facts through the live `RuleMap`/`RuleStatus`
 * predicates directly, and by a field-by-field diff against the archived
 * raw `RCBASE-0.2` file,
 * `archived/prototype_baseline_0_1/verification/reference_responses_0_2.csv`).
 * Zero discrepancies found anywhere. This test's own execution, against
 * this exact oracle, is part of the principal verification run recorded
 * in `docs/CONFORMANCE_REPORT.md` - a passing run there is `REQ-VER-05`'s
 * formal conformance evidence, not merely "toward" it.
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
        $path = dirname(__DIR__, 3) . '/prototype_baseline/verification/reference_responses_0_3.csv';
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
