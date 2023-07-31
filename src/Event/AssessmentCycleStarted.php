<?php

declare(strict_types=1);

namespace Assessment\Event;

use Assessment\AssessmentCycleId;
use Ecotone\Modelling\Attribute\NamedEvent;

#[NamedEvent(self::NAME)]
final class AssessmentCycleStarted
{
    public const NAME = 'assessment_cycle.started';

    public function __construct(public readonly AssessmentCycleId $assessmentCycleId)
    {
    }
}
