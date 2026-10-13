<?php

use Illuminate\Support\Facades\Route;
use Modules\Beneficiary\Http\Controllers\Api\Client\BeneficiaryController;

Route::middleware('auth:api')->group(function () {
    Route::apiResource('beneficiaries', BeneficiaryController::class);
});
