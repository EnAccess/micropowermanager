<?php

namespace Tests\Feature;

use App\Exceptions\EntityHasChildrenException;
use App\Models\Cluster;
use Database\Factories\ClusterFactory;
use Database\Factories\MiniGridFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class ClusterTest extends TestCase {
    use RefreshMultipleDatabases;
    use WithFaker;

    private $user;

    protected function setUp(): void {
        parent::setUp();
        $this->user = UserFactory::new()->create();
        $this->assignRole($this->user, 'admin');
    }

    public function testUserUpdatesACluster(): void {
        $cluster = ClusterFactory::new()->create([
            'name' => 'Original',
            'manager_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->put("/api/clusters/{$cluster->id}", [
            'name' => 'Renamed',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('Renamed', $response['data']['name']);
        $this->assertEquals('Renamed', $cluster->fresh()->name);
    }

    public function testUserSoftDeletesAChildlessCluster(): void {
        $cluster = ClusterFactory::new()->create(['manager_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->delete("/api/clusters/{$cluster->id}");

        $response->assertStatus(200);
        $this->assertNull(Cluster::query()->find($cluster->id));
        $this->assertNotNull(Cluster::withTrashed()->find($cluster->id)->deleted_at);
    }

    public function testClusterDeleteBlockedWhenItHasMiniGrids(): void {
        $cluster = ClusterFactory::new()->create(['manager_id' => $this->user->id]);
        MiniGridFactory::new()->create([
            'cluster_id' => $cluster->id,
            'name' => 'blocking-mini-grid',
        ]);

        $this->withoutExceptionHandling();
        $this->expectException(EntityHasChildrenException::class);

        try {
            $this->actingAs($this->user)->delete("/api/clusters/{$cluster->id}");
        } finally {
            $this->assertNotNull(Cluster::query()->find($cluster->id));
        }
    }

    public function testTrashedClustersHiddenFromIndex(): void {
        $keep = ClusterFactory::new()->create(['manager_id' => $this->user->id]);
        $trash = ClusterFactory::new()->create(['manager_id' => $this->user->id]);
        $trash->delete();

        $response = $this->actingAs($this->user)->get('/api/clusters');

        $response->assertStatus(200);
        $ids = array_column($response['data'], 'id');
        $this->assertContains($keep->id, $ids);
        $this->assertNotContains($trash->id, $ids);
    }
}
