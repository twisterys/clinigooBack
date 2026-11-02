<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct(protected ServiceService $serviceService) {}

    public function index(Request $request): JsonResponse
    {
        $locale = $this->getLocale($request);

        $filters = [
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

        return response()->json($this->serviceService->formatService($service, $locale));
    }

    protected function getLocale(Request $request): string
    {
        $locale = $request->header('Accept-Language') ?? $request->input('lang') ?? app()->getLocale();

        return strlen($locale) > 2 ? substr($locale, 0, 2) : $locale;
    }
}
