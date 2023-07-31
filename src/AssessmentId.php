<?php

declare(strict_types=1);

namespace Assessment;

final class AssessmentId
{
    public function __construct(public readonly int $id)
    {
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
