<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

test('admin guard accepts admin credentials and generates token', function () {
    $admin = User::factory()->admin()->create([
        'password' => bcrypt('secret123'),
    ]);

    $token = Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
    ]);

    expect($token)->not->toBeFalse()
        ->and($token)->toBeString();

    $user = Auth::guard('admin')->setToken($token)->user();
    expect($user)->not->toBeNull()
        ->and($user->id)->toBe($admin->id)
        ->and($user->isAdmin())->toBeTrue();
});

test('admin guard rejects patient and professional credentials', function () {
    $patient = User::factory()->patient()->create([
        'password' => bcrypt('secret123'),
    ]);
    $pro = User::factory()->professional()->create([
        'password' => bcrypt('secret123'),
    ]);

    $patientAttempt = Auth::guard('admin')->attempt([
        'email' => $patient->email,
        'password' => 'secret123',
    ]);
    $proAttempt = Auth::guard('admin')->attempt([
        'email' => $pro->email,
        'password' => 'secret123',
    ]);

    expect($patientAttempt)->toBeFalse()
        ->and($proAttempt)->toBeFalse();
});

test('admin guard rejects patient JWT token on protected routes', function () {
    $patient = User::factory()->patient()->create([
        'password' => bcrypt('secret123'),
    ]);

    $patientToken = Auth::guard('api')->attempt([
        'email' => $patient->email,
        'password' => 'secret123',
    ]);

    // Admin guard should not resolve patient
    $resolvedUser = Auth::guard('admin')->setToken($patientToken)->user();
    expect($resolvedUser)->toBeNull();

    // Calling admin me with patient token returns 401
    $response = $this->withHeader('Authorization', 'Bearer '.$patientToken)
        ->getJson('/api/admin/auth/me');

    $response->assertStatus(401);
});

test('admin can login via admin auth login endpoint', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'superadmin@clinigoo.test',
        'password' => bcrypt('adminpass123'),
    ]);

    $response = $this->postJson('/api/admin/auth/login', [
        'email' => 'superadmin@clinigoo.test',
        'password' => 'adminpass123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'guard',
            'expires_in',
        ])
        ->assertJson([
            'guard' => 'admin',
        ]);
});

test('non-admin users cannot login via admin auth login endpoint', function () {
    $patient = User::factory()->patient()->create([
        'email' => 'patient@clinigoo.test',
        'password' => bcrypt('pass12345'),
    ]);

    $response = $this->postJson('/api/admin/auth/login', [
        'email' => 'patient@clinigoo.test',
        'password' => 'pass12345',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'error' => 'Identifiants administrateur invalides ou accès non autorisé.',
        ]);
});

test('admin can get their profile via admin me endpoint', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/admin/auth/me');

    $response->assertStatus(200)
        ->assertJson([
            'email' => $admin->email,
            'type' => 'admin',
        ]);
});

test('admin can logout and invalidate their token', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/admin/auth/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Déconnexion réussie.']);

    // Subsequent call with invalidated token fails
    $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/admin/auth/me');

    $meResponse->assertStatus(401);
});

test('admin can refresh their token', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/admin/auth/refresh');

    $response->assertStatus(200)
        ->assertJsonStructure(['access_token', 'token_type', 'guard', 'expires_in']);
});
