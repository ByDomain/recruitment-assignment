<?php

declare(strict_types=1);

namespace Assessment\Interceptor;

use Assessment\Event\AssessmentReplaced;
use Ecotone\Messaging\Attribute\Interceptor\Before;

final class BeforeAssessmentReplaced
{
    #[Before(pointcut: 'Assessment\Assessment::whenAssessmentReplaced', changeHeaders: true)]
    public function enrichBeforeCreateMethod(AssessmentReplaced $event): array
    {
        return [
            'previousAssessmentId' => $event->previousAssessmentId->id,
        ];
    }
}
