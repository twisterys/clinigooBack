<?php

namespace Tests\Feature\Http\Controllers\Api\Client;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceCategory;
use Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->patient()->create();
        $this->token = Auth::guard('api')->login($this->user);
    }

    #[Test]
    public function index_returns_services_list(): void
    {
        $category = ServiceCategory::factory()->create();
        Service::factory()->count(3)->create([
            'service_category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/services');

        $response->assertOk()
            ->assertJsonStructure([
                'items' => [
                    '*' => [
                        'id',
                        'service_category',
                        'name',
                        'description',
                        'icon',
                        'base_price',
                        'defaultDurationHours',
                    ],
                ],
                'data',
            ]);
    }

    #[Test]
    public function show_returns_single_service(): void
    {
        $category = ServiceCategory::factory()->create();
        $service = Service::factory()->create([
            'service_category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/services/{$service->id}");

        $response->assertOk()
            ->assertJsonPath('id', (string) $service->id);
    }

    #[Test]
    public function recent_returns_recent_services(): void
    {
        $category = ServiceCategory::factory()->create();
        Service::factory()->count(4)->create([
            'service_category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/services/recent');

        $response->assertOk()
            ->assertJsonStructure(['items', 'data']);
    }
}
