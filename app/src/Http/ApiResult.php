<?php

declare(strict_types=1);

namespace Icd10Prototype\Http;

/**
 * A controller's HTTP-independent result. Kept separate from superglobals so
 * controllers are testable without booting a web server.
 */
final class ApiResult
{
    public function __construct(
        public readonly int $status,
        public readonly array $body,
    ) {
    }
}
