<?php

namespace App\Services;

use App\Http\Resources\ProfessionResource;
use App\Models\Profession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ProfessionService
{
    /**
     * Get DataTables response for professions.
     *
     * @param  array<string, mixed>  $filters
     */
    public function dataTable(array $filters = []): mixed
    {
        $query = Profession::with(['services', 'documents']);

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($filters) {
                if (! empty($filters['search'])) {
                    $search = $filters['search'];
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%");
                    });
                }
            })->editColumn('icon', function ($profession) {
                return $profession->icon ? asset('storage/'.$profession->icon) : null;
            })->addColumn('documents', function ($profession) {
                return $profession->documents->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'name' => $doc->name,
                        'required' => (bool) ($doc->pivot->required ?? true),
                    ];
                })->values()->toArray();
            })->make();
    }

    /**
     * Get active professions for clients.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Profession>
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Profession::with(['services', 'documents'])->where('is_active', true);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->get();
    }

    /**
     * Store a new profession.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): Profession
    {
        if (empty($data['slug']) && ! empty($data['name']) && is_string($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if (($data['icon'] ?? null) instanceof UploadedFile) {
            $data['icon'] = $data['icon']->store('profession_icons', 'public');
        }

        $profession = Profession::create(collect($data)->except(['services', 'service_ids', 'documents'])->toArray());

        if (isset($data['services'])) {
            $services = $data['services'] ?? [];
            $this->syncServices($profession, (array) $services);
        }

        if (isset($data['documents'])) {
            $this->syncDocuments($profession, (array) $data['documents']);
        }

        return $profession->fresh(['services', 'documents']);
    }

    /**
     * Update an existing profession.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Profession $profession, array $data): ProfessionResource
    {
        if (empty($data['slug']) && ! empty($data['name']) && is_string($data['name']) && empty($profession->slug)) {
            $data['slug'] = Str::slug($data['name']);
        }

        if (($data['icon'] ?? null) instanceof UploadedFile) {
            if ($profession->icon && Storage::disk('public')->exists($profession->icon)) {
                Storage::disk('public')->delete($profession->icon);
            }

            $data['icon'] = $data['icon']->store('profession_icons', 'public');
        } elseif (array_key_exists('icon', $data) && $data['icon'] === null) {
            if ($profession->icon && Storage::disk('public')->exists($profession->icon)) {
                Storage::disk('public')->delete($profession->icon);
            }
        } else {
            unset($data['icon']);
        }

        $profession->update(collect($data)->except(['services', 'service_ids', 'documents'])->toArray());

        if (isset($data['services'])) {
            $services = $data['services'] ?? [];
            $this->syncServices($profession, (array) $services);
        }

        if (isset($data['documents'])) {
            $this->syncDocuments($profession, (array) $data['documents']);
        }

        return new ProfessionResource($profession->fresh(['services', 'documents']));
    }

    /**
     * Delete a profession.
     */
    public function delete(Profession $profession): bool
    {
        if ($profession->icon && Storage::disk('public')->exists($profession->icon)) {
            Storage::disk('public')->delete($profession->icon);
        }

        return (bool) $profession->delete();
    }

    /**
     * Sync associated services.
     *
     * @param  array<int|string>  $serviceIds
     */
    public function syncServices(Profession $profession, array $serviceIds): void
    {
        $cleanIds = array_values(array_filter(array_map('intval', $serviceIds)));
        $profession->services()->sync($cleanIds);
    }

    /**
     * Sync documents with pivot "required" value.
     * Supports formats:
     * - [1, 2, 3] -> defaults required = true
     * - [1 => ['required' => false], 2 => ['required' => true]]
     * - [['id' => 1, 'required' => false], ['document_id' => 2, 'required' => true]]
     *
     * @param  array<mixed>  $documents
     */
    public function syncDocuments(Profession $profession, array $documents): void
    {
        $syncData = [];

        foreach ($documents as $key => $item) {
            if (is_array($item)) {
                $docId = $item['id'] ?? $item['document_id'] ?? $key;
                $required = ! isset($item['required']) || $item['required'] === true || $item['required'] === 'true';

                if (is_numeric($docId)) {
                    $syncData[(int) $docId] = ['required' => $required];
                }
            } elseif (is_numeric($item)) {
                // Flat array of IDs: [1, 2, 3]
                $syncData[(int) $item] = ['required' => true];
            }
        }

        $profession->documents()->sync($syncData);
    }

    /**
     * Format profession for API responses.
     *
     * @return array<string, mixed>
     */
    public function formatProfession(Profession $profession): array
    {
        $services = $profession->relationLoaded('services')
            ? $profession->services->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'icon' => $service->icon ?? '',
                'base_price' => (float) ($service->base_price ?? 0),
                'duration_minutes' => $service->duration_minutes,
            ])->values()->all()
            : [];

        return [
            'id' => $profession->id,
            'name' => $profession->name,
            'description' => $profession->description,
            'slug' => $profession->slug,
            'icon' => $profession->icon ? asset('storage/'.$profession->icon) : null,
            'is_active' => (bool) $profession->is_active,
            'services' => $services,
        ];
    }
}
