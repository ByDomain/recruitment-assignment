<?php

declare(strict_types=1);

namespace Assessment\AssessmentCycle\IssueAssessment\Policy;

use Assessment\AssessmentCycle\Assessment;
use Assessment\AssessmentCycle\IssueAssessment\IssueAssessmentPolicy;
use Assessment\AssessmentCycleSnapshot;
use Assessment\Control\Accept;
use Assessment\Control\Reject;
use Munus\Control\Either;

// todo simplified, policy should take evaluation date into account
final class SupervisorHasAuthorityForStandard implements IssueAssessmentPolicy
{
    public function __construct(private readonly array $supervisorStandards)
    {
    }

    public function isSatisfiedBy(Assessment $assessment, AssessmentCycleSnapshot $assessmentCycle): Either
    {
        if (!array_key_exists($assessment->issuedBy->id, $this->supervisorStandards)) {
            return new Either\Left(new Reject('Supervisor has no authority'));
        }

        if (!in_array($assessmentCycle->assessmentCycleId->standard, $this->supervisorStandards[$assessment->issuedBy->id], true)) {
            return new Either\Left(new Reject('Supervisor has no authority in requested standard'));
        }

        return new Either\Right(new Accept());
    }
}
