<?php

declare(strict_types=1);

namespace Assessment\AssessmentCycle\IssueAssessment\Policy;

use Assessment\AssessmentCycle\Assessment;
use Assessment\AssessmentCycle\IssueAssessment\IssueAssessmentPolicy;
use Assessment\AssessmentCycleSnapshot;
use Assessment\Control\Accept;
use Assessment\Control\Reject;
use Munus\Control\Either;

final class AssessmentTimeLimitPreserved implements IssueAssessmentPolicy
{
    public function __construct(private readonly array $timeLimits)
    {
    }

    public function isSatisfiedBy(Assessment $assessment, AssessmentCycleSnapshot $assessmentCycle): Either
    {
        $lastAssessment = $assessmentCycle->lastAssessment();

        if (
            $lastAssessment instanceof Assessment
            && $assessment->issuedAt->diff($lastAssessment->issuedAt)->days <= $this->timeLimits[$lastAssessment->evaluation->value]
        ) {
            return new Either\Left(new Reject('Time limit not preserved'));
        }

        return new Either\Right(new Accept());
    }
}
