<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Modules\Professions\Models\Profession;
use Modules\Professions\Models\ProfessionDocument;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceCategory;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

test('unauthenticated users cannot access admin profession routes', function () {
    $response = $this->getJson('/api/admin/professions');
    $response->assertStatus(401);

    $response = $this->postJson('/api/admin/professions', ['name' => 'Kiné']);
    $response->assertStatus(401);
});

test('clients can list active professions with services and documents', function () {
    $user = User::factory()->patient()->create();
    $token = Auth::guard('api')->login($user);

    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create(['service_category_id' => $category->id]);

    $doc = ProfessionDocument::factory()->create([
        'name' => 'Carte CPS',
        'formats' => ['pdf', 'png'],
    ]);

    $profession = Profession::factory()->create(['name' => 'Infirmier Libéral', 'is_active' => true]);
    $profession->services()->attach($service->id);
    $profession->documents()->attach($doc->id, ['required' => true]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/professions');

    $response->assertOk()
        ->assertJsonStructure([
            'items' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'slug',
                    'icon',
                    'is_active',
                    'services' => [
                        '*' => ['id', 'name', 'base_price'],
                    ],
                    'documents' => [
                        '*' => ['id', 'name', 'formats', 'enabled_formats', 'required'],
                    ],
                ],
            ],
            'data',
        ]);

    $first = $response->json('items.0');
    expect($first['documents'][0]['required'])->toBeTrue()
        ->and($first['documents'][0]['formats'])->toEqual(['pdf', 'png'])
        ->and($first['documents'][0]['enabled_formats'])->toEqual(['pdf', 'png']);
});

test('clients can view single profession with required and optional documents', function () {
    $user = User::factory()->patient()->create();
    $token = Auth::guard('api')->login($user);

    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create(['service_category_id' => $category->id]);

    $reqDoc = ProfessionDocument::factory()->create([
        'name' => 'Diplôme',
        'formats' => ['pdf'],
    ]);

    $optDoc = ProfessionDocument::factory()->create([
        'name' => 'Recommandation',
        'formats' => ['pdf', 'jpg'],
    ]);

    $profession = Profession::factory()->create(['name' => 'Aide-Soignant']);
    $profession->services()->attach($service->id);
    $profession->documents()->attach([
        $reqDoc->id => ['required' => true],
        $optDoc->id => ['required' => false],
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/professions/{$profession->id}");

    $response->assertOk()
        ->assertJsonPath('name', 'Aide-Soignant');

    $docs = collect($response->json('documents'));
    $diplome = $docs->firstWhere('id', $reqDoc->id);
    $recom = $docs->firstWhere('id', $optDoc->id);

    expect($diplome['required'])->toBeTrue()
        ->and($recom['required'])->toBeFalse();
});

test('clients can list profession documents and view enabled formats', function () {
    $user = User::factory()->patient()->create();
    $token = Auth::guard('api')->login($user);

    ProfessionDocument::factory()->create([
        'name' => 'Assurance RC Pro',
        'formats' => ['pdf'],
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/profession-documents');

    $response->assertOk()
        ->assertJsonStructure([
            'items' => [
                '*' => ['id', 'name', 'formats', 'enabled_formats', 'is_active'],
            ],
            'data',
        ]);
});

test('admin can create and update profession documents', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    // Create document
    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/admin/profession-documents', [
            'name' => 'Carte Ordinale',
            'description' => 'Inscription au tableau de l ordre',
            'formats' => ['pdf', 'png', 'jpeg'],
            'is_active' => true,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'name' => 'Carte Ordinale',
            'formats' => ['pdf', 'png', 'jpeg'],
            'is_active' => true,
        ]);

    $docId = $response->json('id');

    // Update document
    $updateResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/admin/profession-documents/{$docId}", [
            'name' => 'Carte de l Ordre National',
            'formats' => ['pdf'],
        ]);

    $updateResponse->assertOk()
        ->assertJson([
            'name' => 'Carte de l Ordre National',
            'formats' => ['pdf'],
        ]);

    $this->assertDatabaseHas('profession_documents', [
        'id' => $docId,
        'name' => 'Carte de l Ordre National',
    ]);
});

