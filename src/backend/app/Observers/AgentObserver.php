<?php

namespace App\Observers;

use App\Models\Agent;
use App\Models\Person\Person;

class AgentObserver {
    public function created(Agent $agent): void {}

    public function updated(Agent $agent): void {}

    public function deleted(Agent $agent): void {
        $person = Person::find($agent->person_id);
        $person->delete();
        foreach ($agent->addresses()->get() as $address) {
            $address->delete();
        }
    }
}
