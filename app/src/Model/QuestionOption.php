<?php

declare(strict_types=1);

namespace Icd10Prototype\Model;

/**
 * One `question_option` row: a displayed choice. `verification_only`
 * questions have zero option rows - they are never learner-navigable
 * (REQ-INT-04, RULE-NOA-01's option-membership invariant).
 */
final class QuestionOption
{
    public const KIND_CODE = 'code';
    public const KIND_NONE_OF_ABOVE = 'none_of_above';

    public function __construct(
        public readonly string $optionId,
        public readonly string $optionKind,
        public readonly ?string $code,
        public readonly int $canonicalPosition,
    ) {
    }
}
