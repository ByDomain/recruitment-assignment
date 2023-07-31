<?php

declare(strict_types=1);

namespace Assessment;

use Assessment\Command\ExpireAssessment;
use Assessment\Event\AssessmentCreated;
use Assessment\Event\AssessmentExpired;
use Assessment\Event\AssessmentIssued;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
final class Assessment
{
    use WithAggregateVersioning;

    public const ASSESSMENT_VALIDITY_PERIOD_IN_DAYS = 365;
    public const SNAPSHOT_QUERY = 'assessment.query.snapshot';

    #[AggregateIdentifier]
    private AssessmentId $assessmentId;
    private \DateTimeImmutable $issuedAt;
    private \DateTimeImmutable $expireAt;

    #[EventHandler]
    public static function whenAssessmentIssued(AssessmentIssued $event): array
    {
        $expireAt = $event->issuedAt->modify(sprintf('+%d days', self::ASSESSMENT_VALIDITY_PERIOD_IN_DAYS));

        return [new AssessmentCreated($event->assessmentId, $event->assessmentCycleId, $event->issuedAt, $expireAt)];
    }

    #[CommandHandler]
    public function expireAssessment(ExpireAssessment $command): array
    {
        $events = [];
        if ($command->expireAt >= $this->expireAt) {
            $events[] = new AssessmentExpired($this->assessmentId, $this->expireAt);
        }

        return $events;
    }

    #[EventSourcingHandler]
    public function applyAssessmentCreated(AssessmentCreated $event): void
    {
        $this->assessmentId = $event->assessmentId;
        $this->issuedAt = $event->issuedAt;
        $this->expireAt = $event->expireAt;
    }

    #[QueryHandler(self::SNAPSHOT_QUERY)]
    public function snapshot(): AssessmentSnapshot
    {
        return new AssessmentSnapshot($this->assessmentId, $this->issuedAt, $this->expireAt);
    }
}
