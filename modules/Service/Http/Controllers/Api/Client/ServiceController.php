<?php

namespace Modules\Service\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Service\Models\Service;
use Modules\Service\Services\ServiceService;

class ServiceController extends Controller
{
    public function __construct(protected ServiceService $serviceService) {}

    public function index(Request $request): JsonResponse
    {
        $locale = $this->getLocale($request);

        $filters = [
            'category_id' => $request->input('category_id') ?? $request->input('service_category_id') ?? $request->input('category'),
            'recent' => $request->boolean('recent'),
        ];

        $services = $this->serviceService->getServices($filters)
            ->map(fn ($s) => $this->serviceService->formatService($s, $locale));

        return response()->json([
            'items' => $services,
            'data' => $services,
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $locale = $this->getLocale($request);

        $services = $this->serviceService->getServices(['recent' => true, 'limit' => 6])
            ->map(fn ($s) => $this->serviceService->formatService($s, $locale));

        return response()->json([
            'items' => $services,
            'data' => $services,
        ]);
    }

    public function show(Request $request, Service $service): JsonResponse
    {
        $locale = $this->getLocale($request);

        $service->load('serviceCategory');

        return response()->json($this->serviceService->formatService($service, $locale));
    }

    protected function getLocale(Request $request): string
    {
        $locale = $request->header('Accept-Language') ?? $request->input('lang') ?? app()->getLocale();

        return strlen($locale) > 2 ? substr($locale, 0, 2) : $locale;
    }
}
