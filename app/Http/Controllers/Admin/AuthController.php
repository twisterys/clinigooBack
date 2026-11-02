<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PatientProfile;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Get a JWT via given credentials.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! $token = Auth::guard('admin')->attempt($credentials)) {
            return response()->json([
                'error' => 'Identifiants administrateur invalides ou accès non autorisé.',
            ], 401);
        }

        return $this->respondWithToken((string) $token);
    }

    /**
     * Get the authenticated User with their profile.
     */
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('admin')->user();

        return response()->json($user);
    }

    /**
     * Get the authenticated user's specific profile.
     */
    public function profile(): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('admin')->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'type' => $user->type?->value,
            'profile' => $user->profile,
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('admin')->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->isPatient()) {
            $validated = $request->validate([
                'first_name' => ['nullable', 'string', 'max:255'],
                'firstName' => ['nullable', 'string', 'max:255'],
                'last_name' => ['nullable', 'string', 'max:255'],
                'lastName' => ['nullable', 'string', 'max:255'],
                'birthday' => ['nullable', 'date'],
                'sexe' => ['nullable', 'string', 'max:50'],
                'phone' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255'],
            ]);

            $firstName = $validated['first_name'] ?? $validated['firstName'] ?? null;
            $lastName = $validated['last_name'] ?? $validated['lastName'] ?? null;

            $profileData = array_filter([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'birthday' => $validated['birthday'] ?? null,
                'sexe' => $validated['sexe'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
            ], fn ($val) => ! is_null($val));

            /** @var PatientProfile $profile */
            $profile = $user->patientProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );

            return response()->json([
                'message' => 'Profil patient mis à jour avec succès.',
                'profile' => $profile,
            ]);
        }

        if ($user->isProfessional()) {
            $validated = $request->validate([
                'first_name' => ['nullable', 'string', 'max:255'],
                'firstName' => ['nullable', 'string', 'max:255'],
                'last_name' => ['nullable', 'string', 'max:255'],
                'lastName' => ['nullable', 'string', 'max:255'],
                'sexe' => ['nullable', 'string', 'max:50'],
                'id_number' => ['nullable', 'string', 'max:100'],
                'ID' => ['nullable', 'string', 'max:100'],
                'professional_id' => ['nullable', 'string', 'max:100'],
            ]);

            $firstName = $validated['first_name'] ?? $validated['firstName'] ?? null;
            $lastName = $validated['last_name'] ?? $validated['lastName'] ?? null;
            $idNumber = $validated['id_number'] ?? $validated['ID'] ?? $validated['professional_id'] ?? null;

            $profileData = array_filter([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'sexe' => $validated['sexe'] ?? null,
                'id_number' => $idNumber,
            ], fn ($val) => ! is_null($val));

            /** @var ProfessionalProfile $profile */
            $profile = $user->professionalProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );

            return response()->json([
                'message' => 'Profil professionnel mis à jour avec succès.',
                'profile' => $profile,
            ]);
        }

        return response()->json([
            'message' => 'Aucun profil spécifique pour ce type d\'utilisateur.',
        ]);
    }

    /**
     * Update authenticated user's password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        /** @var User|null $user */
        $user = Auth::guard('admin')->user();

        if (! $user || ! Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Mot de passe actuel incorrect.'], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(): JsonResponse
    {
        Auth::guard('admin')->logout();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    /**
     * Refresh a token.
     */
    public function refresh(): JsonResponse
    {
        try {
            return $this->respondWithToken((string) Auth::guard('admin')->refresh());
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Session expirée ou token invalide.',
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'guard' => 'admin',
            'expires_in' => Auth::guard('admin')->factory()->getTTL() * 60,
        ]);
    }
}
