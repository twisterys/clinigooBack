<?php

use Illuminate\Support\Facades\Route;
use Modules\Service\Http\Controllers\Api\Admin\ServiceCategoryController;
use Modules\Service\Http\Controllers\Api\Admin\ServiceController;

Route::group(['middleware' => ['auth:admin', 'api'], 'prefix' => '/services'], function () {
    Route::get('/categories', [ServiceCategoryController::class, 'index']);
    Route::post('/categories', [ServiceCategoryController::class, 'store']);
    Route::put('/categories/{id}', [ServiceCategoryController::class, 'update']);
    Route::delete('/categories/{id}', [ServiceCategoryController::class, 'destroy']);

    Route::get('/', [ServiceController::class, 'index']);
    Route::post('/', [ServiceController::class, 'store']);
    Route::get('/{id}', [ServiceController::class, 'show']);
    Route::put('/{id}', [ServiceController::class, 'update']);
    Route::delete('/{id}', [ServiceController::class, 'destroy']);
});
