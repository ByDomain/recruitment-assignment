<?php

declare(strict_types=1);

namespace Assessment\Control;

final class Reject
{
    public function __construct(public readonly string $reason)
    {
    }
}
