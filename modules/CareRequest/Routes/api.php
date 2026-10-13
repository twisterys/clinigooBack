<?php

use Illuminate\Support\Facades\Route;
use Modules\CareRequest\Http\Controllers\Api\Client\CareRequestController;

Route::middleware('auth:api')->group(function () {
    Route::apiResource('care-requests', CareRequestController::class)->except('destroy');
});
