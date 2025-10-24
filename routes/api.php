<?php

use App\Http\Controllers\Api\ExceptionController;
use Illuminate\Support\Facades\Route;

// Exception API routes
Route::post('/exceptions', [ExceptionController::class, 'store']);
Route::get('/exceptions/{hash}', [ExceptionController::class, 'show']);
