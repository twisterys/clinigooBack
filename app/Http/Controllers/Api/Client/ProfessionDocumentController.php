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
        $locale = $this->getLocale($request);

        $documents = $this->documentService->getAll()
            ->map(fn ($doc) => $this->documentService->formatDocument($doc, $locale));

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

        $locale = $this->getLocale($request);

        return response()->json($this->documentService->formatDocument($document, $locale));
    }

    public function getDocumentsByProfession(Request $request, int|string $professionId): JsonResponse
    {

        $documents = $this->documentService->getDocumentsByProfession($professionId)
            ->map(fn ($doc) => $this->documentService->formatDocument($doc));

        return response()->json([
            'data' => $documents,
        ]);
    }

    protected function getLocale(Request $request): string
    {
        $locale = $request->header('Accept-Language') ?? $request->input('lang') ?? app()->getLocale();

        return strlen($locale) > 2 ? substr($locale, 0, 2) : $locale;
    }
}
