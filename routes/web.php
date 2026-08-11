<?php

declare(strict_types=1);

use App\Http\Controllers\IncomingMailController;
use App\Http\Controllers\OutgoingMailController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('incoming-mails.index');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('incoming-mails', IncomingMailController::class);
    Route::resource('outgoing-mails', OutgoingMailController::class);
});
