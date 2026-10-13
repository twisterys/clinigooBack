<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Professions\Models\Profession;
use Modules\Professions\Models\ProfessionDocument;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceCategory;
use Modules\User\Enums\DocumentVerificationStatus;
use Modules\User\Enums\ProfessionalHistoryAction;
use Modules\User\Enums\ProfessionalStatus;
use Modules\User\Models\ProfessionalDocument;
use Modules\User\Models\ProfessionalProfile;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake("public");
});

function createHealthcareSetup()
{
    $category = ServiceCategory::factory()->create(["name" => "Soins Infirmiers"]);

    $service1 = Service::factory()->create([
        "service_category_id" => $category->id,
        "name" => "Injection à domicile",
        "is_active" => true,
    ]);
    $service2 = Service::factory()->create([
        "service_category_id" => $category->id,
        "name" => "Pansement complexe",
        "is_active" => true,
    ]);
    $serviceUnrelated = Service::factory()->create([
        "service_category_id" => $category->id,
        "name" => "Séance de Kinésithérapie respiratoire",
        "is_active" => true,
    ]);

    $docCin = ProfessionDocument::factory()->create([
        "name" => "Carte Nationale d"Identité (CIN)",
        "formats" => ["pdf", "png", "jpg"],
    ]);
    $docDiploma = ProfessionDocument::factory()->create([
        "name" => "Diplôme d"État Infirmier",
        "formats" => ["pdf"],
    ]);
    $docCv = ProfessionDocument::factory()->create([
        "name" => "Curriculum Vitae",
        "formats" => ["pdf"],
    ]);

    $profession = Profession::factory()->create([
        "name" => "Infirmier / Infirmière",
        "is_active" => true,
    ]);

    $profession->services()->attach([$service1->id, $service2->id]);
    $profession->documents()->attach([
        $docCin->id => ["required" => true],
        $docDiploma->id => ["required" => true],
        $docCv->id => ["required" => false],
    ]);

    return compact("category", "service1", "service2", "serviceUnrelated", "docCin", "docDiploma", "docCv", "profession");
}

