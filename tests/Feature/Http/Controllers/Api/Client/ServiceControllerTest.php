<?php

namespace Tests\Feature\Http\Controllers\Api\Client;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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
        Service::factory()->count(3)->create([
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/services');

        $response->assertOk()
            ->assertJsonStructure([
                'items' => [
                    '*' => [
                        'id',
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
        $service = Service::factory()->create([
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
        Service::factory()->count(4)->create([
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/services/recent');

        $response->assertOk()
            ->assertJsonStructure(['items', 'data']);
    }
}
