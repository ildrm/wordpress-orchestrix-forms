<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Security;

final class RateLimiter
{
    public function allow(string $scope, string $identity, int $limit, int $window): bool
    {
        $key = 'orchestrix_rl_' . hash_hmac('sha256', $scope . '|' . $identity, wp_salt('auth'));
        $state = get_transient($key);
        if (! is_array($state) || (int) ($state['reset'] ?? 0) <= time()) {
            set_transient($key, ['count' => 1, 'reset' => time() + $window], $window);
            return true;
        }
        if ((int) $state['count'] >= $limit) {
            return false;
        }
        $state['count'] = ((int) $state['count']) + 1;
        set_transient($key, $state, max(1, ((int) $state['reset']) - time()));
        return true;
    }
}
