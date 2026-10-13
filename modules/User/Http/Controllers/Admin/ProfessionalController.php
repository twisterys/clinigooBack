<?php

namespace Modules\User\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
     * Get the authenticated admin user.
     */
    protected function getAdminUser(): User
    {
        /** @var User $admin */
        $admin = Auth::guard('admin')->user();

        return $admin;
    }

    /**
     * List all professionals with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'profession_id',
            'is_available',
            'is_validated',
            'search',
            'per_page',
        ]);

        $paginator = $this->profileService->getProfessionalsList($filters);

        return response()->json($paginator);
    }

    /**
     * View full dossier of a professional.
     */
    public function show(int $id): JsonResponse
    {
        $profile = ProfessionalProfile::findOrFail($id);

        return response()->json($this->profileService->getDossier($profile));
    }

    /**
     * Verify an individual document (validate or reject).
     */
    public function verifyDocument(Request $request, int $profileId, int $documentId): JsonResponse
    {
        $profile = ProfessionalProfile::findOrFail($profileId);
        /** @var ProfessionalDocument $document */
        $document = $profile->documents()->findOrFail($documentId);

        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:validate,reject'],
            'reason' => ['nullable', 'string', 'required_if:decision,reject'],
        ]);

        $updatedDoc = $this->profileService->adminVerifyDocument(
            $document,
            $validated['decision'],
            $validated['reason'] ?? null,
            $this->getAdminUser()
        );

        return response()->json([
            'message' => $validated['decision'] === 'validate'
                ? 'Document validé avec succès.'
                : 'Document rejeté avec motif.',
            'document' => [
                'id' => $updatedDoc->id,
                'status' => $updatedDoc->status->value,
                'rejection_reason' => $updatedDoc->rejection_reason,
                'verified_at' => $updatedDoc->verified_at?->toIso8601String(),
            ],
            'profile' => $this->profileService->getDossier($profile->fresh()),
        ]);
    }

    /**
     * Validate or reject the entire professional dossier.
     */
    public function verifyDossier(Request $request, int $profileId): JsonResponse
    {
        $profile = ProfessionalProfile::findOrFail($profileId);

        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:validate,reject'],
            'reason' => ['nullable', 'string', 'required_if:decision,reject'],
        ]);

        $result = $this->profileService->adminVerifyDossier(
            $profile,
            $validated['decision'],
            $validated['reason'] ?? null,
            $this->getAdminUser()
        );

        return response()->json($result);
    }

    /**
     * Review sensitive changes requested by an already validated professional.
     */
    public function reviewSensitiveChanges(Request $request, int $profileId): JsonResponse
    {
        $profile = ProfessionalProfile::findOrFail($profileId);

        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:approve,reject'],
            'reason' => ['nullable', 'string', 'required_if:decision,reject'],
        ]);

        $result = $this->profileService->adminReviewSensitiveChanges(
            $profile,
            $validated['decision'],
            $validated['reason'] ?? null,
            $this->getAdminUser()
        );

        return response()->json($result);
    }

    /**
     * Directly update services assigned to a professional.
     */
    public function updateServices(Request $request, int $profileId): JsonResponse
    {
        $profile = ProfessionalProfile::findOrFail($profileId);

        $validated = $request->validate([
            'services' => ['required', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
        ]);

        $result = $this->profileService->adminUpdateServices(
            $profile,
            $validated['services'],
            $this->getAdminUser()
        );

        return response()->json($result);
    }

    /**
     * Get history of decisions and verification events for a professional.
     */
    public function history(int $profileId): JsonResponse
    {
        $profile = ProfessionalProfile::with(['decisionHistories.admin'])->findOrFail($profileId);

        $history = $profile->decisionHistories->map(fn ($h) => [
            'id' => $h->id,
            'action' => $h->action->value,
            'action_label' => $h->action->label(),
            'status_before' => $h->status_before,
            'status_after' => $h->status_after,
            'reason' => $h->reason,
            'metadata' => $h->metadata,
            'admin_id' => $h->admin_id,
            'admin_name' => $h->admin?->name,
            'created_at' => $h->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $history,
        ]);
    }

    /**
     * Admin download document.
     */
    public function downloadDocument(int $profileId, int $documentId): BinaryFileResponse
    {
        $profile = ProfessionalProfile::findOrFail($profileId);
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
