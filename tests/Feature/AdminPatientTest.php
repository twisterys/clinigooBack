<?php

use App\Models\Address;
use App\Models\Beneficiary;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('unauthenticated users cannot access patient endpoints', function () {
    $this->getJson('/api/admin/patients')->assertStatus(401);
    $this->postJson('/api/admin/patients', [])->assertStatus(401);
    $this->getJson('/api/admin/patients/1')->assertStatus(401);
    $this->putJson('/api/admin/patients/1', [])->assertStatus(401);
    $this->deleteJson('/api/admin/patients/1')->assertStatus(401);
});

test('non-admin users cannot access patient endpoints', function () {
    $patient = User::factory()->patient()->create();
    $token = Auth::guard('api')->login($patient);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/admin/patients')
        ->assertStatus(401);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/admin/patients', [
            'name' => 'Sample Patient',
            'email' => 'sample@example.com',
        ])
        ->assertStatus(401);
});

test('admin can list patients with datatable structure and pagination', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    User::factory()->count(3)->withPatientProfile()->create();

    // Default datatable format
    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/admin/patients');

    $response->assertOk()
        ->assertJsonStructure(['data', 'draw', 'recordsTotal', 'recordsFiltered']);

    // Paginated format
    $paginatedResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/admin/patients?paginate=true&per_page=2');

    $paginatedResponse->assertOk()
        ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
});

test('admin can store a patient and automatically create a beneficiary with the same name', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $payload = [
        'first_name' => 'Amine',
        'last_name' => 'Berrada',
        'email' => 'amine.berrada@example.com',
        'password' => 'secret1234',
        'phone' => '+212611223344',
        'birthday' => '1990-05-15',
        'sexe' => 'M',
        'cin_amo_cnss' => 'AB123456',
        'medical_notes' => 'Allergic to penicillin',
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/admin/patients', $payload);

    $response->assertStatus(201)
        ->assertJsonFragment([
            'email' => 'amine.berrada@example.com',
            'name' => 'Amine Berrada',
        ]);

    // Verify user in database
    $user = User::where('email', 'amine.berrada@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->isPatient())->toBeTrue()
        ->and($user->name)->toBe('Amine Berrada');

    // Verify patient profile
    expect($user->patientProfile)->not->toBeNull()
        ->and($user->patientProfile->first_name)->toBe('Amine')
        ->and($user->patientProfile->last_name)->toBe('Berrada')
        ->and($user->patientProfile->phone)->toBe('+212611223344')
        ->and($user->patientProfile->birthday?->format('Y-m-d'))->toBe('1990-05-15')
        ->and($user->patientProfile->sexe)->toBe('M');

    // Verify automatic beneficiary creation with the same name
    $beneficiary = Beneficiary::where('user_id', $user->id)->first();
    expect($beneficiary)->not->toBeNull()
        ->and($beneficiary->first_name)->toBe('Amine')
        ->and($beneficiary->last_name)->toBe('Berrada')
        ->and($beneficiary->phone)->toBe('+212611223344')
        ->and($beneficiary->gender)->toBe('M')
        ->and($beneficiary->date_of_birth?->format('Y-m-d'))->toBe('1990-05-15')
        ->and($beneficiary->is_default)->toBeTrue()
        ->and($beneficiary->cin_amo_cnss)->toBe('AB123456')
        ->and($beneficiary->medical_notes)->toBe('Allergic to penicillin');
});

test('admin can store a patient by providing full name and beneficiary is created with parsed name', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $payload = [
        'name' => 'Khadija Mansouri',
        'email' => 'khadija.mansouri@example.com',
        'gender' => 'F',
        'date_of_birth' => '1995-12-01',
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/admin/patients', $payload);

    $response->assertStatus(201);

    $user = User::where('email', 'khadija.mansouri@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Khadija Mansouri');

    $beneficiary = $user->beneficiaries()->first();
    expect($beneficiary)->not->toBeNull()
        ->and($beneficiary->first_name)->toBe('Khadija')
        ->and($beneficiary->last_name)->toBe('Mansouri')
        ->and($beneficiary->is_default)->toBeTrue()
        ->and($beneficiary->gender)->toBe('F');
});

test('admin can filter patients by search and attributes', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $target = User::factory()->patient()->create([
        'name' => 'Zineb Chraibi',
        'email' => 'zineb@example.com',
    ]);
    PatientProfile::create([
        'user_id' => $target->id,
        'first_name' => 'Zineb',
        'last_name' => 'Chraibi',
        'phone' => '+212699001122',
        'sexe' => 'F',
    ]);

    $other = User::factory()->patient()->create([
        'name' => 'Omar Bennani',
        'email' => 'omar@example.com',
    ]);
    PatientProfile::create([
        'user_id' => $other->id,
        'first_name' => 'Omar',
        'last_name' => 'Bennani',
        'phone' => '+212688001122',
        'sexe' => 'M',
    ]);

    // Search filter
    $searchRes = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/admin/patients?search=Chraibi');

    $searchRes->assertOk();
    $data = $searchRes->json('data');
    expect(count($data))->toBe(1)
        ->and($data[0]['email'])->toBe('zineb@example.com');

    // Phone filter
    $phoneRes = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/admin/patients?phone=+212688001122');
    $phoneRes->assertOk();
    $dataPhone = $phoneRes->json('data');
    expect(count($dataPhone))->toBe(1)
        ->and($dataPhone[0]['email'])->toBe('omar@example.com');

    // Gender/sexe filter
    $genderRes = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/admin/patients?gender=F');
    $genderRes->assertOk();
    $dataGender = $genderRes->json('data');
    expect(count($dataGender))->toBe(1)
        ->and($dataGender[0]['email'])->toBe('zineb@example.com');
});

