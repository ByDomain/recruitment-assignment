<?php

declare(strict_types=1);

namespace Assessment;

use Assessment\AssessmentCycle\Assessment;
use Assessment\AssessmentCycle\IssueAssessment\IssueAssessmentProcess;
use Assessment\Command\IssueAssessment;
use Assessment\Event\AssessmentCycleStarted;
use Assessment\Event\AssessmentIssued;
use Assessment\Event\AssessmentReplaced;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
final class AssessmentCycle
{
    use WithAggregateVersioning;

    public const SNAPSHOT_QUERY = 'assessment_cycle.query.snapshot';

    #[AggregateIdentifier]
    private AssessmentCycleId $assessmentCycleId;

    /** @var array<Assessment> */
    private array $assessments = [];

    #[CommandHandler]
    public static function startCycleWithIssuedAssessment(IssueAssessment $command, IssueAssessmentProcess $issueAssessmentProcess): array
    {
        $rejections = $issueAssessmentProcess->process($command->assessment(), new AssessmentCycleSnapshot($command->assessmentCycleId, []));

        if ($rejections->isPresent()) {
            // todo domain exception thrown according to rejections
            throw new \DomainException();
        }

        return [
            new AssessmentCycleStarted($command->assessmentCycleId),
            new AssessmentIssued($command->assessmentCycleId, $command->assessmentId, $command->issuedAt, $command->issuedBy, $command->evaluation)
        ];
    }

    #[CommandHandler]
    public function issueAssessment(IssueAssessment $command, IssueAssessmentProcess $issueAssessmentProcess): array
    {
        $rejections = $issueAssessmentProcess->process($command->assessment(), $this->snapshot());

        if ($rejections->isPresent()) {
            // todo domain exception thrown according to rejections
            throw new \DomainException();
        }

        $events = [
            new AssessmentIssued($command->assessmentCycleId, $command->assessmentId, $command->issuedAt, $command->issuedBy, $command->evaluation)
        ];
        $lastAssessment = $this->snapshot()->lastAssessment();
        if ($lastAssessment !== null) {
            $events[] = new AssessmentReplaced($this->assessmentCycleId, $lastAssessment->assessmentId, $command->assessmentId);
        }

        return $events;
    }

    #[EventSourcingHandler]
    public function applyAssessmentCycleStarted(AssessmentCycleStarted $event): void
    {
        $this->assessmentCycleId = $event->assessmentCycleId;
    }

    #[EventSourcingHandler]
    public function applyAssessmentIssued(AssessmentIssued $event): void
    {
        $this->assessments[] = new Assessment($event->assessmentId, $event->issuedAt, $event->issuedBy, $event->evaluation);
    }

    #[QueryHandler(self::SNAPSHOT_QUERY)]
    public function snapshot(): AssessmentCycleSnapshot
    {
        return new AssessmentCycleSnapshot($this->assessmentCycleId, $this->assessments);
    }
}
