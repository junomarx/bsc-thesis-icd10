<?php

declare(strict_types=1);

namespace Icd10Prototype\Http;

use Icd10Prototype\Model\Patient;
use Icd10Prototype\Repository\PatientRepository;
use Icd10Prototype\Repository\QuestionRepository;

/**
 * GET /api/patients, GET /api/patients/{patient_id} (REQ-INT-01/02).
 * Every patient is learner-facing; the `verification_only` boundary is
 * enforced per-question by `QuestionRepository::listLearnerVisibleForPatient()`,
 * not here.
 */
final class PatientController
{
    public function __construct(
        private readonly PatientRepository $patients,
        private readonly QuestionRepository $questions,
    ) {
    }

    public function list(): ApiResult
    {
        $patients = array_map($this->summarize(...), $this->patients->listAll());

        return new ApiResult(200, ['patients' => $patients]);
    }

    public function show(string $patientId): ApiResult
    {
        $patient = $this->patients->findById($patientId);
        if ($patient === null) {
            return new ApiResult(404, ['error' => 'patient_not_found']);
        }

        $questions = array_map(
            static fn ($question): array => [
                'question_id' => $question->questionId,
                'title' => $question->title,
                'canonical_position' => $question->canonicalPosition,
            ],
            $this->questions->listLearnerVisibleForPatient($patientId),
        );

        return new ApiResult(200, [
            ...$this->summarize($patient),
            'questions' => $questions,
        ]);
    }

    /** @return array<string, mixed> */
    private function summarize(Patient $patient): array
    {
        return [
            'patient_id' => $patient->patientId,
            'display_name' => $patient->displayName,
            'age_years' => $patient->ageYears,
            'sex' => $patient->sex,
            'self_described_background' => $patient->selfDescribedBackground,
            'history_availability' => $patient->historyAvailability,
            'difficulty_role' => $patient->difficultyRole,
            'general_health_summary' => $patient->generalHealthSummary,
            'question_count' => count($this->questions->listLearnerVisibleForPatient($patient->patientId)),
            'context_items' => array_map(
                static fn ($item): array => [
                    'context_item_id' => $item->contextItemId,
                    'item_type' => $item->itemType,
                    'information_source' => $item->informationSource,
                    'display_text' => $item->displayText,
                ],
                $patient->contextItems,
            ),
        ];
    }
}
