<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Service::query();

        if ($request->boolean('recent')) {
            $query->latest();
        }

        $services = $query->get();

        return response()->json([
            'items' => $services,
            'data' => $services,
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $services = Service::latest()->take(6)->get();

        return response()->json([
            'items' => $services,
            'data' => $services,
        ]);
    }

    public function show(Request $request, Service $service): JsonResponse
    {
        return response()->json($service);
    }
}
