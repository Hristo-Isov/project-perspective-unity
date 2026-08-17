<?php

use App\Http\Controllers\StackController;
use App\Http\Controllers\KeyValueController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    Route::post('/stack', [StackController::class, 'push']);
    Route::get('/stack', [StackController::class, 'pop']);

    Route::post('/store', [KeyValueController::class, 'put']);
    Route::get('/store/{key}', [KeyValueController::class, 'get']);
    Route::delete('/store/{key}', [KeyValueController::class, 'delete']);
});