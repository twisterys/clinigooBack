<?php

namespace Tests\Feature\Http\Controllers\Api\Client;

use App\Http\Controllers\Api\Client\BeneficiaryController;
use App\Http\Requests\Api\Client\BeneficiaryStoreRequest;
use App\Http\Requests\Api\Client\BeneficiaryUpdateRequest;
use App\Models\Beneficiary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see BeneficiaryController
 */
final class BeneficiaryControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_displays_view(): void
    {
        $beneficiaries = Beneficiary::factory()->count(3)->create();

        $response = $this->get(route('beneficiaries.index'));

        $response->assertOk();
        $response->assertViewIs('beneficiary.index');
        $response->assertViewHas('beneficiaries', $beneficiaries);
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
        $first_name = fake()->firstName();
        $last_name = fake()->lastName();
        $relationship = fake()->word();
        $gender = fake()->word();
        $date_of_birth = Carbon::parse(fake()->date());
        $phone = fake()->phoneNumber();
        $medical_notes = fake()->text();
        $is_default = fake()->boolean();

        $response = $this->post(route('beneficiaries.store'), [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'relationship' => $relationship,
            'gender' => $gender,
            'date_of_birth' => $date_of_birth,
            'phone' => $phone,
            'medical_notes' => $medical_notes,
            'is_default' => $is_default,
        ]);

        $beneficiaries = Beneficiary::query()
            ->where('first_name', $first_name)
            ->where('last_name', $last_name)
            ->where('relationship', $relationship)
            ->where('gender', $gender)
            ->where('date_of_birth', $date_of_birth)
            ->where('phone', $phone)
            ->where('medical_notes', $medical_notes)
            ->where('is_default', $is_default)
            ->get();
        $this->assertCount(1, $beneficiaries);
        $beneficiary = $beneficiaries->first();

        $response->assertOk();
        $response->assertViewIs('beneficiary.show');
        $response->assertViewHas('beneficiary');
    }

    #[Test]
    public function show_displays_view(): void
    {
        $beneficiary = Beneficiary::factory()->create();

        $response = $this->get(route('beneficiaries.show', $beneficiary));

        $response->assertOk();
        $response->assertViewIs('beneficiary.show');
        $response->assertViewHas('beneficiary', $beneficiary);
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
        $beneficiary = Beneficiary::factory()->create();
        $first_name = fake()->firstName();
        $last_name = fake()->lastName();
        $relationship = fake()->word();
        $gender = fake()->word();
        $date_of_birth = Carbon::parse(fake()->date());
        $phone = fake()->phoneNumber();
        $medical_notes = fake()->text();
        $is_default = fake()->boolean();

        $response = $this->put(route('beneficiaries.update', $beneficiary), [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'relationship' => $relationship,
            'gender' => $gender,
            'date_of_birth' => $date_of_birth,
            'phone' => $phone,
            'medical_notes' => $medical_notes,
            'is_default' => $is_default,
        ]);

        $beneficiary->refresh();

        $response->assertOk();
        $response->assertViewIs('beneficiary.show');
        $response->assertViewHas('beneficiary', $beneficiary);

        $this->assertEquals($first_name, $beneficiary->first_name);
        $this->assertEquals($last_name, $beneficiary->last_name);
        $this->assertEquals($relationship, $beneficiary->relationship);
        $this->assertEquals($gender, $beneficiary->gender);
        $this->assertEquals($date_of_birth, $beneficiary->date_of_birth);
        $this->assertEquals($phone, $beneficiary->phone);
        $this->assertEquals($medical_notes, $beneficiary->medical_notes);
        $this->assertEquals($is_default, $beneficiary->is_default);
    }

    #[Test]
    public function destroy_deletes_and_redirects(): void
    {
        $beneficiary = Beneficiary::factory()->create();

        $response = $this->delete(route('beneficiaries.destroy', $beneficiary));

        $response->assertRedirect(route('beneficiary.index'));

        $this->assertModelMissing($beneficiary);
    }
}
