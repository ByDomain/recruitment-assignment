<?php

declare(strict_types=1);

namespace Assessment\Event;

use Assessment\AssessmentCycleId;
use Assessment\AssessmentId;
use Ecotone\Modelling\Attribute\NamedEvent;

#[NamedEvent(self::NAME)]
final class AssessmentCreated
{
    public const NAME = 'assessment.created';

    public function __construct(
        public readonly AssessmentId $assessmentId,
        public readonly AssessmentCycleId $assessmentCycleId,
        public readonly \DateTimeImmutable $issuedAt,
        public readonly \DateTimeImmutable $expireAt,
    ) {
    }
}
