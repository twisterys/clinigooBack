<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Client\CareRequestStoreRequest;
use App\Http\Requests\Api\Client\CareRequestUpdateRequest;
use App\Models\CareRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CareRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $careRequests = CareRequest::with(['documents', 'service', 'beneficiary', 'address'])
            ->where('user_id', auth()->id())
            ->get();

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

        if (empty($data['uuid'])) {
            $data['uuid'] = (string) Str::uuid();
        }

        if (isset($data['address']) && is_array($data['address'])) {
            if (isset($data['address']['latitude'])) {
                $data['latitude'] = $data['address']['latitude'];
            }
            if (isset($data['address']['longitude'])) {
                $data['longitude'] = $data['address']['longitude'];
            }
        }

        if (! empty($data['urgent'])) {
            $data['date'] = null;
            $data['time'] = null;
            $data['scheduled_at'] = now();
        } elseif (! empty($data['date'])) {
            $timeStr = ! empty($data['time']) ? $data['time'] : '00:00';
            $data['scheduled_at'] = Carbon::parse($data['date'].' '.$timeStr);
        }

        unset($data['address'], $data['documents']);

        $careRequest = CareRequest::create($data);

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('care_requests/documents', 'public');
                    $careRequest->documents()->create([
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }
        }

        $careRequest->load(['documents', 'service', 'beneficiary', 'address']);

        return response()->json($careRequest, 201);
    }

    public function show(Request $request, CareRequest $careRequest): JsonResponse
    {
        $careRequest->load(['documents', 'service', 'beneficiary', 'address']);

        return response()->json($careRequest);
    }

    public function update(CareRequestUpdateRequest $request, CareRequest $careRequest): JsonResponse
    {
        $data = $request->validated();
        if (isset($data['description'])) {
            $data['notes'] = $data['description'];
        }

        $careRequest->update($data);
        $careRequest->load(['documents', 'service', 'beneficiary', 'address']);

        return response()->json($careRequest);
    }
}
