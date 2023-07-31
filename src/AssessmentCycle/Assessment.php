<?php

declare(strict_types=1);

namespace Assessment\AssessmentCycle;

use Assessment\AssessmentId;
use Assessment\Evaluation;
use Assessment\SupervisorId;

final class Assessment
{
    public function __construct(
        public readonly AssessmentId $assessmentId,
        public readonly \DateTimeImmutable $issuedAt,
        public readonly SupervisorId $issuedBy,
        public readonly Evaluation $evaluation,
    ) {
    }

    public function isPassed(): bool
    {
        return $this->evaluation === Evaluation::PASSED;
    }
}
