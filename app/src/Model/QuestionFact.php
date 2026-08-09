<?php

declare(strict_types=1);

namespace Icd10Prototype\Model;

/**
 * One `question_fact` row: a single typed fact explicitly represented for an
 * atomic coding question. RULE-* predicates may consume only facts
 * represented here (REQ-MOD-01/REQ-MOD-04) - never patient context prose or
 * the question prompt.
 */
final class QuestionFact
{
    public const TYPE_TEXT = 'text';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_DECIMAL = 'decimal';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_CODE = 'code';
    public const TYPE_ENUM = 'enum';

    public function __construct(
        public readonly string $factKey,
        public readonly string $valueType,
        public readonly string|int|float|bool $value,
        public readonly ?string $unit,
        public readonly string $learnerLabel,
        public readonly ?string $sourceContextItemId,
    ) {
    }
}
