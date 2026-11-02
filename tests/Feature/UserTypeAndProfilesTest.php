<?php

use App\Enums\UserType;
use App\Models\PatientProfile;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user types enum supports patient, professional, and admin', function () {
    expect(UserType::Patient->value)->toBe('patient')
        ->and(UserType::Professional->value)->toBe('professional')
        ->and(UserType::Admin->value)->toBe('admin')
        ->and(UserType::values())->toEqual(['patient', 'professional', 'admin']);
});

test('users can be created with distinct types and helper methods work', function () {
    $patient = User::factory()->patient()->create();
    $pro = User::factory()->professional()->create();
    $admin = User::factory()->admin()->create();

    expect($patient->isPatient())->toBeTrue()
        ->and($patient->isProfessional())->toBeFalse()
        ->and($patient->isAdmin())->toBeFalse()
        ->and($pro->isPatient())->toBeFalse()
        ->and($pro->isProfessional())->toBeTrue()
        ->and($pro->isAdmin())->toBeFalse()
        ->and($admin->isPatient())->toBeFalse()
        ->and($admin->isProfessional())->toBeFalse()
        ->and($admin->isAdmin())->toBeTrue();
});

test('user query scopes filter by type correctly', function () {
    User::factory()->count(2)->patient()->create();
    User::factory()->count(3)->professional()->create();
    User::factory()->count(1)->admin()->create();

    expect(User::patients()->count())->toBe(2)
        ->and(User::professionals()->count())->toBe(3)
        ->and(User::admins()->count())->toBe(1);
});

test('patient profile has firstName, lastName, birthday, sexe, phone, email', function () {
    $user = User::factory()->patient()->create();

    $profile = PatientProfile::create([
        'user_id' => $user->id,
        'first_name' => 'Sara',
        'last_name' => 'El Amrani',
        'birthday' => '1995-04-12',
        'sexe' => 'F',
        'phone' => '+212600112233',
        'email' => 'sara@clinigoo.test',
    ]);

    expect($user->patientProfile)->not->toBeNull()
        ->and($user->patientProfile->first_name)->toBe('Sara')
        ->and($user->patientProfile->last_name)->toBe('El Amrani')
        ->and($user->patientProfile->birthday->format('Y-m-d'))->toBe('1995-04-12')
        ->and($user->patientProfile->sexe)->toBe('F')
        ->and($user->patientProfile->phone)->toBe('+212600112233')
        ->and($user->patientProfile->email)->toBe('sara@clinigoo.test')
        ->and($user->patientProfile->full_name)->toBe('Sara El Amrani')
        ->and($user->patientProfile->firstName)->toBe('Sara')
        ->and($user->patientProfile->lastName)->toBe('El Amrani')
        ->and($user->profile->id)->toBe($profile->id);
});

test('professional profile has firstName, lastName, sexe, ID', function () {
    $user = User::factory()->professional()->create();

    $profile = ProfessionalProfile::create([
        'user_id' => $user->id,
        'first_name' => 'Karim',
        'last_name' => 'Benali',
        'sexe' => 'M',
        'id_number' => 'DOC-48192',
    ]);

    expect($user->professionalProfile)->not->toBeNull()
        ->and($user->professionalProfile->first_name)->toBe('Karim')
        ->and($user->professionalProfile->last_name)->toBe('Benali')
        ->and($user->professionalProfile->sexe)->toBe('M')
        ->and($user->professionalProfile->id_number)->toBe('DOC-48192')
        ->and($user->professionalProfile->professional_id)->toBe('DOC-48192')
        ->and($user->professionalProfile->full_name)->toBe('Karim Benali')
        ->and($user->professionalProfile->firstName)->toBe('Karim')
        ->and($user->professionalProfile->lastName)->toBe('Benali')
        ->and($user->profile->id)->toBe($profile->id);
});

test('deleting a user cascades and deletes their profile', function () {
    $patient = User::factory()->withPatientProfile()->create();
    $pro = User::factory()->withProfessionalProfile()->create();

    expect(PatientProfile::where('user_id', $patient->id)->exists())->toBeTrue()
        ->and(ProfessionalProfile::where('user_id', $pro->id)->exists())->toBeTrue();

    $patient->delete();
    $pro->delete();

    expect(PatientProfile::where('user_id', $patient->id)->exists())->toBeFalse()
        ->and(ProfessionalProfile::where('user_id', $pro->id)->exists())->toBeFalse();
});

