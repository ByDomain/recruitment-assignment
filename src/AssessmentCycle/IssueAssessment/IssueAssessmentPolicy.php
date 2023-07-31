<?php

declare(strict_types=1);

namespace Assessment\AssessmentCycle\IssueAssessment;

use Assessment\AssessmentCycle\Assessment;
use Assessment\AssessmentCycleSnapshot;
use Munus\Control\Either;

interface IssueAssessmentPolicy
{
    public function isSatisfiedBy(Assessment $assessment, AssessmentCycleSnapshot $assessmentCycle): Either;
}
