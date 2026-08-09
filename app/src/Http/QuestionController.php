<?php

declare(strict_types=1);

namespace Icd10Prototype\Http;

use Icd10Prototype\Model\CodingQuestion;
use Icd10Prototype\Model\QuestionOption;
use Icd10Prototype\Repository\CatalogueRepository;
use Icd10Prototype\Repository\QuestionRepository;

/**
 * GET /api/questions/{question_id} (REQ-INT-01/04, `APIBASE-0.1` §2/§5).
 * 404s for a `verification_only` question, mirroring the old
 * `CaseController::show()`'s asymmetry with the evaluate endpoint: the
 * verification harness reaches hidden fixtures through `POST .../evaluate`
 * (never filtered by `intended_use`, see `EvaluationController`), not
 * through this learner-facing detail read, and gets no special read
 * endpoint of its own. `options` here is the *displayed* set, not the
 * evaluation domain (REQ-MOD-06) - a question may accept a code that never
 * appears in this response.
 *
 * Deliberately does not return `question_fact` rows: `APIBASE-0.1` §5 fixes
 * raw facts as evaluator-internal, pre-submission data, and `learner_label`
 * as a post-submission explanation label, not a visibility flag. Everything
 * a learner needs to answer is expected to already be in `prompt` and/or the
 * patient's `patient_context_item` rows (confirmed against the materialized
 * data - e.g. `Q-001-01`'s prompt states the FEV1 value directly).
 */
final class QuestionController
{
    public function __construct(
        private readonly QuestionRepository $questions,
        private readonly CatalogueRepository $catalogue,
    ) {
    }

    public function show(string $questionId): ApiResult
    {
        $question = $this->questions->findById($questionId);
        if ($question === null || !$question->isLearnerVisible()) {
            return new ApiResult(404, ['error' => 'question_not_found']);
        }

        return new ApiResult(200, $this->render($question));
    }

    /** @return array<string, mixed> */
    private function render(CodingQuestion $question): array
    {
        $codes = array_values(array_filter(array_map(
            static fn (QuestionOption $option): ?string => $option->optionKind === QuestionOption::KIND_CODE ? $option->code : null,
            $question->options,
        )));
        $catalogueByCode = [];
        foreach ($this->catalogue->findByCodes($codes) as $record) {
            $catalogueByCode[$record->code] = $record;
        }

        $options = array_map(
            static function (QuestionOption $option) use ($catalogueByCode): array {
                if ($option->optionKind === QuestionOption::KIND_NONE_OF_ABOVE) {
                    return ['option_id' => $option->optionId, 'option_kind' => 'none_of_above'];
                }

                $record = $catalogueByCode[$option->code] ?? null;

                return [
                    'option_id' => $option->optionId,
                    'option_kind' => 'code',
                    'code' => $option->code,
                    'designation' => $record?->designation,
                    'short_designation' => $record?->shortDesignation,
                ];
            },
            $question->options,
        );

        return [
            'question_id' => $question->questionId,
            'patient_id' => $question->patientId,
            'title' => $question->title,
            'prompt' => $question->prompt,
            'canonical_position' => $question->canonicalPosition,
            'options' => $options,
        ];
    }
}
