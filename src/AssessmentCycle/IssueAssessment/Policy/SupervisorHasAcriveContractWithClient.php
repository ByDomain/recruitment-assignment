<?php

declare(strict_types=1);

namespace Assessment\AssessmentCycle\IssueAssessment\Policy;

use Assessment\AssessmentCycle\Assessment;
use Assessment\AssessmentCycle\IssueAssessment\IssueAssessmentPolicy;
use Assessment\AssessmentCycleSnapshot;
use Assessment\Control\Accept;
use Assessment\Control\Reject;
use Munus\Control\Either;

final class SupervisorHasAcriveContractWithClient implements IssueAssessmentPolicy
{
    public function __construct(public readonly array $activeContracts)
    {
    }

    public function isSatisfiedBy(Assessment $assessment, AssessmentCycleSnapshot $assessmentCycle): Either
    {
        if (!array_key_exists($assessment->issuedBy->id, $this->activeContracts)) {
            return new Either\Left(new Reject('No contract for supervisor'));
        }

        if (!in_array($assessmentCycle->assessmentCycleId->clientId->id, $this->activeContracts[$assessment->issuedBy->id], true)) {
            return new Either\Left(new Reject('No contract between supervisor and client'));
        }

        return new Either\Right(new Accept());
    }
}
