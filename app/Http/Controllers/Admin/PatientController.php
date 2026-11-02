<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePatientRequest;
use App\Http\Requests\Admin\UpdatePatientRequest;
use App\Models\User;
use App\Services\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct(
        protected PatientService $patientService
    ) {}

    /**
     * List patients with filters and DataTables/pagination support.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('paginate') || $request->input('format') === 'paginated') {
            return response()->json($this->patientService->paginate($request->all()));
        }

        return $this->patientService->dataTable($request->all());
    }

    /**
     * Store a new patient and automatically create a beneficiary with the same name.
     */
    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = $this->patientService->store($request->validated());

        return response()->json($patient, 201);
    }

    /**
     * Display a specific patient.
     */
    public function show(User|int|string $id): JsonResponse
    {
        $patient = $this->patientService->find($id);

        return response()->json($patient);
    }

    /**
     * Update an existing patient.
     */
    public function update(UpdatePatientRequest $request, User|int|string $id): JsonResponse
    {
        $patient = $this->patientService->find($id);
        $updated = $this->patientService->update($patient, $request->validated());

        return response()->json($updated);
    }

    /**
     * Delete an existing patient.
     */
    public function destroy(User|int|string $id): JsonResponse
    {
        $patient = $this->patientService->find($id);
        $this->patientService->delete($patient);

        return response()->json(null, 204);
    }
}
