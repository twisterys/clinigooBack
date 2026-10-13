<?php

use Illuminate\Support\Facades\Route;
use Modules\Professions\Http\Controllers\Api\Client\ProfessionController;
use Modules\Professions\Http\Controllers\Api\Client\ProfessionDocumentController;

Route::get('professions', [ProfessionController::class, 'index']);
Route::get('professions/{profession}', [ProfessionController::class, 'show']);
Route::get('profession-documents', [ProfessionDocumentController::class, 'index']);
Route::get('profession-documents/{document}', [ProfessionDocumentController::class, 'show']);
Route::get('professions/{profession}/documents', [ProfessionDocumentController::class, 'getDocumentsByProfession']);
Route::get('professions/{profession}/services', [ProfessionController::class, 'getProfessionServices']);

Route::group(['prefix' => 'public'], function () {
    Route::get('professions', [ProfessionController::class, 'index']);
    Route::get('professions/{profession}', [ProfessionController::class, 'show']);
    Route::get('profession-documents', [ProfessionDocumentController::class, 'index']);
    Route::get('profession-documents/{document}', [ProfessionDocumentController::class, 'show']);
    Route::get('professions/{profession}/documents', [ProfessionDocumentController::class, 'getDocumentsByProfession']);
    Route::get('professions/{profession}/services', [ProfessionController::class, 'getProfessionServices']);
});
