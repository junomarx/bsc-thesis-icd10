<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Support;

use Icd10Prototype\Model\CatalogueRecord;
use Icd10Prototype\Model\CodingQuestion;
use Icd10Prototype\Model\QuestionCodeDomainRelation;
use Icd10Prototype\Model\QuestionFact;
use Icd10Prototype\Model\QuestionFacts;
use Icd10Prototype\Model\QuestionOption;
use Icd10Prototype\Model\QuestionRelationFact;
use Icd10Prototype\Model\ResponseInput;

/**
 * Test-only fixtures for the forward (MODELBASE-0.2) model, replacing the
 * `CaseFacts`-based builders. Some vectors (e.g. a COPD relation with FEV1
 * removed, or a hospital-outpatient status relation with the LKF flag
 * removed) are pure technical fixtures used to isolate a branch
 * (TESTBASE-0.1 Section 2); they are not additional clinical reference
 * cases.
 */
final class Fixtures
{
    /**
     * @param array<string, QuestionFact> $facts factKey => fact
     * @param array<string, QuestionCodeDomainRelation> $domain code => relation
     * @param array<string, list<QuestionRelationFact>> $relationFacts code => linked facts
     * @param list<QuestionOption> $options
     */
    public static function question(
        string $questionId,
        array $facts = [],
        array $domain = [],
        array $relationFacts = [],
        array $options = [],
        string $intendedUse = 'learner_visible',
        ?string $patientId = null,
    ): CodingQuestion {
        return new CodingQuestion(
            $questionId,
            $patientId,
            'test fixture: ' . $questionId,
            'test fixture prompt',
            $intendedUse,
            1,
            $questionId,
            'test fixture',
            new QuestionFacts($facts),
            $domain,
            $relationFacts,
            $options,
        );
    }

    /**
     * A COPD-family question: `encounter_setting`/`diagnosis_role`/
     * `copd_base_code`/`fev1_stable_pct_predicted` facts - mirrors the old
     * `copdCase()` helper's shape exactly.
     *
     * @param array<string, bool> $responseDomain code => accepted (true =>
     *   `accepted_reference`; false => `source_rule_resolved`, i.e. defined
     *   but handled by a source-specific hard/graded rule, not itself a
     *   terminal acceptance)
     */
    public static function copdQuestion(
        string $questionId,
        string $baseCode,
        ?float $fev1,
        array $responseDomain,
        string $encounterSetting = 'inpatient',
        string $diagnosisRole = 'main',
        string $intendedUse = 'learner_visible',
    ): CodingQuestion {
        $facts = [
            'encounter_setting' => self::enumFact('encounter_setting', $encounterSetting),
            'diagnosis_role' => self::enumFact('diagnosis_role', $diagnosisRole),
            'copd_base_code' => self::codeFact('copd_base_code', $baseCode),
        ];
        if ($fev1 !== null) {
            $facts['fev1_stable_pct_predicted'] = self::decimalFact('fev1_stable_pct_predicted', $fev1);
        }

        return self::question($questionId, $facts, self::domainFromBooleans($responseDomain), [], [], $intendedUse);
    }

    /**
     * A status(`!`)-family question: `encounter_setting`/`diagnosis_role`/
     * `inpatient_lkf_scored` facts, no COPD facts - mirrors the old
     * `statusCase()` helper's shape exactly.
     *
     * @param array<string, bool> $responseDomain code => accepted
     */
    public static function statusQuestion(
        string $questionId,
        string $encounterSetting,
        ?bool $inpatientLkfScored,
        array $responseDomain,
        string $diagnosisRole = 'main',
        string $intendedUse = 'learner_visible',
    ): CodingQuestion {
        $facts = [
            'encounter_setting' => self::enumFact('encounter_setting', $encounterSetting),
            'diagnosis_role' => self::enumFact('diagnosis_role', $diagnosisRole),
        ];
        if ($inpatientLkfScored !== null) {
            $facts['inpatient_lkf_scored'] = self::boolFact('inpatient_lkf_scored', $inpatientLkfScored);
        }

        return self::question($questionId, $facts, self::domainFromBooleans($responseDomain), [], [], $intendedUse);
    }

