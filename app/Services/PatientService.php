<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\Beneficiary;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class PatientService
{
    /**
     * Build base query with filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<User>
     */
    protected function buildFilteredQuery(array $filters = []): Builder
    {
        $query = User::patients()->with(['patientProfile', 'beneficiaries', 'addresses']);

        $search = $filters['search'] ?? null;
        if (is_array($search)) {
            $search = $search['value'] ?? null;
        }

        if (! empty($search)) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('patientProfile', function (Builder $pq) use ($search) {
                        $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('beneficiaries', function (Builder $bq) use ($search) {
                        $bq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('cin_amo_cnss', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['name'])) {
            $name = $filters['name'];
            $query->where(function (Builder $q) use ($name) {
                $q->where('name', 'like', "%{$name}%")
                    ->orWhereHas('patientProfile', function (Builder $pq) use ($name) {
                        $pq->where('first_name', 'like', "%{$name}%")
                            ->orWhere('last_name', 'like', "%{$name}%");
                    });
            });
        }

        if (! empty($filters['email'])) {
            $query->where('email', 'like', "%{$filters['email']}%");
        }

        if (! empty($filters['phone'])) {
            $phone = $filters['phone'];
            $query->whereHas('patientProfile', function (Builder $pq) use ($phone) {
                $pq->where('phone', 'like', "%{$phone}%");
            });
        }

        if (! empty($filters['gender']) || ! empty($filters['sexe'])) {
            $sexe = $filters['gender'] ?? $filters['sexe'];
            $query->whereHas('patientProfile', function (Builder $pq) use ($sexe) {
                $pq->where('sexe', $sexe);
            });
        }

        if (! empty($filters['created_from']) || ! empty($filters['date_from'])) {
            $from = $filters['created_from'] ?? $filters['date_from'];
            $query->whereDate('created_at', '>=', $from);
        }

        if (! empty($filters['created_to']) || ! empty($filters['date_to'])) {
            $to = $filters['created_to'] ?? $filters['date_to'];
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    /**
     * Get DataTables response for patients.
     *
     * @param  array<string, mixed>  $filters
     */
    public function dataTable(array $filters = []): mixed
    {
        $query = $this->buildFilteredQuery($filters);

        return DataTables::of($query)
            ->addColumn('first_name', fn (User $user) => $user->patientProfile?->first_name)
            ->addColumn('last_name', fn (User $user) => $user->patientProfile?->last_name)
            ->addColumn('phone', fn (User $user) => $user->patientProfile?->phone)
            ->addColumn('birthday', fn (User $user) => $user->patientProfile?->birthday?->format('Y-m-d'))
            ->addColumn('sexe', fn (User $user) => $user->patientProfile?->sexe)
            ->addColumn('beneficiaries_count', fn (User $user) => $user->beneficiaries->count())
            ->make(true);
    }

    /**
     * Get paginated patients list.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = $this->buildFilteredQuery($filters);
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Store a new patient and automatically create a beneficiary with the same name.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $firstName = $data['first_name'] ?? $data['firstName'] ?? null;
            $lastName = $data['last_name'] ?? $data['lastName'] ?? null;

            if (empty($firstName) && empty($lastName)) {
                $name = $data['name'] ?? '';
                $parts = explode(' ', trim($name), 2);
                $firstName = $parts[0] ?: 'Patient';
                $lastName = $parts[1] ?? '';
            }

            $lastName = $lastName ?? '';
            $fullName = $data['name'] ?? trim("{$firstName} {$lastName}");
            if (empty($fullName)) {
                $fullName = $firstName;
            }

            $password = $data['password'] ?? Str::random(16);

            /** @var User $user */
            $user = User::create([
                'name' => $fullName,
                'email' => $data['email'],
                'type' => UserType::Patient,
                'password' => Hash::make($password),
            ]);

            $birthday = $data['birthday'] ?? $data['date_of_birth'] ?? null;
            $sexe = $data['sexe'] ?? $data['gender'] ?? null;
            $phone = $data['phone'] ?? null;

            $user->patientProfile()->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'birthday' => $birthday,
                'sexe' => $sexe,
                'phone' => $phone,
                'email' => $data['profile_email'] ?? $data['email'],
            ]);

            $conditions = $data['conditions'] ?? null;
            if (is_array($conditions)) {
                $conditions = json_encode($conditions);
            }

            // Automatically create a beneficiary for the user with the same name
            $user->beneficiaries()->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'relationship' => $data['relationship'] ?? 'self',
                'gender' => $sexe,
                'date_of_birth' => $birthday,
                'phone' => $phone,
                'medical_notes' => $data['medical_notes'] ?? null,
                'cin_amo_cnss' => $data['cin_amo_cnss'] ?? null,
                'conditions' => $conditions,
                'is_default' => true,
            ]);

            return $user->load(['patientProfile', 'beneficiaries', 'addresses']);
        });
    }

    /**
     * Find a patient by user ID or patient profile ID.
     */
    public function find(User|int|string $patient): User
    {
        if ($patient instanceof User) {
            return $patient->loadMissing(['patientProfile', 'beneficiaries', 'addresses', 'careRequests.service']);
        }

        $user = User::patients()
            ->with(['patientProfile', 'beneficiaries', 'addresses', 'careRequests.service'])
            ->find($patient);

        if (! $user) {
            $user = User::patients()
                ->whereHas('patientProfile', fn (Builder $q) => $q->where('id', $patient))
                ->with(['patientProfile', 'beneficiaries', 'addresses', 'careRequests.service'])
                ->firstOrFail();
        }

        return $user;
    }

    /**
     * Update an existing patient.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $patient, array $data): User
    {
        return DB::transaction(function () use ($patient, $data) {
            $firstName = $data['first_name'] ?? $data['firstName'] ?? $patient->patientProfile?->first_name;
            $lastName = $data['last_name'] ?? $data['lastName'] ?? $patient->patientProfile?->last_name ?? '';

            $userUpdates = [];

            if (isset($data['name'])) {
                $userUpdates['name'] = $data['name'];
            } elseif (isset($data['first_name']) || isset($data['last_name']) || isset($data['firstName']) || isset($data['lastName'])) {
                $userUpdates['name'] = trim("{$firstName} {$lastName}");
            }

            if (! empty($data['email'])) {
                $userUpdates['email'] = $data['email'];
            }

            if (! empty($data['password'])) {
                $userUpdates['password'] = Hash::make($data['password']);
            }

            if (! empty($userUpdates)) {
                $patient->update($userUpdates);
            }

            $profileUpdates = array_filter([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'birthday' => $data['birthday'] ?? $data['date_of_birth'] ?? null,
                'sexe' => $data['sexe'] ?? $data['gender'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['profile_email'] ?? null,
            ], fn ($val) => ! is_null($val));

            /** @var PatientProfile $profile */
            $patient->patientProfile()->updateOrCreate(
                ['user_id' => $patient->id],
                $profileUpdates
            );

            // If the user's primary/self beneficiary exists, keep name and phone synced
            /** @var Beneficiary|null $defaultBeneficiary */
            $defaultBeneficiary = $patient->beneficiaries()->where('is_default', true)->first();
            if ($defaultBeneficiary && ($firstName || $lastName)) {
                $defaultBeneficiary->update(array_filter([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $data['phone'] ?? null,
                ], fn ($val) => ! is_null($val)));
            }

            return $patient->fresh(['patientProfile', 'beneficiaries', 'addresses']);
        });
    }

    /**
     * Delete a patient and their related records.
     */
    public function delete(User $patient): bool
    {
        return DB::transaction(function () use ($patient) {
            // Delete care request documents and care requests
            $patient->careRequests()->each(function ($careRequest) {
                if (method_exists($careRequest, 'documents')) {
                    $careRequest->documents()->delete();
                }
                $careRequest->delete();
            });

            $patient->beneficiaries()->delete();
            $patient->addresses()->delete();
            $patient->patientProfile()?->delete();

            return (bool) $patient->delete();
        });
    }
}
