<?php

namespace App\Services;

use App\Models\CareRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class CareRequestService
{
    /**
     * Get DataTables response for care requests.
     */
    public function dataTable(array $filters = []): mixed
    {
        $query = CareRequest::with(['service', 'beneficiary', 'user', 'address']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($filters) {
                if (! empty($filters['search'])) {
                    $search = $filters['search'];
                    $query->where(function ($q) use ($search) {
                        $q->where('reference', 'like', "%{$search}%")
                            ->orWhere('status', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($uq) use ($search) {
                                $uq->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            })
                            ->orWhereHas('beneficiary', function ($bq) use ($search) {
                                $bq->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('service', function ($sq) use ($search) {
                                $sq->where('name', 'like', "%{$search}%");
                            });
                    });
                }
            })
            ->make(true);
    }

    /**
     * Get all care requests for a user.
     */
    public function getForUser(int $userId): Collection
    {
        return CareRequest::with(['documents', 'service', 'beneficiary', 'address'])
            ->where('user_id', $userId)
            ->get();
    }

    /**
     * Store a new care request.
     *
     * @param  UploadedFile[]  $documents
     */
    public function store(int $userId, array $data, array $documents = []): CareRequest
    {
        return DB::transaction(function () use ($userId, $data, $documents) {
            $data['user_id'] = $userId;

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

            // Remove non-model attributes
            $modelData = collect($data)->except(['address', 'documents'])->toArray();

            $careRequest = CareRequest::create($modelData);

            $this->uploadDocuments($careRequest, $documents);

            return $careRequest->load(['documents', 'service', 'beneficiary', 'address']);
        });
    }

    /**
     * Update an existing care request.
     */
    public function update(CareRequest $careRequest, array $data): CareRequest
    {
        if (isset($data['description'])) {
            $data['notes'] = $data['description'];
        }

        $careRequest->update($data);

        return $careRequest->load(['documents', 'service', 'beneficiary', 'address']);
    }

    /**
     * Upload and attach documents to a care request.
     *
     * @param  UploadedFile[]  $documents
     */
    public function uploadDocuments(CareRequest $careRequest, array $documents): void
    {
        foreach ($documents as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
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
}
