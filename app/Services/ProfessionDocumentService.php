<?php

namespace App\Services;

use App\Models\Profession;
use App\Models\ProfessionDocument;
use Illuminate\Database\Eloquent\Collection;
use Yajra\DataTables\Facades\DataTables;

class ProfessionDocumentService
{
    /**
     * Get DataTables response for profession documents.
     *
     * @param  array<string, mixed>  $filters
     */
    public function dataTable(array $filters = []): mixed
    {
        $query = ProfessionDocument::withCount('professions');

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($filters) {
                if (! empty($filters['search'])) {
                    $search = $filters['search'];
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                }
            })
            ->make(true);
    }

    /**
     * Get all active profession documents.
     *
     * @return Collection<int, ProfessionDocument>
     */
    public function getAll(): Collection
    {
        return ProfessionDocument::where('is_active', true)->get();
    }

    /**
     * Store a new profession document.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): ProfessionDocument
    {

        if (empty($data['formats'])) {
            $data['formats'] = ['pdf', 'jpg', 'jpeg', 'png'];
        }

        return ProfessionDocument::create($data);
    }

    /**
     * Update an existing profession document.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ProfessionDocument $document, array $data): ProfessionDocument
    {
        if (isset($data['enabled_formats']) && ! isset($data['formats'])) {
            $data['formats'] = $data['enabled_formats'];
        }

        $document->update($data);

        return $document->fresh();
    }

    /**
     * Delete a profession document.
     */
    public function delete(ProfessionDocument $document): bool
    {
        return (bool) $document->delete();
    }

    /**
     * Format profession document for API responses.
     *
     * @return array<string, mixed>
     */
    public function formatDocument(ProfessionDocument $document, ?string $locale = null): array
    {
        return [
            'id' => $document->id,
            'name' => $document->name,
            'description' => $document->description,
            'formats' => $document->formats ?? ['pdf', 'jpg', 'jpeg', 'png'],
            'enabled_formats' => $document->enabled_formats ?? ['pdf', 'jpg', 'jpeg', 'png'],
            'required' => (bool) ($document->pivot->required ?? true),
            'is_active' => (bool) $document->is_active,
        ];
    }

    public function getDocumentsByProfession($professionId)
    {
        $profession = Profession::with('documents')->find($professionId);
        if ($profession) {
            return $profession->documents;
        }

        return collect();
    }
}
