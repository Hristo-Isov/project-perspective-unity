<?php

use App\Http\Controllers\StackController;
use App\Http\Controllers\KeyValueController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    Route::post('/stack', [StackController::class, 'store']);
    Route::delete('/stack', [StackController::class, 'destroy']);

    Route::post('/store', [KeyValueController::class, 'store']);
    Route::get('/store/{key}', [KeyValueController::class, 'show']);
    Route::delete('/store/{key}', [KeyValueController::class, 'destroy']);
});