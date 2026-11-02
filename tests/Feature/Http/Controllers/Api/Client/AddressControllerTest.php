<?php

namespace Tests\Feature\Http\Controllers\Api\Client;

use App\Http\Controllers\Api\Client\AddressController;
use App\Http\Requests\Api\Client\AddressStoreRequest;
use App\Http\Requests\Api\Client\AddressUpdateRequest;
use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see AddressController
 */
final class AddressControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    #[Test]
    public function index_displays_view(): void
    {
        Address::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson(route('addresses.index'));

        $response->assertOk();
        $response->assertJsonStructure(['items', 'data']);
    }

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            AddressController::class,
            'store',
            AddressStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_displays_view(): void
    {
        $payload = [
            'label' => 'Home',
            'address_line' => '123 Main St',
            'city' => 'Tanger',
            'postal_code' => '90000',
            'latitude' => 35.7595,
            'longitude' => -5.8340,
            'instructions' => 'Near the park',
            'is_default' => true,
        ];

        $response = $this->postJson(route('addresses.store'), $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('addresses', [
            'user_id' => $this->user->id,
            'label' => 'Home',
            'address_line' => '123 Main St',
            'city' => 'Tanger',
        ]);
    }

    #[Test]
    public function show_displays_view(): void
    {
        $address = Address::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson(route('addresses.show', $address));

        $response->assertOk();
        $response->assertJsonPath('id', $address->id);
    }

    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            AddressController::class,
            'update',
            AddressUpdateRequest::class
        );
    }

    #[Test]
    public function update_displays_view(): void
    {
        $address = Address::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson(route('addresses.update', $address), [
            'label' => 'Work',
            'address_line' => '456 Business Ave',
            'city' => 'Tanger',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'label' => 'Work',
        ]);
    }

    #[Test]
    public function destroy_deletes_and_redirects(): void
    {
        $address = Address::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson(route('addresses.destroy', $address));

        $response->assertOk();
        $this->assertModelMissing($address);
    }
}
