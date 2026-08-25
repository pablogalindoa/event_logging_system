<?php

use App\Http\Controllers\EventController;
use App\Http\Middleware\ValidateEventJson;
use Illuminate\Support\Facades\Route;

Route::post('/events', [EventController::class, 'store'])
    ->middleware(ValidateEventJson::class);

Route::get('/events', [EventController::class, 'index']);