test('admin can create profession with services and documents with required pivot', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $category = ServiceCategory::factory()->create();
    $service1 = Service::factory()->create(['service_category_id' => $category->id]);
    $service2 = Service::factory()->create(['service_category_id' => $category->id]);

    $doc1 = ProfessionDocument::factory()->create(['name' => 'Diplome']);
    $doc2 = ProfessionDocument::factory()->create(['name' => 'Casier']);

    $payload = [
        'name' => 'Sage-Femme',
        'description' => 'Profession médicale maïeutique',
        'services' => [$service1->id, $service2->id],
        'documents' => [
            ['id' => $doc1->id, 'required' => true],
            ['id' => $doc2->id, 'required' => false],
        ],
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/admin/professions', $payload);

    $response->assertStatus(201)
        ->assertJson([
            'name' => 'Sage-Femme',
        ]);

    $professionId = $response->json('id');
    $profession = Profession::with(['services', 'documents'])->findOrFail($professionId);

    expect($profession->services->pluck('id')->all())->toEqualCanonicalizing([$service1->id, $service2->id]);

    $this->assertDatabaseHas('profession_service', [
        'profession_id' => $professionId,
        'service_id' => $service1->id,
    ]);

    $this->assertDatabaseHas('profession_profession_document', [
        'profession_id' => $professionId,
        'profession_document_id' => $doc1->id,
        'required' => true,
    ]);

    $this->assertDatabaseHas('profession_profession_document', [
        'profession_id' => $professionId,
        'profession_document_id' => $doc2->id,
        'required' => false,
    ]);
});

test('admin can update profession and sync its services and documents', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $category = ServiceCategory::factory()->create();
    $service1 = Service::factory()->create(['service_category_id' => $category->id]);
    $service2 = Service::factory()->create(['service_category_id' => $category->id]);

    $doc1 = ProfessionDocument::factory()->create();
    $doc2 = ProfessionDocument::factory()->create();

    $profession = Profession::factory()->create(['name' => 'Kinésithérapeute']);
    $profession->services()->attach($service1->id);
    $profession->documents()->attach($doc1->id, ['required' => true]);

    $updatePayload = [
        'name' => 'Masseur-Kinésithérapeute D.E.',
        'services' => [$service2->id],
        'documents' => [
            ['id' => $doc2->id, 'required' => true],
        ],
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/admin/professions/{$profession->id}", $updatePayload);

    $response->assertOk()
        ->assertJson(['name' => 'Masseur-Kinésithérapeute D.E.']);

    $profession->refresh();
    expect($profession->services->pluck('id')->all())->toBe([$service2->id]);

    $this->assertDatabaseMissing('profession_service', [
        'profession_id' => $profession->id,
        'service_id' => $service1->id,
    ]);

    $this->assertDatabaseHas('profession_service', [
        'profession_id' => $profession->id,
        'service_id' => $service2->id,
    ]);

    $this->assertDatabaseMissing('profession_profession_document', [
        'profession_id' => $profession->id,
        'profession_document_id' => $doc1->id,
    ]);

    $this->assertDatabaseHas('profession_profession_document', [
        'profession_id' => $profession->id,
        'profession_document_id' => $doc2->id,
        'required' => true,
    ]);
});

test('admin can delete a profession', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $profession = Profession::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/admin/professions/{$profession->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('professions', ['id' => $profession->id]);
});

test('admin can delete a profession document', function () {
    $admin = User::factory()->admin()->create();
    $token = Auth::guard('admin')->login($admin);

    $doc = ProfessionDocument::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/admin/profession-documents/{$doc->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('profession_documents', ['id' => $doc->id]);
});

test('profession document checks if format is allowed', function () {
    $doc = ProfessionDocument::factory()->create([
        'formats' => ['pdf', 'png'],
    ]);

    expect($doc->isFormatAllowed('pdf'))->toBeTrue()
        ->and($doc->isFormatAllowed('PDF'))->toBeTrue()
        ->and($doc->isFormatAllowed('png'))->toBeTrue()
        ->and($doc->isFormatAllowed('docx'))->toBeFalse()
        ->and($doc->isFormatAllowed('exe'))->toBeFalse();
});

test('service model has inverse professions relationship', function () {
    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create(['service_category_id' => $category->id]);
    $profession = Profession::factory()->create();

    $service->professions()->attach($profession->id);

    expect($service->professions->count())->toBe(1)
        ->and($service->professions->first()->id)->toBe($profession->id);
});
