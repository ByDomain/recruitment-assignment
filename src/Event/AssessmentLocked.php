<?php

declare(strict_types=1);

namespace Assessment\Event;

use Assessment\AssessmentId;
use Assessment\LockType;
use Ecotone\Modelling\Attribute\NamedEvent;

#[NamedEvent(self::NAME)]
final class AssessmentLocked
{
    public const NAME = 'assessment.locked';

    public function __construct(public readonly AssessmentId $assessmentId, public readonly LockType $lockType)
    {
    }
}
