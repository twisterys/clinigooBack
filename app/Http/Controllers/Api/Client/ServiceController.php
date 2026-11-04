<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Profession;
use App\Models\Service;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct(protected ServiceService $serviceService) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'recent' => $request->boolean('recent'),
        ];

        $services = $this->serviceService->getServices($filters)
            ->map(fn ($s) => $this->serviceService->formatService($s));

        return response()->json([
            'items' => $services,
            'data' => $services,
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $services = $this->serviceService->getServices(['recent' => true, 'limit' => 6])
            ->map(fn ($s) => $this->serviceService->formatService($s));

        return response()->json([
            'items' => $services,
            'data' => $services,
        ]);
    }

    public function show(Request $request, Service $service): JsonResponse
    {
        return response()->json($this->serviceService->formatService($service));
    }

    public function byProfession(Request $request, Profession|int|string $profession): JsonResponse
    {
        if (! $profession instanceof Profession) {
            $profession = Profession::findOrFail($profession);
        }

        $services = $this->serviceService->getServicesByProfession($profession)
            ->map(fn ($s) => $this->serviceService->formatService($s));

        return response()->json([
            'profession' => [
                'id' => $profession->id,
                'name' => $profession->name,
                'slug' => $profession->slug,
            ],
            'items' => $services,
            'data' => $services,
        ]);
    }
}