test("scenario 1: complete registration, document upload, submission and admin approval", function () {
    $setup = createHealthcareSetup();
    $admin = User::factory()->admin()->create();
    $adminToken = Auth::guard("admin")->login($admin);

    $registerRes = $this->postJson("/api/auth/register", [
        "email" => "infirmier.kamal@clinigoo.test",
        "password" => "Secr3tPassword!",
        "type" => "professional",
        "firstName" => "Kamal",
        "lastName" => "Idrissi",
        "sexe" => "M",
        "ID" => "CIN-AB123456",
        "profession_id" => $setup["profession"]->id,
        "services" => [$setup["service1"]->id, $setup["service2"]->id],
    ]);

    $registerRes->assertOk();
    $user = User::where("email", "infirmier.kamal@clinigoo.test")->first();
    $proToken = Auth::guard("api")->login($user);

    $profile = $user->professionalProfile;
    expect($profile->status)->toBe(ProfessionalStatus::Draft)
        ->and($profile->isValidated())->toBeFalse()
        ->and($profile->isEligible())->toBeFalse();

    $dossierRes = $this->withHeader("Authorization", "Bearer {$proToken}")
        ->getJson("/api/professional/dossier");

    $dossierRes->assertOk()
        ->assertJsonPath("status", "draft")
        ->assertJsonPath("profession.name", "Infirmier / Infirmière");

    $checklist = collect($dossierRes->json("document_checklist"));
    expect($checklist->firstWhere("name", "Carte Nationale d"Identité (CIN)")["is_required"])->toBeTrue()
        ->and($checklist->firstWhere("name", "Diplôme d"État Infirmier")["is_required"])->toBeTrue()
        ->and($checklist->firstWhere("name", "Curriculum Vitae")["is_required"])->toBeFalse();

    $failSubmit = $this->withHeader("Authorization", "Bearer {$proToken}")
        ->postJson("/api/professional/submit");
    $failSubmit->assertStatus(422)
        ->assertJsonValidationErrors(["documents"]);

    $fileCin = UploadedFile::fake()->create("cin.pdf", 500, "application/pdf");
    $uploadCinRes = $this->withHeader("Authorization", "Bearer {$proToken}")
        ->postJson("/api/professional/documents", [
            "profession_document_id" => $setup["docCin"]->id,
            "file" => $fileCin,
        ]);
    $uploadCinRes->assertStatus(201)
        ->assertJsonPath("document.status", "pending");

    $fileDiploma = UploadedFile::fake()->create("diploma.pdf", 800, "application/pdf");
    $uploadDipRes = $this->withHeader("Authorization", "Bearer {$proToken}")
        ->postJson("/api/professional/documents", [
            "profession_document_id" => $setup["docDiploma"]->id,
            "file" => $fileDiploma,
        ]);
    $uploadDipRes->assertStatus(201);

    $submitRes = $this->withHeader("Authorization", "Bearer {$proToken}")
        ->postJson("/api/professional/submit");

    $submitRes->assertOk()
        ->assertJsonPath("profile.status", "pending");

    $profile->refresh();
    expect($profile->status)->toBe(ProfessionalStatus::Pending)
        ->and($profile->isValidated())->toBeFalse()
        ->and($profile->isEligible())->toBeFalse()
        ->and($profile->decisionHistories()->count())->toBe(1)
        ->and($profile->decisionHistories()->first()->action)->toBe(ProfessionalHistoryAction::DossierSubmitted);

    $adminListRes = $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->getJson("/api/admin/professionals?status=pending");
    $adminListRes->assertOk()
        ->assertJsonFragment(["email" => "infirmier.kamal@clinigoo.test"]);

    $prematureValidationRes = $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->postJson("/api/admin/professionals/{$profile->id}/verify", [
            "decision" => "validate",
        ]);
    $prematureValidationRes->assertStatus(422)
        ->assertJsonValidationErrors(["dossier"]);

    $docs = $profile->documents()->get();
    foreach ($docs as $d) {
        $verifyDocRes = $this->withHeader("Authorization", "Bearer {$adminToken}")
            ->postJson("/api/admin/professionals/{$profile->id}/documents/{$d->id}/verify", [
                "decision" => "validate",
            ]);
        $verifyDocRes->assertOk()
            ->assertJsonPath("document.status", "validated");
    }

    $verifyDossierRes = $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->postJson("/api/admin/professionals/{$profile->id}/verify", [
            "decision" => "validate",
        ]);
    $verifyDossierRes->assertOk()
        ->assertJsonPath("profile.status", "validated");

    $profile->refresh();
    expect($profile->isValidated())->toBeTrue()
        ->and($profile->status)->toBe(ProfessionalStatus::Validated);

    expect($profile->is_available)->toBeFalse()
        ->and($profile->isEligible())->toBeFalse();

    $availRes = $this->withHeader("Authorization", "Bearer {$proToken}")
        ->putJson("/api/professional/availability", ["is_available" => true]);

    $availRes->assertOk()
        ->assertJsonPath("is_available", true)
        ->assertJsonPath("is_validated", true)
        ->assertJsonPath("is_eligible", true);

    $profile->refresh();
    expect($profile->isEligible())->toBeTrue();
});

test("scenario 2: document rejected with motive, replaced by pro, already approved document remains valid", function () {
    $setup = createHealthcareSetup();
    $admin = User::factory()->admin()->create();
    $adminToken = Auth::guard("admin")->login($admin);

    $user = User::factory()->professional()->create();
    $proToken = Auth::guard("api")->login($user);

    $profile = $user->professionalProfile()->create([
        "profession_id" => $setup["profession"]->id,
        "first_name" => "Aicha",
        "last_name" => "Alami",
        "status" => ProfessionalStatus::Pending,
        "submitted_at" => now(),
    ]);
    $profile->services()->attach([$setup["service1"]->id]);

    $cinDoc = ProfessionalDocument::factory()->validated()->create([
        "professional_profile_id" => $profile->id,
        "profession_document_id" => $setup["docCin"]->id,
        "original_name" => "cin_valide.pdf",
    ]);

    $diplomaDoc = ProfessionalDocument::factory()->pending()->create([
        "professional_profile_id" => $profile->id,
        "profession_document_id" => $setup["docDiploma"]->id,
        "original_name" => "diplome_illisible.pdf",
    ]);

    $rejectDocRes = $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->postJson("/api/admin/professionals/{$profile->id}/documents/{$diplomaDoc->id}/verify", [
            "decision" => "reject",
            "reason" => "Le document est flou et illisible. Veuillez fournir un scan net.",
        ]);

    $rejectDocRes->assertOk()
        ->assertJsonPath("document.status", "rejected")
        ->assertJsonPath("document.rejection_reason", "Le document est flou et illisible. Veuillez fournir un scan net.");

    $failReject = $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->postJson("/api/admin/professionals/{$profile->id}/documents/{$diplomaDoc->id}/verify", [
            "decision" => "reject",
            "reason" => "",
        ]);
    $failReject->assertStatus(422);

    $rejectDossierRes = $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->postJson("/api/admin/professionals/{$profile->id}/verify", [
            "decision" => "reject",
            "reason" => "Veuillez corriger le diplôme refusé avant validation.",
        ]);
    $rejectDossierRes->assertOk();

    $profile->refresh();
    expect($profile->status)->toBe(ProfessionalStatus::Rejected)
        ->and($profile->rejection_reason)->toBe("Veuillez corriger le diplôme refusé avant validation.");

    $dossierRes = $this->withHeader("Authorization", "Bearer {$proToken}")
        ->getJson("/api/professional/dossier");

    $dossierRes->assertOk()
        ->assertJsonPath("status", "rejected")
        ->assertJsonPath("rejection_reason", "Veuillez corriger le diplôme refusé avant validation.");

    $newFile = UploadedFile::fake()->create("diplome_net.pdf", 900, "application/pdf");
    $replaceRes = $this->withHeader("Authorization", "Bearer {$proToken}")
        ->postJson("/api/professional/documents", [
            "profession_document_id" => $setup["docDiploma"]->id,
            "file" => $newFile,
        ]);

    $replaceRes->assertStatus(201)
        ->assertJsonPath("document.status", "pending")
        ->assertJsonPath("document.rejection_reason", null);

    $cinDoc->refresh();
    expect($cinDoc->isValidated())->toBeTrue();

    $resubmitRes = $this->withHeader("Authorization", "Bearer {$proToken}")
        ->postJson("/api/professional/submit");
    $resubmitRes->assertOk()
        ->assertJsonPath("profile.status", "pending");

    $diplomaDoc->refresh();
    $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->postJson("/api/admin/professionals/{$profile->id}/documents/{$diplomaDoc->id}/verify", [
            "decision" => "validate",
        ])->assertOk();

    $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->postJson("/api/admin/professionals/{$profile->id}/verify", [
            "decision" => "validate",
        ])->assertOk();

    $profile->refresh();
    expect($profile->isValidated())->toBeTrue()
        ->and($cinDoc->fresh()->isValidated())->toBeTrue()
        ->and($diplomaDoc->fresh()->isValidated())->toBeTrue();
});

test("scenario 3: validated professional requests profession change, active info stays active until admin decision", function () {
    $setup = createHealthcareSetup();
    $admin = User::factory()->admin()->create();
    $adminToken = Auth::guard("admin")->login($admin);

    $serviceSageFemme = Service::factory()->create(["name" => "Suivi de grossesse"]);
    $professionSageFemme = Profession::factory()->create(["name" => "Sage-Femme", "is_active" => true]);
    $professionSageFemme->services()->attach($serviceSageFemme->id);

    $user = User::factory()->professional()->create();
    $proToken = Auth::guard("api")->login($user);

    $profile = $user->professionalProfile()->create([
        "profession_id" => $setup["profession"]->id,
        "first_name" => "Salma",
        "last_name" => "Berrada",
        "status" => ProfessionalStatus::Validated,
        "is_available" => true,
    ]);
    $profile->services()->attach([$setup["service1"]->id]);

    expect($profile->isValidated())->toBeTrue()
        ->and($profile->isEligible())->toBeTrue()
        ->and($profile->profession_id)->toBe($setup["profession"]->id);

    $requestChangeRes = $this->withHeader("Authorization", "Bearer {$proToken}")
        ->putJson("/api/professional/sensitive-info", [
            "profession_id" => $professionSageFemme->id,
            "services" => [$serviceSageFemme->id],
        ]);

    $requestChangeRes->assertOk()
        ->assertJsonPath("status", "pending_verification");

    $profile->refresh();
    expect($profile->status)->toBe(ProfessionalStatus::PendingChanges)
        ->and($profile->profession_id)->toBe($setup["profession"]->id)
        ->and($profile->services->pluck("id")->all())->toEqual([$setup["service1"]->id])
        ->and($profile->pending_data["profession_id"])->toBe($professionSageFemme->id);

    $rejectChangesRes = $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->postJson("/api/admin/professionals/{$profile->id}/review-changes", [
            "decision" => "reject",
            "reason" => "Diplôme de maïeutique manquant pour exercer en tant que sage-femme.",
        ]);

    $rejectChangesRes->assertOk();
    $profile->refresh();
    expect($profile->status)->toBe(ProfessionalStatus::Validated)
        ->and($profile->profession_id)->toBe($setup["profession"]->id)
        ->and($profile->pending_data)->toBeNull();

    $this->withHeader("Authorization", "Bearer {$proToken}")
        ->putJson("/api/professional/sensitive-info", [
            "profession_id" => $professionSageFemme->id,
            "services" => [$serviceSageFemme->id],
        ])->assertOk();

    $approveChangesRes = $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->postJson("/api/admin/professionals/{$profile->id}/review-changes", [
            "decision" => "approve",
        ]);

    $approveChangesRes->assertOk();
    $profile->refresh();

    expect($profile->status)->toBe(ProfessionalStatus::Validated)
        ->and($profile->profession_id)->toBe($professionSageFemme->id)
        ->and($profile->services->pluck("id")->all())->toEqual([$serviceSageFemme->id])
        ->and($profile->pending_data)->toBeNull();
});

test("scenario 4: validated professional requests adding an authorized service", function () {
    $setup = createHealthcareSetup();
    $admin = User::factory()->admin()->create();
    $adminToken = Auth::guard("admin")->login($admin);

    $user = User::factory()->professional()->create();
    $proToken = Auth::guard("api")->login($user);

    $profile = $user->professionalProfile()->create([
        "profession_id" => $setup["profession"]->id,
        "first_name" => "Tariq",
        "last_name" => "El Fassi",
        "status" => ProfessionalStatus::Validated,
    ]);
    $profile->services()->attach([$setup["service1"]->id]);

    $res = $this->withHeader("Authorization", "Bearer {$proToken}")
        ->putJson("/api/professional/sensitive-info", [
            "services" => [$setup["service1"]->id, $setup["service2"]->id],
        ]);

    $res->assertOk()
        ->assertJsonPath("status", "pending_verification");

    $profile->refresh();
    expect($profile->services->pluck("id")->all())->toEqual([$setup["service1"]->id])
        ->and($profile->status)->toBe(ProfessionalStatus::PendingChanges);

    $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->postJson("/api/admin/professionals/{$profile->id}/review-changes", [
            "decision" => "approve",
        ])->assertOk();

    $profile->refresh();
    expect($profile->status)->toBe(ProfessionalStatus::Validated)
        ->and($profile->services->pluck("id")->all())->toEqualCanonicalizing([$setup["service1"]->id, $setup["service2"]->id]);
});

test("scenario 5: direct administrative modification of professional services", function () {
    $setup = createHealthcareSetup();
    $admin = User::factory()->admin()->create();
    $adminToken = Auth::guard("admin")->login($admin);

    $user = User::factory()->professional()->create();

    $profile = $user->professionalProfile()->create([
        "profession_id" => $setup["profession"]->id,
        "first_name" => "Nadia",
        "last_name" => "Chraibi",
        "status" => ProfessionalStatus::Validated,
    ]);
    $profile->services()->attach([$setup["service1"]->id]);

    $updateRes = $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->putJson("/api/admin/professionals/{$profile->id}/services", [
            "services" => [$setup["service2"]->id],
        ]);

    $updateRes->assertOk();
    $profile->refresh();
    expect($profile->services->pluck("id")->all())->toEqual([$setup["service2"]->id]);

    $history = $profile->decisionHistories()->first();
    expect($history->action)->toBe(ProfessionalHistoryAction::ServicesUpdatedByAdmin)
        ->and($history->admin_id)->toBe($admin->id);

    $invalidServiceRes = $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->putJson("/api/admin/professionals/{$profile->id}/services", [
            "services" => [$setup["serviceUnrelated"]->id],
        ]);
    $invalidServiceRes->assertStatus(422)
        ->assertJsonValidationErrors(["services"]);
});

test("scenario 6: multiple successive rejections retain full decision history", function () {
    $setup = createHealthcareSetup();
    $admin = User::factory()->admin()->create();
    $adminToken = Auth::guard("admin")->login($admin);

    $user = User::factory()->professional()->create();
    $proToken = Auth::guard("api")->login($user);

    $profile = $user->professionalProfile()->create([
        "profession_id" => $setup["profession"]->id,
        "first_name" => "Hassan",
        "last_name" => "Bouzid",
        "status" => ProfessionalStatus::Pending,
    ]);
    $profile->services()->attach([$setup["service1"]->id]);

    $cinDoc = ProfessionalDocument::factory()->pending()->create([
        "professional_profile_id" => $profile->id,
        "profession_document_id" => $setup["docCin"]->id,
    ]);
    $dipDoc = ProfessionalDocument::factory()->pending()->create([
        "professional_profile_id" => $profile->id,
        "profession_document_id" => $setup["docDiploma"]->id,
    ]);

    $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->postJson("/api/admin/professionals/{$profile->id}/documents/{$cinDoc->id}/verify", [
            "decision" => "reject",
            "reason" => "CIN expirée",
        ])->assertOk();

    $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->postJson("/api/admin/professionals/{$profile->id}/verify", [
            "decision" => "reject",
            "reason" => "Veuillez renouveler votre CIN",
        ])->assertOk();

    $newCin = UploadedFile::fake()->create("cin_renouvelee.pdf", 500, "application/pdf");
    $this->withHeader("Authorization", "Bearer {$proToken}")
        ->postJson("/api/professional/documents", [
            "profession_document_id" => $setup["docCin"]->id,
            "file" => $newCin,
        ])->assertStatus(201);

    $this->withHeader("Authorization", "Bearer {$proToken}")
        ->postJson("/api/professional/submit")
        ->assertOk();

    $cinDoc->refresh();
    $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->postJson("/api/admin/professionals/{$profile->id}/documents/{$cinDoc->id}/verify", [
            "decision" => "validate",
        ])->assertOk();

    $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->postJson("/api/admin/professionals/{$profile->id}/documents/{$dipDoc->id}/verify", [
            "decision" => "reject",
            "reason" => "Copie non certifiée conforme",
        ])->assertOk();

    $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->postJson("/api/admin/professionals/{$profile->id}/verify", [
            "decision" => "reject",
            "reason" => "Diplôme non certifié",
        ])->assertOk();

    $newDip = UploadedFile::fake()->create("diplome_certifie.pdf", 600, "application/pdf");
    $this->withHeader("Authorization", "Bearer {$proToken}")
        ->postJson("/api/professional/documents", [
            "profession_document_id" => $setup["docDiploma"]->id,
            "file" => $newDip,
        ])->assertStatus(201);

    $this->withHeader("Authorization", "Bearer {$proToken}")
        ->postJson("/api/professional/submit")
        ->assertOk();

    $cinDoc->refresh();
    expect($cinDoc->isValidated())->toBeTrue();

    $historyRes = $this->withHeader("Authorization", "Bearer {$adminToken}")
        ->getJson("/api/admin/professionals/{$profile->id}/history");

    $historyRes->assertOk();
    $history = collect($historyRes->json("data"));

    expect($history->count())->toBeGreaterThanOrEqual(6)
        ->and($history->where("reason", "CIN expirée")->isNotEmpty())->toBeTrue()
        ->and($history->where("reason", "Copie non certifiée conforme")->isNotEmpty())->toBeTrue();
});

test("document format restrictions are strictly enforced upon upload", function () {
    $setup = createHealthcareSetup();
    $user = User::factory()->professional()->create();
    $proToken = Auth::guard("api")->login($user);

    $user->professionalProfile()->create([
        "first_name" => "Docteur",
        "last_name" => "Test",
        "profession_id" => $setup["profession"]->id,
    ]);

    $exeFile = UploadedFile::fake()->create("malicious.exe", 100);
    $res = $this->withHeader("Authorization", "Bearer {$proToken}")
        ->postJson("/api/professional/documents", [
            "profession_document_id" => $setup["docDiploma"]->id,
            "file" => $exeFile,
        ]);

    $res->assertStatus(422)
        ->assertJsonValidationErrors(["file"]);
});

test("documents are personal and inaccessible by other unauthorized users", function () {
    $setup = createHealthcareSetup();

    $user1 = User::factory()->professional()->create();
    $token1 = Auth::guard("api")->login($user1);
    $profile1 = $user1->professionalProfile()->create([
        "first_name" => "User1",
        "last_name" => "Pro",
        "profession_id" => $setup["profession"]->id,
    ]);

    $doc = ProfessionalDocument::factory()->create([
        "professional_profile_id" => $profile1->id,
        "profession_document_id" => $setup["docCin"]->id,
    ]);

    $user2 = User::factory()->professional()->create();
    $token2 = Auth::guard("api")->login($user2);
    $user2->professionalProfile()->create([
        "first_name" => "User2",
        "last_name" => "Pro",
        "profession_id" => $setup["profession"]->id,
    ]);

    $res = $this->withHeader("Authorization", "Bearer {$token2}")
        ->getJson("/api/professional/documents/{$doc->id}/download");
    $res->assertStatus(404);
});

test("public web registration endpoint creates user, profile, uploads documents, and returns frontend structure", function () {
    $setup = createHealthcareSetup();

    $fileCin = UploadedFile::fake()->create("cin.pdf", 300, "application/pdf");

    $response = $this->post("/api/public/professionals", [
        "personal" => [
            "full_name" => "Dr. Ahmed Benjelloun",
            "sex" => "M",
            "cin" => "AB998877",
            "phone" => "+212 661 12 34 56",
            "email" => "ahmed.benjelloun@clinigoo.ma",
            "city" => "Casablanca",
        ],
        "profession_id" => $setup["profession"]->id,
        "selected_service_ids" => [$setup["service1"]->id],
        "documents" => [
            $setup["docCin"]->id => $fileCin,
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            "id",
            "status",
            "full_name",
            "cin",
            "email",
            "profession" => ["id", "name"],
            "selected_services",
            "submitted_documents",
            "access_token",
        ]);

    $proId = $response->json("id");
    expect($response->json("status"))->toBe("pending")
        ->and($response->json("full_name"))->toBe("Dr. Ahmed Benjelloun")
        ->and($response->json("cin"))->toBe("AB998877")
        ->and($response->json("email"))->toBe("ahmed.benjelloun@clinigoo.ma");

    $statusRes = $this->getJson("/api/public/professionals/{$proId}");
    $statusRes->assertOk()
        ->assertJsonPath("status", "pending")
        ->assertJsonPath("full_name", "Dr. Ahmed Benjelloun")
        ->assertJsonPath("cin", "AB998877")
        ->assertJsonPath("profession.name", "Infirmier / Infirmière");
});
