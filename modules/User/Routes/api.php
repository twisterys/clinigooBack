<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\Admin\AuthController as AdminAuthController;
use Modules\User\Http\Controllers\Admin\ProfessionalController as AdminProfessionalController;
use Modules\User\Http\Controllers\Api\AuthController;
use Modules\User\Http\Controllers\Api\ProfessionalController;

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
    Route::get('profile', [AuthController::class, 'profile'])->middleware('auth:api');
    Route::put('profile', [AuthController::class, 'updateProfile'])->middleware('auth:api');
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'admin/auth',
], function ($router) {
    Route::post('login', [AdminAuthController::class, 'login']);
    Route::post('logout', [AdminAuthController::class, 'logout'])->middleware('auth:admin');
    Route::post('refresh', [AdminAuthController::class, 'refresh']);
    Route::get('me', [AdminAuthController::class, 'me'])->middleware('auth:admin');
    Route::get('profile', [AdminAuthController::class, 'profile'])->middleware('auth:admin');
    Route::put('profile', [AdminAuthController::class, 'updateProfile'])->middleware('auth:admin');
    Route::put('password', [AdminAuthController::class, 'updatePassword'])->middleware('auth:admin');
});

// Public professional registration and verification status (used by web frontend)
Route::post('public/professionals', [ProfessionalController::class, 'publicRegister']);
Route::get('public/professionals/{id}', [ProfessionalController::class, 'publicShow']);
Route::post('professionals', [ProfessionalController::class, 'publicRegister']);
Route::get('professionals/{id}', [ProfessionalController::class, 'publicShow']);

Route::group([
    'middleware' => ['api', 'auth:api'],
    'prefix' => 'professional',
], function () {
    Route::get('me', [ProfessionalController::class, 'dossier']);
    Route::get('dossier', [ProfessionalController::class, 'dossier']);
    Route::put('profile', [ProfessionalController::class, 'updateProfile']);
    Route::put('sensitive-info', [ProfessionalController::class, 'updateSensitiveInfo']);
    Route::put('availability', [ProfessionalController::class, 'updateAvailability']);
    Route::post('documents', [ProfessionalController::class, 'uploadDocument']);
    Route::get('documents/{document}/download', [ProfessionalController::class, 'downloadDocument']);
    Route::post('submit', [ProfessionalController::class, 'submit']);
});

Route::group([
    'middleware' => ['api', 'auth:admin'],
    'prefix' => 'admin/professionals',
], function () {
    Route::get('/', [AdminProfessionalController::class, 'index']);
    Route::get('{id}', [AdminProfessionalController::class, 'show']);
    Route::post('{id}/documents/{documentId}/verify', [AdminProfessionalController::class, 'verifyDocument']);
    Route::post('{id}/verify', [AdminProfessionalController::class, 'verifyDossier']);
    Route::post('{id}/review-changes', [AdminProfessionalController::class, 'reviewSensitiveChanges']);
    Route::put('{id}/services', [AdminProfessionalController::class, 'updateServices']);
    Route::put('{id}/password', [AdminProfessionalController::class, 'updatePassword']);
    Route::get('{id}/history', [AdminProfessionalController::class, 'history']);
    Route::get('{id}/documents/{documentId}/download', [AdminProfessionalController::class, 'downloadDocument']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
