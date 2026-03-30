<?php

namespace Database\Factories;

use App\Models\Address\Address;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Models\Sms;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Sms> */
class SmsFactory extends Factory {
    protected $model = Sms::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        // Get an actual address with phone and person relationship
        $address = Address::whereNotNull('phone')
            ->whereHasMorph('owner', [Person::class])
            ->inRandomOrder()
            ->first();

        $phone = $address->phone;
        /** @var Person $person */
        $person = $address->owner;
        $fullName = "{$person->name} {$person->surname}";

        // Get an actual meter associated with this person
        $device = Device::where('person_id', $person->id)
            ->where('device_type', 'meter')
            ->inRandomOrder()
            ->first();

        if ($device) {
            $meter = Meter::find($device->device_id);
            $meterNumber = $meter->serial_number;
        } else {
            $meter = Meter::inRandomOrder()->first();
            $meterNumber = $meter->serial_number;
        }

        // Generate realistic transaction amounts (between 1000 and 20000)
        $amount = $this->faker->numberBetween(1000, 20000);

        // Calculate units based on amount (approximately amount/2)
        $units = round($amount / 2, 1);

        // Calculate VAT (1% of amount)
        $vat = round($amount / 100, 2);

        // Generate random token (32 characters)
        $token = Str::random(32);

        // Construct message body
        $body = sprintf(
            'Dear %s, we received your transaction %d.Meter: %s, %s Unit %s .Transaction amount is %d, \n VAT for energy : %s \n VAT for the other staffs : 0 . Your Company etc.',
            $fullName,
            $amount,
            $meterNumber,
            $token,
            $units,
            $amount,
            $vat
        );

        return [
            'receiver' => $phone,
            'trigger_type' => 0,
            'trigger_id' => $this->faker->unique()->numberBetween(1, 10000),
            'body' => $body,
            'status' => 0,
            'uuid' => $this->faker->uuid(),
            'sender_id' => null,
            'direction' => 1,
            'gateway_id' => 1,
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
