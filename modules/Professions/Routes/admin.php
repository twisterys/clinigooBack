<?php

use Illuminate\Support\Facades\Route;
use Modules\Professions\Http\Controllers\Api\Admin\ProfessionController;
use Modules\Professions\Http\Controllers\Api\Admin\ProfessionDocumentController;

Route::group(['middleware' => ['auth:admin', 'api']], function () {
    // Profession Documents
    Route::get('profession-documents', [ProfessionDocumentController::class, 'index']);
    Route::post('profession-documents', [ProfessionDocumentController::class, 'store']);
    Route::get('profession-documents/{document}', [ProfessionDocumentController::class, 'show']);
    Route::put('profession-documents/{document}', [ProfessionDocumentController::class, 'update']);
    Route::delete('profession-documents/{document}', [ProfessionDocumentController::class, 'destroy']);

    // Professions
    Route::get('professions', [ProfessionController::class, 'index']);
    Route::post('professions', [ProfessionController::class, 'store']);
    Route::get('professions/{profession}', [ProfessionController::class, 'show']);
    Route::put('professions/{profession}', [ProfessionController::class, 'update']);
    Route::delete('professions/{profession}', [ProfessionController::class, 'destroy']);
});
