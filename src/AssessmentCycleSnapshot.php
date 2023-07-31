<?php

declare(strict_types=1);

namespace Assessment;

use Assessment\AssessmentCycle\Assessment;

final class AssessmentCycleSnapshot
{
    public function __construct(
        public readonly AssessmentCycleId $assessmentCycleId,
        public readonly array $assessments,
    ) {

    }

    public function lastAssessment(): ?Assessment
    {
        return $this->assessments[count($this->assessments) - 1] ?? null;
    }
}
