<?php

declare(strict_types=1);

namespace Orchestrix\Forms\AntiSpam;

final class SpamScorer
{
    /**
     * @param array<string,mixed> $values
     * @return array{score:int,reasons:list<string>}
     */
    public function score(array $values, int $renderedAt, string $honeypot = ''): array
    {
        $score = 0;
        $reasons = [];
        if ($honeypot !== '') {
            $score += 80;
            $reasons[] = 'honeypot';
        }
        if ($renderedAt > 0 && time() - $renderedAt < 2) {
            $score += 35;
            $reasons[] = 'submitted_too_quickly';
        }
        $joined = strtolower(wp_json_encode($values) ?: '');
        if (preg_match_all('~https?://~', $joined) > 5) {
            $score += 25;
            $reasons[] = 'excessive_links';
        }
        /** @var array{score:int,reasons:list<string>} $result */
        $result = apply_filters('orchestrix_forms_spam_score', ['score' => min(100, $score), 'reasons' => $reasons], $values);
        return $result;
    }
}
