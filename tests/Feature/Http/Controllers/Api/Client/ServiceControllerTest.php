<?php

namespace Tests\Feature\Http\Controllers\Api\Client;

use App\Models\Profession;
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

    #[Test]
    public function by_profession_returns_only_active_services_of_that_profession(): void
    {
        $profession = Profession::factory()->create();

        $matching = Service::factory()->create(['is_active' => true]);
        $inactive = Service::factory()->create(['is_active' => false]);
        $otherProfession = Service::factory()->create(['is_active' => true]);

        $profession->services()->attach([$matching->id, $inactive->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/services/profession/{$profession->id}");

        $response->assertOk()
            ->assertJsonPath('profession.id', $profession->id)
            ->assertJsonPath('items.0.id', (string) $matching->id)
            ->assertJsonCount(1, 'items')
            ->assertJsonMissing(['id' => (string) $inactive->id])
            ->assertJsonMissing(['id' => (string) $otherProfession->id]);
    }
}
