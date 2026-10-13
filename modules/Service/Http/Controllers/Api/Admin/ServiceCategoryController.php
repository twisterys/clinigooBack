<?php

namespace Modules\Service\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Service\Models\ServiceCategory;
use Modules\Service\Services\ServiceCategoryService;

class ServiceCategoryController extends Controller
{
    public function __construct(protected ServiceCategoryService $categoryService) {}

    public function index(Request $request): JsonResponse
    {
        return $this->categoryService->dataTable($request->all());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);
        $category = $this->categoryService->store($request->all());

        return response()->json($category, 201);
    }

    public function show(ServiceCategory|int|string $serviceCategory): JsonResponse
    {
        if (! $serviceCategory instanceof ServiceCategory) {
            $serviceCategory = ServiceCategory::findOrFail($serviceCategory);
        }

        return response()->json($serviceCategory);
    }

    public function update(Request $request, ServiceCategory|int|string $serviceCategory): JsonResponse
    {
        if (! $serviceCategory instanceof ServiceCategory) {
            $serviceCategory = ServiceCategory::findOrFail($serviceCategory);
        }

        $category = $this->categoryService->update($serviceCategory, $request->all());

        return response()->json($category);
    }

    public function destroy(ServiceCategory|int|string $serviceCategory): JsonResponse
    {
        $id = $serviceCategory instanceof ServiceCategory ? $serviceCategory->id : (int) $serviceCategory;
        $this->categoryService->delete($id);

        return response()->json(null, 204);
    }
}
