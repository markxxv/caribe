<?php

use App\Http\Controllers\Api\RealestateImportController;
use Illuminate\Support\Facades\Route;

Route::post('/realestate/import', RealestateImportController::class)
    ->middleware('throttle:20');
