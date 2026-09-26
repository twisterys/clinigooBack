<?php

namespace Tests\Feature\Http\Controllers\Api\Client;

use App\Http\Controllers\Api\Client\AddressController;
use App\Http\Requests\Api\Client\AddressStoreRequest;
use App\Http\Requests\Api\Client\AddressUpdateRequest;
use App\Models\Address;
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

    #[Test]
    public function index_displays_view(): void
    {
        $addresses = Address::factory()->count(3)->create();

        $response = $this->get(route('addresses.index'));

        $response->assertOk();
        $response->assertViewIs('address.index');
        $response->assertViewHas('addresses', $addresses);
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
        $label = fake()->word();
        $address_line = fake()->word();
        $city = fake()->city();
        $postal_code = fake()->postcode();
        $latitude = fake()->latitude();
        $longitude = fake()->longitude();
        $instructions = fake()->text();
        $is_default = fake()->boolean();

        $response = $this->post(route('addresses.store'), [
            'label' => $label,
            'address_line' => $address_line,
            'city' => $city,
            'postal_code' => $postal_code,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'instructions' => $instructions,
            'is_default' => $is_default,
        ]);

        $addresses = Address::query()
            ->where('label', $label)
            ->where('address_line', $address_line)
            ->where('city', $city)
            ->where('postal_code', $postal_code)
            ->where('latitude', $latitude)
            ->where('longitude', $longitude)
            ->where('instructions', $instructions)
            ->where('is_default', $is_default)
            ->get();
        $this->assertCount(1, $addresses);
        $address = $addresses->first();

        $response->assertOk();
        $response->assertViewIs('address.show');
        $response->assertViewHas('address');
    }

    #[Test]
    public function show_displays_view(): void
    {
        $address = Address::factory()->create();

        $response = $this->get(route('addresses.show', $address));

        $response->assertOk();
        $response->assertViewIs('address.show');
        $response->assertViewHas('address', $address);
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
        $address = Address::factory()->create();
        $label = fake()->word();
        $address_line = fake()->word();
        $city = fake()->city();
        $postal_code = fake()->postcode();
        $latitude = fake()->latitude();
        $longitude = fake()->longitude();
        $instructions = fake()->text();
        $is_default = fake()->boolean();

        $response = $this->put(route('addresses.update', $address), [
            'label' => $label,
            'address_line' => $address_line,
            'city' => $city,
            'postal_code' => $postal_code,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'instructions' => $instructions,
            'is_default' => $is_default,
        ]);

        $address->refresh();

        $response->assertOk();
        $response->assertViewIs('address.show');
        $response->assertViewHas('address', $address);

        $this->assertEquals($label, $address->label);
        $this->assertEquals($address_line, $address->address_line);
        $this->assertEquals($city, $address->city);
        $this->assertEquals($postal_code, $address->postal_code);
        $this->assertEquals($latitude, $address->latitude);
        $this->assertEquals($longitude, $address->longitude);
        $this->assertEquals($instructions, $address->instructions);
        $this->assertEquals($is_default, $address->is_default);
    }

    #[Test]
    public function destroy_deletes_and_redirects(): void
    {
        $address = Address::factory()->create();

        $response = $this->delete(route('addresses.destroy', $address));

        $response->assertRedirect(route('address.index'));

        $this->assertModelMissing($address);
    }
}
