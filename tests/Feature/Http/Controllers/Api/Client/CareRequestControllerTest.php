<?php

namespace Tests\Feature\Http\Controllers\Api\Client;

use App\Http\Controllers\Api\Client\CareRequestController;
use App\Http\Requests\Api\Client\CareRequestStoreRequest;
use App\Models\Address;
use App\Models\Beneficiary;
use App\Models\CareRequest;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CareRequestControllerTest extends TestCase
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
    public function index_returns_care_requests_json(): void
    {
        CareRequest::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson(route('care-requests.index'));

        $response->assertOk();
        $response->assertJsonStructure(['items', 'data']);
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
    public function store_saves_care_request_with_full_payload(): void
    {
        Storage::fake('public');

        $beneficiary = Beneficiary::factory()->create(['user_id' => $this->user->id]);
        $service = Service::factory()->create();
        $address = Address::factory()->create(['user_id' => $this->user->id]);
        $file = UploadedFile::fake()->create('medical_report.pdf', 500, 'application/pdf');

        $payload = [
            'serviceId' => $service->id,
            'beneficiaryId' => $beneficiary->id,
            'addressId' => $address->id,
            'urgent' => false,
            'date' => '2026-10-15',
            'time' => '14:30',
            'durationHours' => 3.5,
            'description' => 'Test care request description',
            'perimeter' => 15,
            'evaluation' => 4.5,
            'gender' => 'female',
            'certified' => true,
            'free_mobility' => true,
            'documents' => [$file],
        ];

        $response = $this->postJson(route('care-requests.store'), $payload);

        $response->assertCreated();
        $response->assertJsonPath('service_id', $service->id);
        $response->assertJsonPath('beneficiary_id', $beneficiary->id);
        $response->assertJsonPath('address_id', $address->id);
        $response->assertJsonPath('urgent', false);
        $response->assertJsonPath('duration_hours', 3.5);
        $response->assertJsonPath('description', 'Test care request description');
        $response->assertJsonPath('perimeter', 15);
        $response->assertJsonPath('evaluation', 4.5);
        $response->assertJsonPath('gender', 'female');
        $response->assertJsonPath('certified', true);
        $response->assertJsonPath('free_mobility', true);

        $this->assertDatabaseHas('care_requests', [
            'user_id' => $this->user->id,
            'service_id' => $service->id,
            'beneficiary_id' => $beneficiary->id,
            'address_id' => $address->id,
            'urgent' => false,
            'gender' => 'female',
            'certified' => true,
            'free_mobility' => true,
        ]);

        $this->assertDatabaseHas('care_request_documents', [
            'original_name' => 'medical_report.pdf',
        ]);
    }

    #[Test]
    public function store_supports_custom_address_coordinates_when_address_id_omitted(): void
    {
        $beneficiary = Beneficiary::factory()->create(['user_id' => $this->user->id]);
        $service = Service::factory()->create();

        $payload = [
            'serviceId' => $service->id,
            'beneficiaryId' => $beneficiary->id,
            'address' => [
                'latitude' => '35.7595',
                'longitude' => '-5.8340',
            ],
            'urgent' => true,
            'date' => null,
            'time' => null,
            'durationHours' => 2.0,
            'description' => 'Current location request',
            'perimeter' => 10,
            'evaluation' => null,
            'gender' => 'all',
            'certified' => false,
            'free_mobility' => false,
        ];

        $response = $this->postJson(route('care-requests.store'), $payload);

        $response->assertCreated();
        $response->assertJsonPath('urgent', true);
        $response->assertJsonPath('latitude', '35.7595000');
        $response->assertJsonPath('longitude', '-5.8340000');

        $this->assertDatabaseHas('care_requests', [
            'user_id' => $this->user->id,
            'urgent' => true,
            'date' => null,
            'time' => null,
        ]);
    }

    #[Test]
    public function store_fails_validation_when_urgent_is_true_and_date_or_time_is_provided(): void
    {
        $beneficiary = Beneficiary::factory()->create(['user_id' => $this->user->id]);
        $service = Service::factory()->create();
        $address = Address::factory()->create(['user_id' => $this->user->id]);

        $payload = [
            'serviceId' => $service->id,
            'beneficiaryId' => $beneficiary->id,
            'addressId' => $address->id,
            'urgent' => true,
            'date' => '2026-10-15',
            'time' => '14:30',
            'durationHours' => 2.0,
            'description' => 'Urgent request',
            'perimeter' => 10,
            'gender' => 'all',
            'certified' => false,
            'free_mobility' => false,
        ];

        $response = $this->postJson(route('care-requests.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date', 'time']);
    }

    #[Test]
    public function show_returns_care_request_json(): void
    {
        $careRequest = CareRequest::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson(route('care-requests.show', $careRequest));

        $response->assertOk();
        $response->assertJsonPath('id', $careRequest->id);
    }
}
