<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Quizzes;

final class QuizScorer
{
    /**
     * @param array<string,mixed> $answers
     * @param array<string,array<string,float|int>> $weights
     * @return array{score:float,max:float,percentage:float}
     */
    public function score(array $answers, array $weights): array
    {
        $score = 0.0;
        $max = 0.0;
        foreach ($weights as $question => $choices) {
            $max += max([0, ...array_map('floatval', array_values($choices))]);
            $selected = is_array($answers[$question] ?? null) ? $answers[$question] : [$answers[$question] ?? null];
            foreach ($selected as $answer) {
                $score += (float) ($choices[(string) $answer] ?? 0);
            }
        }
        return ['score' => $score, 'max' => $max, 'percentage' => $max <= 0 ? 0.0 : round(($score / $max) * 100, 2)];
    }
}
