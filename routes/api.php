<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Client\AddressController;
use App\Http\Controllers\Api\Client\BeneficiaryController;
use App\Http\Controllers\Api\Client\CareRequestController;
use App\Http\Controllers\Api\Client\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function ($router) {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');
    Route::put('password', [AuthController::class, 'updatePassword'])->middleware('auth:api');
    Route::put('preferences', [AuthController::class, 'updatePreferences'])->middleware('auth:api');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

require __DIR__.'/settings.php';

Route::middleware('auth:api')->group(function () {
    Route::resource('addresses', AddressController::class)->except('create', 'edit');

    Route::resource('beneficiaries', BeneficiaryController::class)->except('create', 'edit');

    Route::get('services/recent', [ServiceController::class, 'recent']);
    Route::resource('services', ServiceController::class)->only('index', 'show');

    Route::resource('care-requests', CareRequestController::class)->except('create', 'edit', 'destroy');
});
