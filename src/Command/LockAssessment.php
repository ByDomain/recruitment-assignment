<?php

declare(strict_types=1);

namespace Assessment\Command;

use Assessment\AssessmentId;
use Assessment\LockType;

final class LockAssessment
{
    public function __construct(public AssessmentId $assessmentId, LockType $Suspension)
    {
    }
}
