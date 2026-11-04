<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\ProfessionDocument;
use App\Services\ProfessionDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfessionDocumentController extends Controller
{
    public function __construct(protected ProfessionDocumentService $documentService) {}

    public function index(Request $request): JsonResponse
    {
        $documents = $this->documentService->getAll()
            ->map(fn ($doc) => $this->documentService->formatDocument($doc));

        return response()->json([
            'items' => $documents,
            'data' => $documents,
        ]);
    }

    public function show(Request $request, ProfessionDocument|int|string $document): JsonResponse
    {
        if (! $document instanceof ProfessionDocument) {
            $document = ProfessionDocument::findOrFail($document);
        }

        return response()->json($this->documentService->formatDocument($document));
    }

    public function getDocumentsByProfession(Request $request, int|string $professionId): JsonResponse
    {

        $documents = $this->documentService->getDocumentsByProfession($professionId)
            ->map(fn ($doc) => $this->documentService->formatDocument($doc));

        return response()->json([
            'data' => $documents,
        ]);
    }
}
