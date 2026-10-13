<?php

namespace Modules\Address\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Client\AddressStoreRequest;
use App\Http\Requests\Api\Client\AddressUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Address\Models\Address;
use Modules\Address\Services\AddressService;

class AddressController extends Controller
{
    public function __construct(protected AddressService $addressService) {}

    public function index(Request $request): JsonResponse
    {
        $addresses = $this->addressService->getForUser(auth()->id());

        return response()->json([
            'items' => $addresses,
            'data' => $addresses,
        ]);
    }

    public function store(AddressStoreRequest $request): JsonResponse
    {
        $address = $this->addressService->store(auth()->id(), $request->validated());

        return response()->json([
            'message' => 'Adresse ajoutée avec succès.',
            'address' => $address,
            'data' => $address,
        ], 201);
    }

    public function show(Request $request, Address $address): JsonResponse
    {
        return response()->json($address);
    }

    public function update(AddressUpdateRequest $request, Address $address): JsonResponse
    {
        $address = $this->addressService->update($address, $request->validated());

        return response()->json([
            'message' => 'Adresse mise à jour avec succès.',
            'address' => $address,
            'data' => $address,
        ]);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        $this->addressService->delete($address);

        return response()->json(['message' => 'Deleted successfully']);
    }
}
