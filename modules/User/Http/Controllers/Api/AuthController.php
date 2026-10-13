<?php

namespace Modules\User\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Modules\User\Enums\UserType;
use Modules\User\Models\PatientProfile;
use Modules\User\Models\ProfessionalProfile;
use Modules\User\Models\User;

class AuthController extends Controller
{
    /**
     * Register a new user and get a JWT.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'type' => ['nullable', 'string', Rule::in(UserType::values())],
            'name' => ['nullable', 'string'],

            // Common Profile fields (snake_case and camelCase)
            'first_name' => ['nullable', 'string'],
            'firstName' => ['nullable', 'string'],
            'last_name' => ['nullable', 'string'],
            'lastName' => ['nullable', 'string'],
            'sexe' => ['nullable', 'string'],

            // Patient Profile fields
            'birthday' => ['nullable', 'date'],
            'phone' => ['nullable', 'string'],
            'profile_email' => ['nullable', 'email'],

            // Professional Profile fields
            'id_number' => ['nullable', 'string'],
            'ID' => ['nullable', 'string'],
            'professional_id' => ['nullable', 'string'],
            'profession_id' => ['nullable', 'integer', 'exists:professions,id'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
        ]);

        $firstName = $validated['first_name'] ?? $validated['firstName'] ?? null;
        $lastName = $validated['last_name'] ?? $validated['lastName'] ?? null;

        $name = $validated['name'] ?? trim(($firstName ?? '').' '.($lastName ?? ''));
        if (empty($name)) {
            $name = $validated['email'];
        }

        $type = isset($validated['type']) ? UserType::from($validated['type']) : UserType::Patient;

        $user = User::create([
            'name' => $name,
            'email' => $validated['email'],
            'type' => $type,
            'password' => Hash::make($validated['password']),
        ]);

        // Create corresponding profile if details provided or default
        if ($user->isPatient()) {
            $user->patientProfile()->create([
                'first_name' => $firstName ?? $name,
                'last_name' => $lastName ?? '',
                'birthday' => $validated['birthday'] ?? null,
                'sexe' => $validated['sexe'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['profile_email'] ?? $validated['email'],
            ]);
        } elseif ($user->isProfessional()) {
            $proId = $validated['id_number'] ?? $validated['ID'] ?? $validated['professional_id'] ?? null;
            /** @var ProfessionalProfile $proProfile */
            $proProfile = $user->professionalProfile()->create([
                'first_name' => $firstName ?? $name,
                'last_name' => $lastName ?? '',
                'sexe' => $validated['sexe'] ?? null,
                'id_number' => $proId,
                'profession_id' => $validated['profession_id'] ?? null,
            ]);

            if (isset($validated['services']) && is_array($validated['services'])) {
                $proProfile->services()->sync($validated['services']);
            }
        }

        $token = Auth::guard('api')->login($user);

        return $this->respondWithToken((string) $token);
    }

    /**
     * Get a JWT via given credentials.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken((string) $token);
    }

    /**
     * Get the authenticated User with their profile.
     */
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();

        if ($user) {
            $user->loadMissing(['patientProfile', 'professionalProfile.profession', 'professionalProfile.services']);
        }

        return response()->json($user);
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    /**
     * Refresh a token.
     */
    public function refresh(): JsonResponse
    {
        /** @var string $token */
        $token = Auth::guard('api')->refresh();

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated user's specific profile.
     */
    public function profile(): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();

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
        $user = Auth::guard('api')->user();

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
                'bio' => ['nullable', 'string', 'max:2000'],
                'avatar' => ['nullable', 'string', 'max:500'],
                'phone' => ['nullable', 'string', 'max:50'],
                'is_available' => ['nullable', 'boolean'],
            ]);

            $firstName = $validated['first_name'] ?? $validated['firstName'] ?? null;
            $lastName = $validated['last_name'] ?? $validated['lastName'] ?? null;
            $idNumber = $validated['id_number'] ?? $validated['ID'] ?? $validated['professional_id'] ?? null;

            $profileData = array_filter([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'sexe' => $validated['sexe'] ?? null,
                'id_number' => $idNumber,
                'bio' => $validated['bio'] ?? null,
                'avatar' => $validated['avatar'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'is_available' => $validated['is_available'] ?? null,
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
        $user = Auth::guard('api')->user();

        if (! $user || ! Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Mot de passe actuel incorrect.'], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }

    /**
     * Update authenticated user preferences (placeholder for client/frontend config).
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Préférences mises à jour.',
        ]);
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ]);
    }
}
