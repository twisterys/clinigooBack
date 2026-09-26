<?php

namespace Tests\Feature\Http\Controllers\Api\Client;

use App\Http\Controllers\Api\Client\CareRequestController;
use App\Http\Requests\Api\Client\CareRequestStoreRequest;
use App\Http\Requests\Api\Client\CareRequestUpdateRequest;
use App\Models\Address;
use App\Models\Beneficiary;
use App\Models\CareRequest;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see CareRequestController
 */
final class CareRequestControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_displays_view(): void
    {
        $careRequests = CareRequest::factory()->count(3)->create();

        $response = $this->get(route('care-requests.index'));

        $response->assertOk();
        $response->assertViewIs('care_request.index');
        $response->assertViewHas('careRequests', $careRequests);
    }

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            CareRequestController::class,
            'store',
            CareRequestStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_displays_view(): void
    {
        $beneficiary = Beneficiary::factory()->create();
        $service = Service::factory()->create();
        $address = Address::factory()->create();
        $scheduled_at = Carbon::parse(fake()->dateTime());
        $notes = fake()->text();

        $response = $this->post(route('care-requests.store'), [
            'beneficiary_id' => $beneficiary->id,
            'service_id' => $service->id,
            'address_id' => $address->id,
            'scheduled_at' => $scheduled_at,
            'notes' => $notes,
        ]);

        $careRequests = CareRequest::query()
            ->where('beneficiary_id', $beneficiary->id)
            ->where('service_id', $service->id)
            ->where('address_id', $address->id)
            ->where('scheduled_at', $scheduled_at)
            ->where('notes', $notes)
            ->get();
        $this->assertCount(1, $careRequests);
        $careRequest = $careRequests->first();

        $response->assertOk();
        $response->assertViewIs('care_request.show');
        $response->assertViewHas('careRequest');
    }

    #[Test]
    public function show_displays_view(): void
    {
        $careRequest = CareRequest::factory()->create();

        $response = $this->get(route('care-requests.show', $careRequest));

        $response->assertOk();
        $response->assertViewIs('care_request.show');
        $response->assertViewHas('careRequest', $careRequest);
    }

    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            CareRequestController::class,
            'update',
            CareRequestUpdateRequest::class
        );
    }

    #[Test]
    public function update_displays_view(): void
    {
        $careRequest = CareRequest::factory()->create();
        $beneficiary = Beneficiary::factory()->create();
        $service = Service::factory()->create();
        $address = Address::factory()->create();
        $scheduled_at = Carbon::parse(fake()->dateTime());
        $notes = fake()->text();

        $response = $this->put(route('care-requests.update', $careRequest), [
            'beneficiary_id' => $beneficiary->id,
            'service_id' => $service->id,
            'address_id' => $address->id,
            'scheduled_at' => $scheduled_at,
            'notes' => $notes,
        ]);

        $careRequest->refresh();

        $response->assertOk();
        $response->assertViewIs('care_request.show');
        $response->assertViewHas('careRequest', $careRequest);

        $this->assertEquals($beneficiary->id, $careRequest->beneficiary_id);
        $this->assertEquals($service->id, $careRequest->service_id);
        $this->assertEquals($address->id, $careRequest->address_id);
        $this->assertEquals($scheduled_at, $careRequest->scheduled_at);
        $this->assertEquals($notes, $careRequest->notes);
    }
}
