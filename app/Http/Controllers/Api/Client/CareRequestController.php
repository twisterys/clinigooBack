<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Client\CareRequestStoreRequest;
use App\Http\Requests\Api\Client\CareRequestUpdateRequest;
use App\Models\CareRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CareRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $careRequests = CareRequest::where('user_id', auth()->id())->get();

        return response()->json([
            'items' => $careRequests,
            'data' => $careRequests,
        ]);
    }

    public function store(CareRequestStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        if (empty($data['reference'])) {
            $data['reference'] = 'CLG-'.rand(1000, 9999);
        }
        $careRequest = CareRequest::create($data);

        return response()->json($careRequest, 201);
    }

    public function show(Request $request, CareRequest $careRequest): JsonResponse
    {
        return response()->json($careRequest);
    }

    public function update(CareRequestUpdateRequest $request, CareRequest $careRequest): JsonResponse
    {
        $careRequest->update($request->validated());

        return response()->json($careRequest);
    }
}
