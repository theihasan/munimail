<?php

use Illuminate\Support\Facades\Route;
use Modules\SMTP\Http\Controllers\SMTPController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('smtps', SMTPController::class)->names('smtp');
});
