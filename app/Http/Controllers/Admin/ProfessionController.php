<?php

namespace App\Http\Controllers\Admin;

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
        return $this->professionService->dataTable($request->all());
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Les 6 professions de santé sont désormais figées. La création via CRUD a été éliminée.',
        ], 403);
    }

    public function show(Profession|int|string $profession): JsonResponse
    {
        return response()->json(
            $this->professionService->getByIdentifier($profession)
        );
    }

    public function update(Request $request, Profession|int|string $profession): JsonResponse
    {
        if ($request->has('documents')) {
            $enum = $this->professionService->resolveEnum($profession);
            if ($enum === null) {
                abort(404, 'Profession introuvable.');
            }

            return response()->json(
                $this->professionService->syncDocumentsForEnum($enum, (array) $request->input('documents'))
            );
        }

        return response()->json([
            'message' => 'Les 6 professions de santé sont désormais figées. Seules les exigences documentaires peuvent être configurées.',
        ], 403);
    }

    public function updateDocuments(Request $request, Profession|int|string $profession): JsonResponse
    {
        $enum = $this->professionService->resolveEnum($profession);
        if ($enum === null) {
            abort(404, 'Profession introuvable.');
        }

        $documents = $request->input('documents', []);

        return response()->json(
            $this->professionService->syncDocumentsForEnum($enum, (array) $documents)
        );
    }

    public function destroy(Profession|int|string $profession): JsonResponse
    {
        return response()->json([
            'message' => 'Les 6 professions de santé sont désormais figées. La suppression via CRUD a été éliminée.',
        ], 403);
    }
}
