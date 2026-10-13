<?php

namespace Modules\Beneficiary\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Beneficiary\Models\Beneficiary;
use Yajra\DataTables\Facades\DataTables;

class BeneficiaryService
{
    /**
     * Get DataTables response for beneficiaries.
     */
    public function dataTable(array $filters = []): mixed
    {
        $query = Beneficiary::with('user');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($filters) {
                if (! empty($filters['search'])) {
                    $search = $filters['search'];
                    $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($uq) use ($search) {
                                $uq->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    });
                }
            })
            ->make(true);
    }

    /**
     * Get all beneficiaries for a user.
     */
    public function getForUser(int $userId): Collection
    {
        return Beneficiary::where('user_id', $userId)->get();
    }

    /**
     * Store a new beneficiary.
     */
    public function store(int $userId, array $data): Beneficiary
    {
        $data['user_id'] = $userId;

        return Beneficiary::create($data);
    }

    /**
     * Update an existing beneficiary.
     */
    public function update(Beneficiary $beneficiary, array $data): Beneficiary
    {
        $beneficiary->update($data);

        return $beneficiary;
    }

    /**
     * Delete a beneficiary.
     */
    public function delete(Beneficiary $beneficiary): bool
    {
        return $beneficiary->delete();
    }
}
