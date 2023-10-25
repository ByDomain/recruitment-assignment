<?php

declare(strict_types=1);

namespace Assessment;

final class AssessmentSnapshot
{
    public function __construct(
        public readonly AssessmentId $assessmentId,
        public readonly \DateTimeImmutable $issuedAt,
        public readonly \DateTimeImmutable $expireAt,
        public readonly bool $isActive,
    ) {
    }
}
