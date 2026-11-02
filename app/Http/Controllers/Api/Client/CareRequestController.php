<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Client\CareRequestStoreRequest;
use App\Http\Requests\Api\Client\CareRequestUpdateRequest;
use App\Models\CareRequest;
use App\Services\CareRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CareRequestController extends Controller
{
    public function __construct(protected CareRequestService $careRequestService) {}

    public function index(Request $request): JsonResponse
    {
        $careRequests = $this->careRequestService->getForUser(auth()->id());

        return response()->json([
            'items' => $careRequests,
            'data' => $careRequests,
        ]);
    }

    public function store(CareRequestStoreRequest $request): JsonResponse
    {
        $careRequest = $this->careRequestService->store(
            auth()->id(),
            $request->validated(),
            $request->file('documents', [])
        );

        return response()->json($careRequest, 201);
    }

    public function show(Request $request, CareRequest $careRequest): JsonResponse
    {
        $careRequest->load(['documents', 'service', 'beneficiary', 'address']);

        return response()->json($careRequest);
    }

    public function update(CareRequestUpdateRequest $request, CareRequest $careRequest): JsonResponse
    {
        $careRequest = $this->careRequestService->update($careRequest, $request->validated());

        return response()->json($careRequest);
    }
}
