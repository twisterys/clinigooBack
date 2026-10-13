<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceCategory;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

test('unauthenticated users cannot update a service', function () {
    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Initial Name',
    ]);

    $response = $this->putJson("/api/admin/services/{$service->id}", [
        'name' => 'Attempted Name',
    ]);

    $response->assertStatus(401);
});

test('non-admin users cannot update a service', function () {
    $patient = User::factory()->patient()->create();
    $token = Auth::guard('api')->login($patient);

    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Initial Name',
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/admin/services/{$service->id}", [
            'name' => 'Attempted Name',
        ]);

    $response->assertStatus(401);
});

test('admin can list services with filters and datatable', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $category = ServiceCategory::factory()->create();
    Service::factory()->count(3)->create(['service_category_id' => $category->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/admin/services');

    $response->assertOk()
        ->assertJsonStructure(['data', 'draw', 'recordsTotal', 'recordsFiltered']);
});

test('admin can create a service', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $category = ServiceCategory::factory()->create();
    $icon = UploadedFile::fake()->image('icon.png');

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/admin/services', [
            'service_category_id' => $category->id,
            'name' => 'New Service',
            'description' => 'New Service Description',
            'base_price' => 120.00,
            'duration_minutes' => 45,
            'is_active' => true,
            'icon' => $icon,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'name' => 'New Service',
            'service_category_id' => $category->id,
            'base_price' => '120.00',
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

    $created = Service::where('name', 'New Service')->first();
    expect($created)->not->toBeNull()
        ->and(Storage::disk('public')->exists($created->icon))->toBeTrue();
});

test('admin can update a service details', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $category = ServiceCategory::factory()->create(['name' => 'General Medicine']);
    $newCategory = ServiceCategory::factory()->create(['name' => 'Nursing']);

    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Old Service Name',
        'description' => 'Old description',
        'base_price' => 100.00,
        'duration_minutes' => 60,
        'is_active' => true,
    ]);

    $payload = [
        'service_category_id' => $newCategory->id,
        'name' => 'Updated Service Name',
        'description' => 'Updated service description',
        'base_price' => 150.00,
        'duration_minutes' => 90,
        'is_active' => false,
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/admin/services/{$service->id}", $payload);

    $response->assertOk()
        ->assertJson([
            'id' => $service->id,
            'service_category_id' => $newCategory->id,
            'name' => 'Updated Service Name',
            'description' => 'Updated service description',
            'base_price' => '150.00',
            'duration_minutes' => 90,
            'is_active' => false,
        ]);

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'service_category_id' => $newCategory->id,
        'name' => 'Updated Service Name',
        'description' => 'Updated service description',
        'base_price' => 150.00,
        'duration_minutes' => 90,
        'is_active' => false,
    ]);
});

test('admin can update a service icon', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Service With Icon',
        'icon' => 'service_icons/old_icon.png',
    ]);

    Storage::disk('public')->put('service_icons/old_icon.png', 'fake old icon content');

    $newIcon = UploadedFile::fake()->image('new_icon.png');

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/admin/services/{$service->id}", [
            '_method' => 'PUT',
            'name' => 'Service With New Icon',
            'icon' => $newIcon,
        ]);

    $response->assertOk();

    $service->refresh();
    expect($service->icon)->not->toBe('service_icons/old_icon.png')
        ->and(Storage::disk('public')->exists($service->icon))->toBeTrue()
        ->and(Storage::disk('public')->exists('service_icons/old_icon.png'))->toBeFalse();
});

test('service update validates input', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create(['service_category_id' => $category->id]);

    $invalidFile = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/admin/services/{$service->id}", [
            '_method' => 'PUT',
            'service_category_id' => 999999, // non-existent category
            'icon' => $invalidFile, // pdf not allowed for icon
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['service_category_id', 'icon']);
});

test('admin can view a service', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $category = ServiceCategory::factory()->create(['name' => 'Cardiology']);
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Consultation',
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/admin/services/{$service->id}");

    $response->assertOk()
        ->assertJson([
            'id' => $service->id,
            'name' => 'Consultation',
            'service_category' => [
                'id' => $category->id,
            ],
        ]);
});

test('admin can delete a service', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'icon' => 'service_icons/to_delete.png',
    ]);

    Storage::disk('public')->put('service_icons/to_delete.png', 'content');

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/admin/services/{$service->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('services', ['id' => $service->id]);
    expect(Storage::disk('public')->exists('service_icons/to_delete.png'))->toBeFalse();
});
