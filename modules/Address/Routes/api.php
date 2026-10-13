<?php

use Illuminate\Support\Facades\Route;
use Modules\Address\Http\Controllers\Api\Client\AddressController;

Route::middleware('auth:api')->group(function () {
    Route::apiResource('addresses', AddressController::class);
});
