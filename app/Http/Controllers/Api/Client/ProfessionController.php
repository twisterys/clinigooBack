<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Profession;
use App\Services\ProfessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfessionController extends Controller
{
    public function __construct(protected ProfessionService $professionService) {}

    public function index(Request $request): JsonResponse
    {
        $formatted = $this->professionService->getAll($request->all())->values();

        return response()->json([
            'items' => $formatted,
            'data' => $formatted,
        ]);
    }

    public function show(Request $request, Profession|int|string $profession): JsonResponse
    {
        return response()->json(
            $this->professionService->getByIdentifier($profession)
        );
    }

    public function getProfessionServices(Request $request, int|string $professionId): JsonResponse
    {
        return response()->json([
            'data' => $this->professionService->getServicesForProfession($professionId),
        ]);
    }
}
