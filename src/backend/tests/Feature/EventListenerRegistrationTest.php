<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Laravel auto-discovers every listener in app/Listeners and registers each
 * public `handle*` method separately. A listener also registered by hand, or
 * exposing a second `handle*` method, therefore runs its whole body twice per
 * event — for the payment listeners that means duplicated ledger rows and
 * double-credited agent balances.
 */
class EventListenerRegistrationTest extends TestCase {
    public function testNoListenerClassIsRegisteredTwiceForTheSameEvent(): void {
        $offenders = [];

        foreach (Event::getRawListeners() as $event => $listeners) {
            $classes = [];
            foreach ($listeners as $listener) {
                // Every framework EventServiceProvider subclass re-registers
                // Laravel's own Registered listener, so only our own are checked.
                if (!is_string($listener) || !str_starts_with($listener, 'App\\')) {
                    continue;
                }
                $classes[] = strtok($listener, '@');
            }

            foreach (array_count_values($classes) as $class => $registrations) {
                if ($registrations > 1) {
                    $offenders[] = "{$event} => {$class} ({$registrations}x)";
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Listener classes registered more than once for one event:\n".implode("\n", $offenders)
        );
    }
}
