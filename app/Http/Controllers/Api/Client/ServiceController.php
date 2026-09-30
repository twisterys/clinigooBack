<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locale = $request->header('Accept-Language') ?? $request->input('lang') ?? app()->getLocale();
        if (strlen($locale) > 2) {
            $locale = substr($locale, 0, 2);
        }

        $query = Service::with('serviceCategory')->where('is_active', true);

        if ($categoryId = $request->input('category_id') ?? $request->input('service_category_id') ?? $request->input('category')) {
            $query->where('service_category_id', $categoryId);
        }

        if ($request->boolean('recent')) {
            $query->latest();
        } else {
            $query->orderBy('sort_order');
        }

        $services = $query->get()->map(fn ($s) => $this->formatService($s, $locale));

        return response()->json([
            'items' => $services,
            'data' => $services,
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $locale = $request->header('Accept-Language') ?? $request->input('lang') ?? app()->getLocale();
        if (strlen($locale) > 2) {
            $locale = substr($locale, 0, 2);
        }

        $services = Service::with('serviceCategory')
            ->where('is_active', true)
            ->latest()
            ->take(6)
            ->get()
            ->map(fn ($s) => $this->formatService($s, $locale));

        return response()->json([
            'items' => $services,
            'data' => $services,
        ]);
    }

    public function show(Request $request, Service $service): JsonResponse
    {
        $locale = $request->header('Accept-Language') ?? $request->input('lang') ?? app()->getLocale();
        if (strlen($locale) > 2) {
            $locale = substr($locale, 0, 2);
        }

        $service->load('serviceCategory');

        return response()->json($this->formatService($service, $locale));
    }

    protected function formatService(Service $service, string $locale): array
    {
        $category = $service->relationLoaded('serviceCategory') && $service->serviceCategory
            ? [
                'id' => (string) $service->serviceCategory->id,
                'name' => $service->serviceCategory->getTranslatedName($locale),
                'slug' => $service->serviceCategory->slug,
                'icon' => $service->serviceCategory->icon ?? '',
            ]
            : null;

        $name = $service->getTranslatedName($locale);
        $description = $service->getTranslatedDescription($locale);

        return [
            'id' => (string) $service->id,
            'service_category_id' => (string) $service->service_category_id,
            'service_category' => $category,
            'name' => $name,
            'nameKey' => $name,
            'description' => $description,
            'descriptionKey' => $description,
            'short_description' => $description,
            'icon' => $service->icon ?? '',
            'image' => $service->image ?? '',
            'price_per_hour' => (float) ($service->base_price ?? 150),
            'pricePerHour' => (float) ($service->base_price ?? 150),
            'default_duration_hours' => round(($service->duration_minutes ?? 60) / 60, 1),
            'defaultDurationHours' => round(($service->duration_minutes ?? 60) / 60, 1),
            'popular' => (bool) $service->sort_order,
        ];
    }
}
