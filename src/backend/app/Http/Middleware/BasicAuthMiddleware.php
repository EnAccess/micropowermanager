<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards a route group with HTTP Basic credentials held in configuration.
 *
 * Two very different surfaces need the same trivial gate — Telescope and the
 * operator dashboard — so the realm is a parameter rather than a class each.
 * Realms differ in one respect that matters: a browser-rendered surface wants the
 * native credential dialog, while an XHR client must not get one, because the
 * dialog would pre-empt the application's own login form.
 */
class BasicAuthMiddleware {
    /** @var array<string, array{label: string, config: string, challenge: bool}> */
    private const array REALMS = [
        'telescope' => [
            'label' => 'Telescope',
            'config' => 'telescope.http_basic_auth',
            'challenge' => true,
        ],
        'operator' => [
            'label' => 'Operator Dashboard',
            'config' => 'micropowermanager.operator_dashboard.basic_auth',
            'challenge' => false,
        ],
    ];

    public function handle(Request $request, \Closure $next, string $realm): Response {
        $definition = self::REALMS[$realm];

        if ($definition['challenge'] && app()->environment('development')) {
            return $next($request);
        }

        $username = config($definition['config'].'.username');
        $password = config($definition['config'].'.password');

        if (empty($username) || empty($password)) {
            abort(403, $definition['label'].' access not configured.');
        }

        if (!$this->matches($request, (string) $username, (string) $password)) {
            return response('Unauthorized', 401, $this->unauthorizedHeaders($definition));
        }

        return $next($request);
    }

    private function matches(Request $request, string $username, string $password): bool {
        return hash_equals($username, (string) $request->getUser())
            && hash_equals($password, (string) $request->getPassword());
    }

    /**
     * @param array{label: string, config: string, challenge: bool} $definition
     *
     * @return array<string, string>
     */
    private function unauthorizedHeaders(array $definition): array {
        if (!$definition['challenge']) {
            return [];
        }

        return ['WWW-Authenticate' => 'Basic realm="'.$definition['label'].'"'];
    }
}
