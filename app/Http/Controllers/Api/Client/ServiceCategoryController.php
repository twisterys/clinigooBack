<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locale = $request->header('Accept-Language') ?? $request->input('lang') ?? app()->getLocale();
        if (strlen($locale) > 2) {
            $locale = substr($locale, 0, 2);
        }

        $categories = ServiceCategory::with('services')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($c) => $this->formatCategory($c, $locale));

        return response()->json([
            'items' => $categories,
            'data' => $categories,
        ]);
    }

    public function show(Request $request, ServiceCategory $serviceCategory): JsonResponse
    {
        $locale = $request->header('Accept-Language') ?? $request->input('lang') ?? app()->getLocale();
        if (strlen($locale) > 2) {
            $locale = substr($locale, 0, 2);
        }

        $serviceCategory->load('services');

        return response()->json($this->formatCategory($serviceCategory, $locale));
    }

    protected function formatCategory(ServiceCategory $category, string $locale): array
    {
        return [
            'id' => (string) $category->id,
            'name' => $category->getTranslatedName($locale),
            'slug' => $category->slug,
            'icon' => $category->icon ?? '',
            'services' => $category->relationLoaded('services')
                ? $category->services->map(fn ($s) => [
                    'id' => (string) $s->id,
                    'name' => $s->getTranslatedName($locale),
                    'nameKey' => $s->getTranslatedName($locale),
                    'description' => $s->getTranslatedDescription($locale),
                    'descriptionKey' => $s->getTranslatedDescription($locale),
                    'icon' => $s->icon ?? '',
                    'price_per_hour' => (float) ($s->base_price ?? 150),
                    'pricePerHour' => (float) ($s->base_price ?? 150),
                    'default_duration_hours' => round(($s->duration_minutes ?? 60) / 60, 1),
                    'defaultDurationHours' => round(($s->duration_minutes ?? 60) / 60, 1),
                ])->values()->all()
                : [],
        ];
    }
}
