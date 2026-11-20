<?php

namespace App\Services;

use App\Enums\HealthcareProfession;
use App\Http\Resources\ProfessionResource;
use App\Models\Profession;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ProfessionService
{
    /**
     * Get DataTables response for professions sourced from HealthcareProfession enum.
     *
     * @param  array<string, mixed>  $filters
     */
    public function dataTable(array $filters = []): mixed
    {
        $professions = $this->getAll($filters);

        return DataTables::of($professions)->make(true);
    }

    /**
     * Get all fixed healthcare professions from the HealthcareProfession enum.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function getAll(array $filters = []): Collection
    {
        $dbProfessionsBySlug = Profession::with(['services', 'documents'])
            ->whereIn('slug', HealthcareProfession::values())
            ->get()
            ->keyBy('slug');

        $professions = collect(HealthcareProfession::cases())
            ->map(fn (HealthcareProfession $enum) => $this->formatEnumProfession(
                $enum,
                $dbProfessionsBySlug->get($enum->value),
            ));

        $search = $this->extractSearchFilter($filters);
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $professions = $professions->filter(function (array $item) use ($needle): bool {
                $haystack = mb_strtolower(implode(' ', [
                    (string) ($item['name'] ?? ''),
                    (string) ($item['description'] ?? ''),
                    (string) ($item['slug'] ?? ''),
                    (string) ($item['category']['name'] ?? ''),
                ]));

                return str_contains($haystack, $needle);
            });
        }

        return $professions->values();
    }

    /**
     * Resolve a HealthcareProfession enum from a slug, label, enum name, 1-based index, or DB Profession.
     */
    public function resolveEnum(Profession|int|string $profession): ?HealthcareProfession
    {
        if ($profession instanceof Profession) {
            return HealthcareProfession::fromInput($profession->slug ?? $profession->name ?? $profession->id);
        }

        $enum = HealthcareProfession::fromInput($profession);
        if ($enum !== null) {
            return $enum;
        }

        if (is_numeric($profession)) {
            $dbProfession = Profession::find((int) $profession);
            if ($dbProfession !== null) {
                return HealthcareProfession::fromInput($dbProfession->slug ?? $dbProfession->name);
            }
        }

        return null;
    }

    /**
     * Get a single formatted profession from the HealthcareProfession enum.
     *
     * @return array<string, mixed>
     */
    public function getByIdentifier(Profession|int|string $profession): array
    {
        $enum = $this->resolveEnum($profession);

        if ($enum === null) {
            abort(404, 'Profession introuvable.');
        }

        $dbProfession = Profession::with(['services', 'documents'])
            ->where('slug', $enum->value)
            ->first();

        return $this->formatEnumProfession($enum, $dbProfession);
    }

    /**
     * Get services associated with a profession resolved from HealthcareProfession enum.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getServicesForProfession(Profession|int|string $profession): array
    {
        $enum = $this->resolveEnum($profession);

        if ($enum === null) {
            abort(404, 'Profession introuvable.');
        }

        $dbProfession = Profession::with('services')
            ->where('slug', $enum->value)
            ->first();

        if (! $dbProfession) {
            return [];
        }

        return $dbProfession->services->map(fn ($service) => [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'icon' => $service->icon ?? '',
            'base_price' => (float) ($service->base_price ?? 0),
            'duration_minutes' => $service->duration_minutes,
        ])->values()->all();
    }

    /**
     * Sync document requirements for an enum-defined profession and return the formatted payload.
     *
     * @param  array<mixed>  $documents
     * @return array<string, mixed>
     */
    public function syncDocumentsForEnum(HealthcareProfession $enum, array $documents): array
    {
        $dbProfession = Profession::firstOrCreate(
            ['slug' => $enum->value],
            [
                'name' => $enum->label(),
                'description' => $enum->category()->description(),
                'icon' => $enum->icon(),
                'is_active' => true,
            ]
        );

        $this->syncDocuments($dbProfession, $documents);
        $dbProfession->load(['services', 'documents']);

        return $this->formatEnumProfession($enum, $dbProfession);
    }

    /**
     * Format a HealthcareProfession enum case for API responses.
     *
     * @return array<string, mixed>
     */
    public function formatEnumProfession(HealthcareProfession $enum, ?Profession $dbProfession = null): array
    {
        $services = $dbProfession && $dbProfession->relationLoaded('services')
            ? $dbProfession->services->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'icon' => $service->icon ?? '',
                'base_price' => (float) ($service->base_price ?? 0),
                'duration_minutes' => $service->duration_minutes,
            ])->values()->all()
            : [];

        $documents = $dbProfession && $dbProfession->relationLoaded('documents')
            ? $dbProfession->documents->map(fn ($doc) => [
                'id' => $doc->id,
                'name' => $doc->name,
                'description' => $doc->description,
                'formats' => $doc->formats ?? ['pdf', 'jpg', 'jpeg', 'png'],
                'enabled_formats' => $doc->enabled_formats ?? ['pdf', 'jpg', 'jpeg', 'png'],
                'required' => (bool) ($doc->pivot->required ?? true),
            ])->values()->all()
            : [];

        return [
            'id' => $enum->value,
            'key' => $enum->value,
            'value' => $enum->value,
            'slug' => $enum->value,
            'name' => $enum->label(),
            'label' => $enum->label(),
            'description' => $enum->category()->description(),
            'icon' => $enum->icon(),
            'category' => [
                'id' => $enum->category()->value,
                'name' => $enum->category()->label(),
            ],
            'is_active' => true,
            'catalog_services' => $enum->catalogServices(),
            'services' => $services,
            'documents' => $documents,
        ];
    }

    /**
     * Extract a plain search string from request/DataTables filters.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function extractSearchFilter(array $filters): string
    {
        $search = $filters['search'] ?? '';
        if (is_array($search)) {
            $search = $search['value'] ?? '';
        }

        return is_string($search) ? trim($search) : '';
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
    public function formatProfession(Profession $profession, ?string $locale = null): array
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

        $documents = $profession->relationLoaded('documents')
            ? $profession->documents->map(fn ($doc) => [
                'id' => $doc->id,
                'name' => $doc->name,
                'description' => $doc->description,
                'formats' => $doc->formats ?? ['pdf', 'jpg', 'jpeg', 'png'],
                'enabled_formats' => $doc->enabled_formats ?? ['pdf', 'jpg', 'jpeg', 'png'],
                'required' => (bool) ($doc->pivot->required ?? true),
            ])->values()->all()
            : [];

        return [
            'id' => $profession->id,
            'name' => $profession->name,
            'description' => $profession->description,
            'slug' => $profession->slug,
            'icon' => $profession->icon,
            'is_active' => (bool) $profession->is_active,
            'services' => $services,
            'documents' => $documents,
        ];
    }
}
