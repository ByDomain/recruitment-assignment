<?php

declare(strict_types=1);

namespace Assessment\Event;

use Assessment\AssessmentCycleId;
use Assessment\AssessmentId;
use Assessment\Evaluation;
use Assessment\SupervisorId;
use Ecotone\Modelling\Attribute\NamedEvent;

#[NamedEvent(self::NAME)]
final class AssessmentIssued
{
    public const NAME = 'assessment_cycle.assessment_issued';

    public function __construct(
        public readonly AssessmentCycleId $assessmentCycleId,
        public readonly AssessmentId $assessmentId,
        public readonly \DateTimeImmutable $issuedAt,
        public readonly SupervisorId $issuedBy,
        public readonly Evaluation $evaluation,
    ) {
    }
}
