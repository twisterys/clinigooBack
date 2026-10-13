<?php

namespace Modules\Service\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Service\Models\ServiceCategory;
use Modules\Service\Services\ServiceCategoryService;

class ServiceCategoryController extends Controller
{
    public function __construct(protected ServiceCategoryService $categoryService) {}

    public function index(Request $request): JsonResponse
    {
        $locale = $this->getLocale($request);

        $categories = $this->categoryService->getActiveWithServices()
            ->map(fn ($c) => $this->categoryService->formatCategory($c, $locale));

        return response()->json([
            'items' => $categories,
            'data' => $categories,
        ]);
    }

    public function show(Request $request, ServiceCategory $serviceCategory): JsonResponse
    {
        $locale = $this->getLocale($request);

        $serviceCategory->load('services');

        return response()->json($this->categoryService->formatCategory($serviceCategory, $locale));
    }

    protected function getLocale(Request $request): string
    {
        $locale = $request->header('Accept-Language') ?? $request->input('lang') ?? app()->getLocale();

        return strlen($locale) > 2 ? substr($locale, 0, 2) : $locale;
    }
}
