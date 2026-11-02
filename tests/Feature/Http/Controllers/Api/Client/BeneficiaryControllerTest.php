<?php

namespace Tests\Feature\Http\Controllers\Api\Client;

use App\Http\Controllers\Api\Client\BeneficiaryController;
use App\Http\Requests\Api\Client\BeneficiaryStoreRequest;
use App\Http\Requests\Api\Client\BeneficiaryUpdateRequest;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see BeneficiaryController
 */
final class BeneficiaryControllerTest extends TestCase
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
        Beneficiary::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson(route('beneficiaries.index'));

        $response->assertOk();
        $response->assertJsonStructure(['items', 'data']);
    }

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            BeneficiaryController::class,
            'store',
            BeneficiaryStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_displays_view(): void
    {
        $payload = [
            'first_name' => 'Sara',
            'last_name' => 'El Amrani',
            'relationship' => 'mother',
            'gender' => 'female',
            'date_of_birth' => '1965-05-12',
            'phone' => '+212600000000',
            'medical_notes' => 'Diabetic',
            'is_default' => true,
        ];

        $response = $this->postJson(route('beneficiaries.store'), $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('beneficiaries', [
            'user_id' => $this->user->id,
            'first_name' => 'Sara',
            'last_name' => 'El Amrani',
        ]);
    }

    #[Test]
    public function show_displays_view(): void
    {
        $beneficiary = Beneficiary::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson(route('beneficiaries.show', $beneficiary));

        $response->assertOk();
        $response->assertJsonPath('id', $beneficiary->id);
    }

    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            BeneficiaryController::class,
            'update',
            BeneficiaryUpdateRequest::class
        );
    }

    #[Test]
    public function update_displays_view(): void
    {
        $beneficiary = Beneficiary::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson(route('beneficiaries.update', $beneficiary), [
            'first_name' => 'Fatima',
            'last_name' => 'El Amrani',
            'relationship' => 'mother',
            'gender' => 'female',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'first_name' => 'Fatima',
        ]);
    }

    #[Test]
    public function destroy_deletes_and_redirects(): void
    {
        $beneficiary = Beneficiary::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson(route('beneficiaries.destroy', $beneficiary));

        $response->assertOk();
        $this->assertModelMissing($beneficiary);
    }
}