    /** @param array<string, bool> $responseDomain code => accepted */
    private static function domainFromBooleans(array $responseDomain): array
    {
        $domain = [];
        foreach ($responseDomain as $code => $accepted) {
            $domain[$code] = $accepted ? self::acceptedReference($code) : self::sourceRuleResolved($code);
        }

        return $domain;
    }

    public static function enumFact(string $key, string $value): QuestionFact
    {
        return new QuestionFact($key, QuestionFact::TYPE_ENUM, $value, null, $key, null);
    }

    public static function codeFact(string $key, string $value): QuestionFact
    {
        return new QuestionFact($key, QuestionFact::TYPE_CODE, $value, null, $key, null);
    }

    public static function decimalFact(string $key, float $value, ?string $label = null): QuestionFact
    {
        return new QuestionFact($key, QuestionFact::TYPE_DECIMAL, $value, '%', $label ?? $key, null);
    }

    public static function boolFact(string $key, bool $value): QuestionFact
    {
        return new QuestionFact($key, QuestionFact::TYPE_BOOLEAN, $value, null, $key, null);
    }

    public static function textFact(string $key, string $value, ?string $label = null): QuestionFact
    {
        return new QuestionFact($key, QuestionFact::TYPE_TEXT, $value, null, $label ?? $value, null);
    }

    public static function acceptedReference(string $code): QuestionCodeDomainRelation
    {
        return new QuestionCodeDomainRelation($code, QuestionCodeDomainRelation::KIND_ACCEPTED_REFERENCE, null, null, 'test fixture');
    }

    public static function sourceRuleResolved(string $code): QuestionCodeDomainRelation
    {
        return new QuestionCodeDomainRelation($code, QuestionCodeDomainRelation::KIND_SOURCE_RULE_RESOLVED, null, null, 'test fixture');
    }

    public static function lessSpecificSupported(string $code, string $improvementCode, string $reasonKey = 'test_less_specific'): QuestionCodeDomainRelation
    {
        return new QuestionCodeDomainRelation($code, QuestionCodeDomainRelation::KIND_LESS_SPECIFIC_SUPPORTED, $reasonKey, $improvementCode, 'test fixture');
    }

    public static function factConflict(string $code, string $reasonKey = 'test_fact_conflict'): QuestionCodeDomainRelation
    {
        return new QuestionCodeDomainRelation($code, QuestionCodeDomainRelation::KIND_FACT_CONFLICT, $reasonKey, null, 'test fixture');
    }

    public static function temporalContextConflict(string $code, string $reasonKey = 'test_temporal_conflict'): QuestionCodeDomainRelation
    {
        return new QuestionCodeDomainRelation($code, QuestionCodeDomainRelation::KIND_TEMPORAL_CONTEXT_CONFLICT, $reasonKey, null, 'test fixture');
    }

    public static function relationFact(string $code, string $factKey, string $relationRole = QuestionRelationFact::ROLE_CONFLICTS_WITH_RESPONSE): QuestionRelationFact
    {
        return new QuestionRelationFact($code, $factKey, $relationRole);
    }

    public static function codeOption(string $optionId, string $code, int $position): QuestionOption
    {
        return new QuestionOption($optionId, QuestionOption::KIND_CODE, $code, $position);
    }

    public static function noneOfAboveOption(string $optionId, int $position): QuestionOption
    {
        return new QuestionOption($optionId, QuestionOption::KIND_NONE_OF_ABOVE, null, $position);
    }

    public static function record(string $code, ?string $marker = null): CatalogueRecord
    {
        return new CatalogueRecord($code, $marker, $code . ' designation', $code . ' short');
    }

    public static function code(string $code): ResponseInput
    {
        return ResponseInput::code($code);
    }

    public static function noneOfAbove(): ResponseInput
    {
        return ResponseInput::noneOfAbove();
    }
}
