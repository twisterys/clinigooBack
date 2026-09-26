<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Client\BeneficiaryStoreRequest;
use App\Http\Requests\Api\Client\BeneficiaryUpdateRequest;
use App\Models\Beneficiary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $beneficiaries = Beneficiary::where('user_id', auth()->id())->get();

        return response()->json([
            'items' => $beneficiaries,
            'data' => $beneficiaries,
        ]);
    }

    public function store(BeneficiaryStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $beneficiary = Beneficiary::create($data);

        return response()->json($beneficiary, 201);
    }

    public function show(Request $request, Beneficiary $beneficiary): JsonResponse
    {
        return response()->json($beneficiary);
    }

    public function update(BeneficiaryUpdateRequest $request, Beneficiary $beneficiary): JsonResponse
    {
        $beneficiary->update($request->validated());

        return response()->json($beneficiary);
    }

    public function destroy(Request $request, Beneficiary $beneficiary): JsonResponse
    {
        $beneficiary->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
