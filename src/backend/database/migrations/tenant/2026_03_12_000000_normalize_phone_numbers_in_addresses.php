<?php

use App\Helpers\PhoneNumberNormalizer;
use App\Models\Address\Address;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void {
        Address::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->chunkById(500, function ($addresses): void {
                foreach ($addresses as $address) {
                    $normalized = PhoneNumberNormalizer::normalize($address->getRawOriginal('phone'));

                    if ($normalized !== $address->getRawOriginal('phone')) {
                        $address->phone = $normalized;
                        $address->save();
                    }
                }
            });
    }

    public function down(): void {
        // Normalization is not reversible
    }
};
