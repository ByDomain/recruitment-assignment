<?php

declare(strict_types=1);

namespace Assessment;

final class AssessmentCycleId
{
    public function __construct(
        public readonly ClientId $clientId,
        public readonly string $standard,
    ) {
    }

    public function __toString(): string
    {
        return sprintf('%d.%s', $this->clientId->id, $this->standard);
    }
}
