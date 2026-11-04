<?php

namespace App\Services;

use App\Http\Requests\Admin\StoreServiceRequest;
use App\Http\Requests\Admin\UpdateServiceRequest;
use App\Models\Profession;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class ServiceService
{
    /**
     * Get DataTables response for services.
     */
    public function dataTable(array $filters = []): mixed
    {
        $query = Service::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($filters) {
                if (! empty($filters['search'])) {
                    $search = $filters['search'];
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                }
            })->editColumn('icon', function ($service) {
                return $service->icon ? asset('storage/'.$service->icon) : null;
            })
            ->make(true);
    }

    /**
     * Get services based on filters.
     */
    public function getServices(array $filters = []): Collection
    {
        $query = Service::where('is_active', true);

        if (! empty($filters['recent'])) {
            $query->latest();
        } else {
            $query->orderBy('sort_order');
        }

        if (! empty($filters['limit'])) {
            $query->take($filters['limit']);
        }

        return $query->get();
    }

    /**
     * Get active services belonging to a profession.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Service>
     */
    public function getServicesByProfession(Profession|int $profession, array $filters = []): Collection
    {
        $professionId = $profession instanceof Profession ? $profession->id : $profession;

        $query = Service::query()
            ->where('is_active', true)
            ->whereHas('professions', fn ($query) => $query->where('professions.id', $professionId))
            ->orderBy('sort_order');

        if (! empty($filters['limit'])) {
            $query->take($filters['limit']);
        }

        return $query->get();
    }

    /**
     * Format service for API response.
     */
    public function formatService(Service $service): array
    {
        return [
            'id' => (string) $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'icon' => $service->icon ?? '',
            'base_price' => (float) ($service->base_price ?? 150),
            'defaultDurationHours' => round(($service->duration_minutes ?? 60) / 60, 1),
        ];
    }

    public function storeService(StoreServiceRequest $request): Service
    {
        $data = $request->validated();

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('service_icons', 'public');
        }

        return Service::create($data);
    }

    public function updateService(Service $service, UpdateServiceRequest|Request|array $request): Service
    {
        if ($request instanceof Request) {
            $data = method_exists($request, 'validated') ? $request->validated() : $request->all();

            if ($request->hasFile('icon')) {
                if ($service->icon && Storage::disk('public')->exists($service->icon)) {
                    Storage::disk('public')->delete($service->icon);
                }
                $data['icon'] = $request->file('icon')->store('service_icons', 'public');
            } elseif (! array_key_exists('icon', $data) || ($data['icon'] === null && ! $request->has('icon'))) {
                unset($data['icon']);
            }
        } else {
            $data = $request;
        }

        $service->update($data);

        return $service->fresh();
    }

    public function deleteService(Service $service): bool
    {
        if ($service->icon && Storage::disk('public')->exists($service->icon)) {
            Storage::disk('public')->delete($service->icon);
        }

        return (bool) $service->delete();
    }
}
