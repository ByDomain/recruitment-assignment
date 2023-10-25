<?php

declare(strict_types=1);

namespace Assessment\Event;

use Assessment\AssessmentId;
use Ecotone\Modelling\Attribute\NamedEvent;

#[NamedEvent(self::NAME)]
final class AssessmentWasReplaced
{
    public const NAME = 'assessment.replaced';

    public function __construct(public readonly AssessmentId $assessmentId, public readonly AssessmentId $subsequentAssessmentId)
    {
    }
}
