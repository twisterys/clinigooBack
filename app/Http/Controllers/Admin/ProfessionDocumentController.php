<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProfessionDocumentRequest;
use App\Http\Requests\Admin\UpdateProfessionDocumentRequest;
use App\Models\ProfessionDocument;
use App\Services\ProfessionDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfessionDocumentController extends Controller
{
    public function __construct(protected ProfessionDocumentService $documentService) {}

    public function index(Request $request): JsonResponse
    {
        return $this->documentService->dataTable($request->all());
    }

    public function store(StoreProfessionDocumentRequest $request): JsonResponse
    {
        $document = $this->documentService->store($request->validated());

        return response()->json($document, 201);
    }

    public function show(ProfessionDocument|int|string $document): JsonResponse
    {
        if (! $document instanceof ProfessionDocument) {
            $document = ProfessionDocument::findOrFail($document);
        }

        return response()->json($document);
    }

    public function update(UpdateProfessionDocumentRequest $request, ProfessionDocument|int|string $document): JsonResponse
    {
        if (! $document instanceof ProfessionDocument) {
            $document = ProfessionDocument::findOrFail($document);
        }

        $document = $this->documentService->update($document, $request->validated());

        return response()->json($document);
    }

    public function destroy(ProfessionDocument|int|string $document): JsonResponse
    {
        if (! $document instanceof ProfessionDocument) {
            $document = ProfessionDocument::findOrFail($document);
        }

        $this->documentService->delete($document);

        return response()->json(null, 204);
    }
}
