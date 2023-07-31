<?php

declare(strict_types=1);

namespace Assessment\Command;

use Assessment\AssessmentCycle\Assessment;
use Assessment\AssessmentCycleId;
use Assessment\AssessmentId;
use Assessment\Evaluation;
use Assessment\SupervisorId;

final class IssueAssessment
{
    public function __construct(
        public readonly AssessmentCycleId $assessmentCycleId,
        public readonly AssessmentId $assessmentId,
        public readonly \DateTimeImmutable $issuedAt,
        public readonly SupervisorId $issuedBy,
        public readonly Evaluation $evaluation,
    ) {
    }

    public function assessment(): Assessment
    {
        return new Assessment($this->assessmentId, $this->issuedAt, $this->issuedBy, $this->evaluation);
    }
}
