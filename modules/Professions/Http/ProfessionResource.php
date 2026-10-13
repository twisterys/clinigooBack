<?php

namespace Modules\Professions\Http;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Professions\Models\Profession;

/**
 * @mixin Profession
 */
class ProfessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'services' => $this->whenLoaded('services', function () {
                return $this->services->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'description' => $service->description,
                        'is_active' => $service->is_active,
                        'icon' => $service->icon ? asset('storage/'.$service->icon) : null,
                        'base_price' => $service->base_price,
                        'duration_minutes' => $service->duration_minutes, ];
                });
            }),
            'documents' => $this->whenLoaded('documents', function () {
                return $this->documents->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'name' => $document->name,
                        'required' => (bool) ($document->pivot->required ?? true),
                        'formats' => $document->formats];
                });
            }),
        ];
    }
}