test('admin can view a patient by id', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $patient = User::factory()->withPatientProfile([
        'first_name' => 'Hassan',
        'last_name' => 'El Fassi',
        'phone' => '+212677889900',
    ])->create([
        'email' => 'hassan@example.com',
    ]);

    Beneficiary::factory()->create([
        'user_id' => $patient->id,
        'first_name' => 'Hassan',
        'last_name' => 'El Fassi',
        'is_default' => true,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/admin/patients/{$patient->id}");

    $response->assertOk()
        ->assertJsonFragment([
            'id' => $patient->id,
            'email' => 'hassan@example.com',
        ])
        ->assertJsonStructure([
            'id',
            'name',
            'email',
            'patient_profile',
            'beneficiaries',
        ]);
});

test('admin can update a patient details and sync default beneficiary', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $patient = User::factory()->withPatientProfile([
        'first_name' => 'OldFirst',
        'last_name' => 'OldLast',
    ])->create([
        'name' => 'OldFirst OldLast',
        'email' => 'initial@example.com',
    ]);

    $beneficiary = Beneficiary::factory()->create([
        'user_id' => $patient->id,
        'first_name' => 'OldFirst',
        'last_name' => 'OldLast',
        'is_default' => true,
    ]);

    $updatePayload = [
        'first_name' => 'NewFirst',
        'last_name' => 'NewLast',
        'email' => 'updated@example.com',
        'phone' => '+212600998877',
        'birthday' => '1985-02-10',
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/admin/patients/{$patient->id}", $updatePayload);

    $response->assertOk()
        ->assertJsonFragment([
            'name' => 'NewFirst NewLast',
            'email' => 'updated@example.com',
        ]);

    $patient->refresh();
    expect($patient->email)->toBe('updated@example.com')
        ->and($patient->name)->toBe('NewFirst NewLast')
        ->and($patient->patientProfile->first_name)->toBe('NewFirst')
        ->and($patient->patientProfile->last_name)->toBe('NewLast')
        ->and($patient->patientProfile->phone)->toBe('+212600998877');

    $beneficiary->refresh();
    expect($beneficiary->first_name)->toBe('NewFirst')
        ->and($beneficiary->last_name)->toBe('NewLast');
});

test('admin can delete a patient and their associated records', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $patient = User::factory()->withPatientProfile()->create();

    Beneficiary::factory()->create([
        'user_id' => $patient->id,
    ]);

    Address::create([
        'user_id' => $patient->id,
        'address_line' => '123 Main St',
        'city' => 'Casablanca',
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/admin/patients/{$patient->id}");

    $response->assertStatus(204);

    expect(User::find($patient->id))->toBeNull()
        ->and(PatientProfile::where('user_id', $patient->id)->exists())->toBeFalse()
        ->and(Beneficiary::where('user_id', $patient->id)->exists())->toBeFalse()
        ->and(Address::where('user_id', $patient->id)->exists())->toBeFalse();
});

test('patient store validates unique email and required fields', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    User::factory()->create(['email' => 'duplicate@example.com']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/admin/patients', [
            'email' => 'duplicate@example.com',
            // Missing name and first_name
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'name']);
});
