<?php

namespace Modules\Address\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Address\Models\Address;
use Yajra\DataTables\Facades\DataTables;

class AddressService
{
    /**
     * Get DataTables response for addresses.
     */
    public function dataTable(array $filters = []): mixed
    {
        $query = Address::with('user');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($filters) {
                if (! empty($filters['search'])) {
                    $search = $filters['search'];
                    $query->where(function ($q) use ($search) {
                        $q->where('address_line', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%")
                            ->orWhere('label', 'like', "%{$search}%")
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
     * Get all addresses for a user.
     */
    public function getForUser(int $userId): Collection
    {
        return Address::where('user_id', $userId)->get();
    }

    /**
     * Store a new address.
     */
    public function store(int $userId, array $data): Address
    {
        return DB::transaction(function () use ($userId, $data) {
            if (! empty($data['is_default'])) {
                $this->resetDefaultStatus($userId);
            }

            $data['user_id'] = $userId;

            return Address::create($data);
        });
    }

    /**
     * Update an existing address.
     */
    public function update(Address $address, array $data): Address
    {
        return DB::transaction(function () use ($address, $data) {
            if (! empty($data['is_default'])) {
                $this->resetDefaultStatus($address->user_id, $address->id);
            }

            $address->update($data);

            return $address;
        });
    }

    /**
     * Delete an address.
     */
    public function delete(Address $address): bool
    {
        return $address->delete();
    }

    /**
     * Reset default status for all user addresses.
     */
    protected function resetDefaultStatus(int $userId, ?int $exceptAddressId = null): void
    {
        $query = Address::where('user_id', $userId);

        if ($exceptAddressId) {
            $query->where('id', '!=', $exceptAddressId);
        }

        $query->update(['is_default' => false]);
    }
}
