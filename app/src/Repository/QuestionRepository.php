<?php

declare(strict_types=1);

namespace Icd10Prototype\Repository;

use Icd10Prototype\Model\CodingQuestion;
use Icd10Prototype\Model\QuestionCodeDomainRelation;
use Icd10Prototype\Model\QuestionFact;
use Icd10Prototype\Model\QuestionFacts;
use Icd10Prototype\Model\QuestionOption;
use Icd10Prototype\Model\QuestionRelationFact;

/**
 * Reads `coding_question` and its facts/domain-relations/relation-facts/
 * options, replacing `CaseRepository`. `findById()` does not filter by
 * `intended_use` - both learner-visible and `verification_only` questions
 * resolve by ID, because the verification path must be able to evaluate the
 * eight hidden historical fixtures (REQ-VER-09). The learner-facing
 * visibility boundary is enforced by callers (`listLearnerVisible()` here,
 * and the controller's own check on a direct single-question fetch), not by
 * this method - mirroring how `EvaluationController` never filtered by
 * `intended_use` even though `CaseController::show()` did.
 */
final class QuestionRepository
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly string $questionBaselineId,
        private readonly string $subsetBaselineId,
    ) {
    }

    public function findById(string $questionId): ?CodingQuestion
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM coding_question WHERE question_baseline_id = :baseline AND question_id = :question_id',
        );
        $statement->execute(['baseline' => $this->questionBaselineId, 'question_id' => $questionId]);
        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /** @return list<CodingQuestion> */
    public function listLearnerVisibleForPatient(string $patientId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT * FROM coding_question
             WHERE question_baseline_id = :baseline AND patient_id = :patient_id AND intended_use = 'learner_visible'
             ORDER BY canonical_position",
        );
        $statement->execute(['baseline' => $this->questionBaselineId, 'patient_id' => $patientId]);

        return array_map($this->hydrate(...), $statement->fetchAll());
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): CodingQuestion
    {
        $questionId = $row['question_id'];

        $factStatement = $this->pdo->prepare(
            'SELECT * FROM question_fact WHERE question_baseline_id = :baseline AND question_id = :question_id',
        );
        $factStatement->execute(['baseline' => $this->questionBaselineId, 'question_id' => $questionId]);

        $factsByKey = [];
        foreach ($factStatement->fetchAll() as $factRow) {
            $factsByKey[$factRow['fact_key']] = new QuestionFact(
                $factRow['fact_key'],
                $factRow['value_type'],
                $this->typedFactValue($factRow),
                $factRow['unit'],
                $factRow['learner_label'],
                $factRow['source_context_item_id'],
            );
        }
        $facts = new QuestionFacts($factsByKey);

        $domainStatement = $this->pdo->prepare(
            'SELECT * FROM question_code_domain WHERE question_baseline_id = :baseline AND question_id = :question_id AND subset_baseline_id = :subset',
        );
        $domainStatement->execute([
            'baseline' => $this->questionBaselineId,
            'question_id' => $questionId,
            'subset' => $this->subsetBaselineId,
        ]);

        $domain = [];
        foreach ($domainStatement->fetchAll() as $domainRow) {
            $domain[$domainRow['code']] = new QuestionCodeDomainRelation(
                $domainRow['code'],
                $domainRow['relation_kind'],
                $domainRow['reason_key'],
                $domainRow['improvement_code'],
                $domainRow['source_audit_ref'],
            );
        }
        $this->assertImprovementCodesResolve($questionId, $domain);

        $relationFactStatement = $this->pdo->prepare(
            'SELECT * FROM question_relation_fact WHERE question_baseline_id = :baseline AND question_id = :question_id AND subset_baseline_id = :subset',
        );
        $relationFactStatement->execute([
            'baseline' => $this->questionBaselineId,
            'question_id' => $questionId,
            'subset' => $this->subsetBaselineId,
        ]);

        $relationFacts = [];
        foreach ($relationFactStatement->fetchAll() as $relationFactRow) {
            $relationFacts[$relationFactRow['code']][] = new QuestionRelationFact(
                $relationFactRow['code'],
                $relationFactRow['fact_key'],
                $relationFactRow['relation_role'],
            );
        }

        $optionStatement = $this->pdo->prepare(
            'SELECT * FROM question_option WHERE question_baseline_id = :baseline AND question_id = :question_id ORDER BY canonical_position',
        );
        $optionStatement->execute(['baseline' => $this->questionBaselineId, 'question_id' => $questionId]);

        $options = array_map(
            static fn (array $optionRow): QuestionOption => new QuestionOption(
                $optionRow['option_id'],
                $optionRow['option_kind'],
                $optionRow['code'],
                (int) $optionRow['canonical_position'],
            ),
            $optionStatement->fetchAll(),
        );

        return new CodingQuestion(
            $questionId,
            $row['patient_id'],
            $row['title'],
            $row['prompt'],
            $row['intended_use'],
            (int) $row['canonical_position'],
            $row['legacy_case_id'],
            $row['source_audit_ref'],
            $facts,
            $domain,
            $relationFacts,
            $options,
        );
    }

    /** @param array<string, mixed> $factRow */
    private function typedFactValue(array $factRow): string|int|float|bool
    {
        return match ($factRow['value_type']) {
            QuestionFact::TYPE_INTEGER => (int) $factRow['value_integer'],
            QuestionFact::TYPE_DECIMAL => (float) $factRow['value_decimal'],
            QuestionFact::TYPE_BOOLEAN => (bool) $factRow['value_boolean'],
            QuestionFact::TYPE_CODE => (string) $factRow['value_code'],
            QuestionFact::TYPE_ENUM => (string) $factRow['value_enum'],
            default => (string) $factRow['value_text'],
        };
    }

    /**
     * MODELBASE-0.2 §5.6 requires a `less_specific_supported` relation's
     * `improvement_code` to resolve to an `accepted_reference` row for the
     * *same* question. The schema's FK only checks the code exists in
     * `catalogue_code`, not that it carries the right relation kind here
     * (deviation #8 flagged by the migration's own spec-extraction pass) -
     * enforced at hydration time instead, failing loudly rather than letting
     * bad authoring data reach the evaluator silently.
     *
     * @param array<string, QuestionCodeDomainRelation> $domain
     */
    private function assertImprovementCodesResolve(string $questionId, array $domain): void
    {
        foreach ($domain as $relation) {
            if ($relation->relationKind !== QuestionCodeDomainRelation::KIND_LESS_SPECIFIC_SUPPORTED) {
                continue;
            }

            $improvement = $domain[$relation->improvementCode] ?? null;
            if ($improvement === null || $improvement->relationKind !== QuestionCodeDomainRelation::KIND_ACCEPTED_REFERENCE) {
                throw new \RuntimeException(sprintf(
                    'question %s: improvement_code %s for %s does not resolve to an accepted_reference relation on the same question',
                    $questionId,
                    (string) $relation->improvementCode,
                    $relation->code,
                ));
            }
        }
    }
}
