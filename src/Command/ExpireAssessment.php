<?php

declare(strict_types=1);

namespace Assessment\Command;

use Assessment\AssessmentId;
use DateTimeImmutable;

final class ExpireAssessment
{
    public function __construct(
        public readonly AssessmentId $assessmentId,
        public readonly DateTimeImmutable $expireAt,
    ) {
    }
}
