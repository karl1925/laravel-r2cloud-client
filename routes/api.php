<?php

use Illuminate\Support\Facades\Route;
use R2Cloud\Http\Controllers\R2WebhookController;

Route::post('/webhooks/user-updated', [R2WebhookController::class, 'handleUserUpdate']);
