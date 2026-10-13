<?php

namespace Modules\Service\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Service\Models\ServiceCategory;
use Yajra\DataTables\Facades\DataTables;

class ServiceCategoryService
{
    /**
     * Get DataTables response for service categories.
     */
    public function dataTable(array $filters = []): mixed
    {
        $query = ServiceCategory::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($filters) {
                if (! empty($filters['search'])) {
                    $search = $filters['search'];
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%");
                    });
                }
            })
            ->make(true);
    }

    /**
     * Get active service categories with services.
     */
    public function getActiveWithServices(): Collection
    {
        return ServiceCategory::with('services')
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get all service categories for admin.
     */
    public function getAll(): Collection
    {
        return ServiceCategory::get();
    }

    /**
     * Store a new service category.
     */
    public function store(array $data): ServiceCategory
    {
        return ServiceCategory::create($data);
    }

    /**
     * Update an existing service category.
     */
    public function update(ServiceCategory $category, array $data): ServiceCategory
    {
        $category->update($data);

        return $category;
    }

    /**
     * Delete a service category.
     */
    public function delete(int $category): bool
    {
        $category = ServiceCategory::find($category);
        if (! $category) {
            return false;
        }

        return $category->delete();
    }

    /**
     * Format category for API response.
     */
    public function formatCategory(ServiceCategory $category, ?string $locale = null): array
    {
        return [
            'id' => (string) $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'icon' => $category->icon ?? '',
            'services' => $category->relationLoaded('services')
                ? $category->services->map(fn ($s) => $this->formatServiceMinimal($s, $locale))->values()->all()
                : [],
        ];
    }

    /**
     * Format service minimally for category response.
     */
    protected function formatServiceMinimal($service, ?string $locale = null): array
    {
        return [
            'id' => (string) $service->id,
            'name' => $service->name,
            'nameKey' => $service->name,
            'description' => $service->description,
            'descriptionKey' => $service->description,
            'icon' => $service->icon ?? '',
            'price_per_hour' => (float) ($service->base_price ?? 150),
            'pricePerHour' => (float) ($service->base_price ?? 150),
            'default_duration_hours' => round(($service->duration_minutes ?? 60) / 60, 1),
            'defaultDurationHours' => round(($service->duration_minutes ?? 60) / 60, 1),
        ];
    }
}
