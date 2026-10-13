<?php

use Illuminate\Support\Facades\Route;
use Modules\Service\Http\Controllers\Api\Client\ServiceCategoryController;
use Modules\Service\Http\Controllers\Api\Client\ServiceController;

Route::middleware('auth:api')->group(function () {
    Route::apiResource('service-categories', ServiceCategoryController::class)->only('index', 'show');
    Route::get('services/recent', [ServiceController::class, 'recent']);
    Route::apiResource('services', ServiceController::class)->only('index', 'show');
});
