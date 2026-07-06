<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Cluster;
use Database\Factories\UserFactory;
use Tests\TestCase;

class ClusterImportControllerTest extends TestCase {
    public function testImportCreatesAClusterFromNameOnly(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'customers');

        $response = $this->actingAs($user)->postJson('/api/import/clusters', [
            'data' => [['cluster_name' => 'Imported Cluster']],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.success', true);
        $response->assertJsonPath('data.added_count', 1);

        $cluster = Cluster::query()->where('name', 'Imported Cluster')->first();
        $this->assertNotNull($cluster);
        $this->assertSame($user->id, $cluster->manager_id);
    }
}
