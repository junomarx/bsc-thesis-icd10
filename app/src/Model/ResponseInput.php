<?php

declare(strict_types=1);

namespace Icd10Prototype\Model;

/**
 * One tagged learner response: either a submitted ICD code or
 * `none_of_above` (REQ-INT-01, RULE-NOA-01). `none_of_above` is a response
 * kind, never an ICD code - it is not represented by a `catalogue_code` row
 * and must never be confused with one downstream.
 */
final class ResponseInput
{
    public const KIND_CODE = 'code';
    public const KIND_NONE_OF_ABOVE = 'none_of_above';

    private function __construct(
        public readonly string $kind,
        public readonly ?string $code,
    ) {
    }

    public static function code(string $code): self
    {
        return new self(self::KIND_CODE, $code);
    }

    public static function noneOfAbove(): self
    {
        return new self(self::KIND_NONE_OF_ABOVE, null);
    }

    public function isCode(): bool
    {
        return $this->kind === self::KIND_CODE;
    }

    public function isNoneOfAbove(): bool
    {
        return $this->kind === self::KIND_NONE_OF_ABOVE;
    }
}
