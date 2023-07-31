<?php

declare(strict_types=1);

namespace Assessment\Event;

use Assessment\AssessmentCycleId;
use Assessment\AssessmentId;
use Ecotone\Modelling\Attribute\NamedEvent;

#[NamedEvent(self::NAME)]
final class AssessmentReplaced
{
    public const NAME = 'assessment_cycle.assessment_replaced';

    public function __construct(
        public readonly AssessmentCycleId $assessmentCycleId,
        public readonly AssessmentId $previousAssessmentId,
        public readonly AssessmentId $subsequentAssessmentId,
    ) {
    }
}
