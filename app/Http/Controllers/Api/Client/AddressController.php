<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Client\AddressStoreRequest;
use App\Http\Requests\Api\Client\AddressUpdateRequest;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = Address::where('user_id', auth()->id())->get();

        return response()->json([
            'items' => $addresses,
            'data' => $addresses,
        ]);
    }

    public function store(AddressStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $address = Address::create($data);

        return response()->json($address, 201);
    }

    public function show(Request $request, Address $address): JsonResponse
    {
        return response()->json($address);
    }

    public function update(AddressUpdateRequest $request, Address $address): JsonResponse
    {
        $address->update($request->validated());

        return response()->json($address);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        $address->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
