<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appliance;
use Database\Factories\UserFactory;
use Tests\TestCase;

class ApplianceImportControllerTest extends TestCase {
    public function testImportCreatesAnApplianceAndParsesADisplayPrice(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'appliances');

        $response = $this->actingAs($user)->postJson('/api/import/appliances', [
            'data' => [['appliance_name' => 'Imported Appliance', 'appliance_type' => 'Imported Type', 'price' => '1,500']],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.success', true);
        $response->assertJsonPath('data.added_count', 1);

        $appliance = Appliance::query()->where('name', 'Imported Appliance')->first();
        $this->assertNotNull($appliance);
        $this->assertSame(1500, (int) $appliance->price);
    }
}
