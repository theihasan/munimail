<?php

use Illuminate\Support\Facades\Route;
use Modules\SMTP\Http\Controllers\SMTPController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('smtps', SMTPController::class)->names('smtp');
});
