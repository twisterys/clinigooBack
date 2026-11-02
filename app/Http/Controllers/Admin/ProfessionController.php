<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProfessionRequest;
use App\Http\Requests\Admin\UpdateProfessionRequest;
use App\Http\Resources\ProfessionResource;
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

    public function store(StoreProfessionRequest $request): JsonResponse
    {
        $profession = $this->professionService->store($request->validated());

        return response()->json($profession, 201);
    }

    public function show(Profession|int|string $profession): JsonResponse
    {
        if (! $profession instanceof Profession) {
            $profession = Profession::findOrFail($profession);
        }

        $profession->load(['services', 'documents']);

        return response()->json(new ProfessionResource($profession));
    }

    public function update(UpdateProfessionRequest $request, Profession|int|string $profession): JsonResponse
    {
        if (! $profession instanceof Profession) {
            $profession = Profession::findOrFail($profession);
        }

        $profession = $this->professionService->update($profession, $request->validated());

        return response()->json($profession);
    }

    public function destroy(Profession|int|string $profession): JsonResponse
    {
        if (! $profession instanceof Profession) {
            $profession = Profession::findOrFail($profession);
        }

        $this->professionService->delete($profession);

        return response()->json(null, 204);
    }
}
