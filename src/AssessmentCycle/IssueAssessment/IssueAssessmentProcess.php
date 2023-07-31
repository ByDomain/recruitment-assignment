<?php

declare(strict_types=1);

namespace Assessment\AssessmentCycle\IssueAssessment;

use Assert\Assertion;
use Assessment\AssessmentCycle\Assessment;
use Assessment\AssessmentCycleSnapshot;
use Munus\Collection\GenericList;
use Munus\Control\Either;
use Munus\Control\Option;

final class IssueAssessmentProcess
{
    public function __construct(private readonly GenericList $policies)
    {
        $policies->forEach(fn($policy) => Assertion::isInstanceOf($policy, IssueAssessmentPolicy::class));
    }

    public function process(Assessment $assessment, AssessmentCycleSnapshot $assessmentCycle): Option
    {
        return $this->policies
            ->map(function (IssueAssessmentPolicy $policy) use ($assessment, $assessmentCycle): Either {
                return $policy->isSatisfiedBy($assessment, $assessmentCycle);
            })
            ->find(function (Either $either): bool {
                return $either->isLeft();
            })
            ->map(function (Either $either) {
                return $either->getLeft();
            })
        ;
    }
}
