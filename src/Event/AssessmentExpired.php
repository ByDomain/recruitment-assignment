<?php

declare(strict_types=1);

namespace Assessment\Event;

use Assessment\AssessmentId;
use DateTimeImmutable;
use Ecotone\Modelling\Attribute\NamedEvent;

#[NamedEvent(self::NAME)]
final class AssessmentExpired
{
    public const NAME = 'assessment.expired';

    public function __construct(
        public readonly AssessmentId $assessmentId,
        public readonly DateTimeImmutable $expireAt,
    ) {
    }
}