test('registration api creates patient user and patient profile', function () {
    $response = $this->postJson('/api/auth/register', [
        'email' => 'newpatient@clinigoo.test',
        'password' => 'secret1234',
        'type' => 'patient',
        'first_name' => 'Youssef',
        'last_name' => 'Tazi',
        'birthday' => '1992-08-20',
        'sexe' => 'M',
        'phone' => '+212611223344',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);

    $user = User::where('email', 'newpatient@clinigoo.test')->first();
    expect($user)->not->toBeNull()
        ->and($user->isPatient())->toBeTrue()
        ->and($user->patientProfile)->not->toBeNull()
        ->and($user->patientProfile->first_name)->toBe('Youssef')
        ->and($user->patientProfile->last_name)->toBe('Tazi')
        ->and($user->patientProfile->birthday->format('Y-m-d'))->toBe('1992-08-20')
        ->and($user->patientProfile->sexe)->toBe('M')
        ->and($user->patientProfile->phone)->toBe('+212611223344');
});

test('registration api creates professional user and professional profile with ID', function () {
    $response = $this->postJson('/api/auth/register', [
        'email' => 'nurse.fatima@clinigoo.test',
        'password' => 'secret1234',
        'type' => 'professional',
        'firstName' => 'Fatima',
        'lastName' => 'Zahra',
        'sexe' => 'F',
        'ID' => 'NURSE-7890',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);

    $user = User::where('email', 'nurse.fatima@clinigoo.test')->first();
    expect($user)->not->toBeNull()
        ->and($user->isProfessional())->toBeTrue()
        ->and($user->professionalProfile)->not->toBeNull()
        ->and($user->professionalProfile->first_name)->toBe('Fatima')
        ->and($user->professionalProfile->last_name)->toBe('Zahra')
        ->and($user->professionalProfile->sexe)->toBe('F')
        ->and($user->professionalProfile->id_number)->toBe('NURSE-7890');
});

test('authenticated user can get and update their patient profile', function () {
    $user = User::factory()->withPatientProfile([
        'first_name' => 'Initial',
        'last_name' => 'Name',
        'birthday' => '2000-01-01',
        'sexe' => 'M',
        'phone' => '0600000000',
    ])->create();

    $token = auth('api')->login($user);

    // Get profile
    $getRes = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/auth/profile');

    $getRes->assertStatus(200)
        ->assertJson([
            'type' => 'patient',
            'profile' => [
                'first_name' => 'Initial',
                'last_name' => 'Name',
            ],
        ]);

    // Update profile
    $putRes = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/auth/profile', [
            'firstName' => 'UpdatedFirst',
            'lastName' => 'UpdatedLast',
            'birthday' => '1998-11-25',
            'sexe' => 'F',
            'phone' => '+212699887766',
            'email' => 'contact@test.com',
        ]);

    $putRes->assertStatus(200)
        ->assertJson([
            'message' => 'Profil patient mis à jour avec succès.',
            'profile' => [
                'first_name' => 'UpdatedFirst',
                'last_name' => 'UpdatedLast',
                'sexe' => 'F',
                'phone' => '+212699887766',
                'email' => 'contact@test.com',
            ],
        ]);

    $user->refresh();
    expect($user->patientProfile->first_name)->toBe('UpdatedFirst')
        ->and($user->patientProfile->last_name)->toBe('UpdatedLast')
        ->and($user->patientProfile->birthday->format('Y-m-d'))->toBe('1998-11-25');
});

test('authenticated professional can get and update their professional profile', function () {
    $user = User::factory()->withProfessionalProfile([
        'first_name' => 'Dr.',
        'last_name' => 'House',
        'sexe' => 'M',
        'id_number' => 'DOC-001',
    ])->create();

    $token = auth('api')->login($user);

    // Get profile
    $getRes = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/auth/profile');

    $getRes->assertStatus(200)
        ->assertJson([
            'type' => 'professional',
            'profile' => [
                'first_name' => 'Dr.',
                'last_name' => 'House',
                'id_number' => 'DOC-001',
            ],
        ]);

    // Update profile
    $putRes = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/auth/profile', [
            'firstName' => 'Doctor Gregory',
            'lastName' => 'House MD',
            'sexe' => 'M',
            'ID' => 'DOC-007',
        ]);

    $putRes->assertStatus(200)
        ->assertJson([
            'message' => 'Profil professionnel mis à jour avec succès.',
            'profile' => [
                'first_name' => 'Doctor Gregory',
                'last_name' => 'House MD',
                'id_number' => 'DOC-007',
            ],
        ]);

    $user->refresh();
    expect($user->professionalProfile->first_name)->toBe('Doctor Gregory')
        ->and($user->professionalProfile->last_name)->toBe('House MD')
        ->and($user->professionalProfile->id_number)->toBe('DOC-007');
});
