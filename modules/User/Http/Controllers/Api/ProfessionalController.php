<?php

namespace Modules\User\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Professions\Models\Profession;
use Modules\User\Enums\DocumentVerificationStatus;
use Modules\User\Enums\ProfessionalHistoryAction;
use Modules\User\Enums\ProfessionalStatus;
use Modules\User\Enums\UserType;
use Modules\User\Models\ProfessionalDocument;
use Modules\User\Models\ProfessionalProfile;
use Modules\User\Models\User;
use Modules\User\Services\ProfessionalProfileService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfessionalController extends Controller
{
    public function __construct(
        protected ProfessionalProfileService $profileService
    ) {}

    /**
     * Get the authenticated professional's profile or throw 403.
     */
    protected function getAuthenticatedProfile(): ProfessionalProfile
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();

        if (! $user || ! $user->isProfessional()) {
            abort(403, 'Accès réservé aux professionnels de santé.');
        }

        /** @var ProfessionalProfile $profile */
        $profile = $user->professionalProfile()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'first_name' => $user->name,
            'last_name' => '',
        ]);

        return $profile;
    }

    /**
     * Public registration endpoint used by the web frontend.
     */
    public function publicRegister(Request $request): JsonResponse
    {
        $personal = $request->input('personal', []);

        $email = $personal['email'] ?? $request->input('email');
        $fullName = $personal['full_name'] ?? $request->input('name') ?? $request->input('full_name') ?? '';
        $sex = $personal['sex'] ?? $request->input('sexe') ?? $request->input('sex') ?? 'M';
        $cin = $personal['cin'] ?? $request->input('id_number') ?? $request->input('cin') ?? '';
        $phone = $personal['phone'] ?? $request->input('phone') ?? '';
        $professionId = (int) ($request->input('profession_id'));
        $serviceIds = $request->input('selected_service_ids') ?? $request->input('services', []);
        $password = $request->input('password', Str::random(16));

        $request->validate([
            'personal.email' => ['sometimes', 'email'],
            'email' => ['sometimes', 'email'],
            'profession_id' => ['required', 'integer', 'exists:professions,id'],
        ]);

        if (empty($email)) {
            return response()->json([
                'message' => 'L\'adresse email est obligatoire.',
                'errors' => ['personal.email' => ['L\'adresse email est obligatoire.']],
            ], 422);
        }

        $parts = explode(' ', trim($fullName), 2);
        $firstName = $parts[0] ?: $fullName;
        $lastName = $parts[1] ?? '';

        /** @var User $user */
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $fullName ?: $email,
                'password' => Hash::make($password),
                'type' => UserType::Professional,
            ]
        );

        if (! $user->isProfessional()) {
            $user->update(['type' => UserType::Professional]);
        }

        /** @var ProfessionalProfile $profile */
        $profile = $user->professionalProfile()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'sexe' => $sex,
                'id_number' => $cin,
                'phone' => $phone,
                'profession_id' => $professionId,
                'status' => ProfessionalStatus::Pending,
                'submitted_at' => now(),
            ]
        );

        $profile->update([
            'first_name' => $firstName ?: $profile->first_name,
            'last_name' => $lastName ?: $profile->last_name,
            'sexe' => $sex ?: $profile->sexe,
            'id_number' => $cin ?: $profile->id_number,
            'phone' => $phone ?: $profile->phone,
            'profession_id' => $professionId,
            'status' => ProfessionalStatus::Pending,
            'submitted_at' => now(),
        ]);

        // Sync services if provided
        if (! empty($serviceIds) && is_array($serviceIds)) {
            $this->profileService->syncProposedServices($profile, $serviceIds);
        }

        // Upload any attached documents
        $documents = $request->file('documents', []);
        if (is_array($documents)) {
            foreach ($documents as $docId => $file) {
                if ($file instanceof UploadedFile) {
                    $this->profileService->uploadDocument($profile, (int) $docId, $file);
                }
            }
        }

        $profile->recordHistory(
            ProfessionalHistoryAction::DossierSubmitted,
            'Dossier professionnel soumis lors de l\'inscription web.'
        );

        $token = Auth::guard('api')->login($user);

        $profile->load(['profession', 'services', 'documents.professionDocument']);

        return response()->json([
            'id' => $profile->id,
            'status' => 'pending',
            'full_name' => $profile->full_name,
            'cin' => $profile->id_number,
            'phone' => $profile->phone,
            'email' => $user->email,
            'profession' => $profile->profession ? [
                'id' => $profile->profession->id,
                'name' => $profile->profession->name,
            ] : null,
            'selected_services' => $profile->services->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'description' => $s->description ?? '',
                'icon' => $s->icon ?? '',
            ])->values()->all(),
            'submitted_documents' => $profile->documents->map(fn ($d) => [
                'id' => $d->id,
                'document_name' => $d->professionDocument?->name ?? 'Document',
                'status' => $d->status === DocumentVerificationStatus::Validated ? 'approved' : $d->status->value,
                'rejection_reason' => $d->rejection_reason,
                'url' => $d->url,
            ])->values()->all(),
            'access_token' => (string) $token,
            'token_type' => 'bearer',
            'message' => 'Inscription effectuée avec succès.',
        ], 201);
    }

    /**
     * Public endpoint to view a professional registration status by ID.
     */
    public function publicShow(int|string $id): JsonResponse
    {
        /** @var ProfessionalProfile $profile */
        $profile = ProfessionalProfile::with([
            'user',
            'profession',
            'services',
            'documents.professionDocument',
        ])->findOrFail($id);

        $frontendStatus = match ($profile->status) {
            ProfessionalStatus::Validated => 'approved',
            ProfessionalStatus::PendingChanges => 'changes_pending',
            default => $profile->status->value,
        };

        return response()->json([
            'id' => $profile->id,
            'status' => $frontendStatus,
            'rejection_reason' => $profile->rejection_reason,
            'full_name' => $profile->full_name,
            'cin' => $profile->id_number,
            'phone' => $profile->phone,
            'email' => $profile->user?->email,
            'created_at' => $profile->created_at?->format('d/m/Y'),
            'updated_at' => $profile->updated_at?->format('d/m/Y'),
            'profession' => $profile->profession ? [
                'id' => $profile->profession->id,
                'name' => $profile->profession->name,
            ] : null,
            'selected_services' => $profile->services->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'description' => $s->description ?? '',
                'icon' => $s->icon ?? '',
            ])->values()->all(),
            'submitted_documents' => $profile->documents->map(fn ($d) => [
                'id' => $d->id,
                'document_name' => $d->professionDocument?->name ?? 'Document',
                'status' => $d->status === DocumentVerificationStatus::Validated ? 'approved' : $d->status->value,
                'rejection_reason' => $d->rejection_reason,
                'url' => $d->url,
            ])->values()->all(),
            'pending_changes' => $profile->pending_data,
        ]);
    }

    /**
     * Get full dossier and lifecycle status of the authenticated professional.
     */
    public function dossier(): JsonResponse
    {
        $profile = $this->getAuthenticatedProfile();

        return response()->json($this->profileService->getDossier($profile));
    }

    /**
     * Update non-sensitive profile information (bio, avatar, phone).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $profile = $this->getAuthenticatedProfile();

        $validated = $request->validate([
            'bio' => ['nullable', 'string', 'max:2000'],
            'avatar' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_available' => ['nullable', 'boolean'],
        ]);

        $updated = $this->profileService->updateNonSensitiveProfile($profile, $validated);

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'profile' => $this->profileService->getDossier($updated),
        ]);
    }

    /**
     * Update sensitive information (identity, profession, services).
     */
    public function updateSensitiveInfo(Request $request): JsonResponse
    {
        $profile = $this->getAuthenticatedProfile();

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'firstName' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'lastName' => ['nullable', 'string', 'max:255'],
            'sexe' => ['nullable', 'string', 'in:M,F,Autre'],
            'id_number' => ['nullable', 'string', 'max:100'],
            'ID' => ['nullable', 'string', 'max:100'],
            'profession_id' => ['nullable', 'integer', 'exists:professions,id'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
        ]);

        $result = $this->profileService->updateSensitiveProfile($profile, $validated);

        return response()->json($result);
    }

    /**
     * Toggle availability for missions.
     */
    public function updateAvailability(Request $request): JsonResponse
    {
        $profile = $this->getAuthenticatedProfile();

        $validated = $request->validate([
            'is_available' => ['required', 'boolean'],
        ]);

        $this->profileService->updateNonSensitiveProfile($profile, [
            'is_available' => $validated['is_available'],
        ]);

        return response()->json([
            'message' => 'Disponibilité mise à jour.',
            'is_available' => (bool) $profile->is_available,
            'is_validated' => $profile->isValidated(),
            'is_eligible' => $profile->isEligible(),
        ]);
    }

    /**
     * Upload or replace a document.
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $profile = $this->getAuthenticatedProfile();

        $validated = $request->validate([
            'profession_document_id' => ['required', 'integer', 'exists:profession_documents,id'],
            'file' => ['required', 'file', 'max:10240'], // 10MB max
        ]);

        $document = $this->profileService->uploadDocument(
            $profile,
            (int) $validated['profession_document_id'],
            $request->file('file')
        );

        return response()->json([
            'message' => 'Document téléversé avec succès. Il est en attente de vérification administrative.',
            'document' => [
                'id' => $document->id,
                'profession_document_id' => $document->profession_document_id,
                'original_name' => $document->original_name,
                'url' => $document->url,
                'status' => $document->status->value,
                'rejection_reason' => $document->rejection_reason,
            ],
        ], 201);
    }

    /**
     * Submit completed dossier for verification.
     */
    public function submit(): JsonResponse
    {
        $profile = $this->getAuthenticatedProfile();

        $result = $this->profileService->submitDossier($profile);

        return response()->json($result);
    }

    /**
     * Securely download or view uploaded document.
     */
    public function downloadDocument(int $documentId): BinaryFileResponse
    {
        $profile = $this->getAuthenticatedProfile();

        /** @var ProfessionalDocument $document */
        $document = $profile->documents()->findOrFail($documentId);

        if (! Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Fichier introuvable.');
        }

        $path = Storage::disk('public')->path($document->file_path);

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="'.$document->original_name.'"',
        ]);
    }
}
