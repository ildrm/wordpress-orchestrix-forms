<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Surveys;

final class SurveyStatistics
{
    /**
     * @param list<int|float> $scores
     * @return array{responses:int,promoters:int,passives:int,detractors:int,nps:float}
     */
    public function nps(array $scores): array
    {
        $promoters = count(array_filter($scores, static fn ($score): bool => $score >= 9));
        $detractors = count(array_filter($scores, static fn ($score): bool => $score <= 6));
        $responses = count($scores);
        return [
            'responses' => $responses, 'promoters' => $promoters, 'passives' => $responses - $promoters - $detractors,
            'detractors' => $detractors, 'nps' => $responses === 0 ? 0.0 : round((($promoters - $detractors) / $responses) * 100, 2),
        ];
    }
}
