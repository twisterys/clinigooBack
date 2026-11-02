<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Client\BeneficiaryStoreRequest;
use App\Http\Requests\Api\Client\BeneficiaryUpdateRequest;
use App\Models\Beneficiary;
use App\Services\BeneficiaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
    public function __construct(protected BeneficiaryService $beneficiaryService) {}

    public function index(Request $request): JsonResponse
    {
        $beneficiaries = $this->beneficiaryService->getForUser(auth()->id());

        return response()->json([
            'items' => $beneficiaries,
            'data' => $beneficiaries,
        ]);
    }

    public function store(BeneficiaryStoreRequest $request): JsonResponse
    {
        $beneficiary = $this->beneficiaryService->store(auth()->id(), $request->validated());

        return response()->json($beneficiary, 201);
    }

    public function show(Request $request, Beneficiary $beneficiary): JsonResponse
    {
        return response()->json($beneficiary);
    }

    public function update(BeneficiaryUpdateRequest $request, Beneficiary $beneficiary): JsonResponse
    {
        $beneficiary = $this->beneficiaryService->update($beneficiary, $request->validated());

        return response()->json($beneficiary);
    }

    public function destroy(Request $request, Beneficiary $beneficiary): JsonResponse
    {
        $this->beneficiaryService->delete($beneficiary);

        return response()->json(['message' => 'Deleted successfully']);
    }
}
