<?php

use Illuminate\Support\Facades\Route;

Route::post('/webhooks/user-updated', [\App\Http\Controllers\R2WebhookController::class, 'handleUserUpdate']);
