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
        $professions = $this->professionService->getAll($request->all());

        $formatted = $professions->map(fn (Profession $p) => $this->professionService->formatProfession($p))->values();

        return response()->json(
            $formatted
        );
    }

    public function show(Request $request, Profession|int|string $profession): JsonResponse
    {
        if (! $profession instanceof Profession) {
            $profession = Profession::findOrFail($profession);
        }

        $profession->load(['services', 'documents']);

        return response()->json($this->professionService->formatProfession($profession));
    }

    public function getProfessionServices(Request $request, int|string $professionId): JsonResponse
    {
        $profession = Profession::with('services')->findOrFail($professionId);

        return response()->json([
            'data' => $profession->services,
        ]);
    }
}
