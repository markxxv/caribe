<?php

use App\Http\Controllers\Api\RealestateImportController;
use App\Http\Controllers\Api\RealestatePhotoController;
use Illuminate\Support\Facades\Route;

Route::post('/realestate/import', RealestateImportController::class)
    ->middleware('throttle:20');

Route::post('/realestate/import/photo', RealestatePhotoController::class)
    ->middleware('throttle:120');
