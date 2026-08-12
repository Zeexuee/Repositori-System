<?php

declare(strict_types=1);

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\IncomingMailController;
use App\Http\Controllers\MailDispositionController;
use App\Http\Controllers\OutgoingMailController;
use App\Http\Controllers\RepositoryController;
use Illuminate\Support\Facades\Route;

// Autentikasi
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/quick-login/{email}', [AuthController::class, 'quickLogin'])->name('quick-login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', function () {
    return redirect()->route('incoming-mails.index');
});

Route::middleware(['auth'])->group(function () {
    // Unduh Dokumen Internal (Disk Local)
    Route::get('/download-document', [DocumentDownloadController::class, 'download'])->name('document.download');

    // Repositori Dokumen (Card Per Bulan)
    Route::get('/repository', [RepositoryController::class, 'index'])->name('repository.index');

    // Disposisi Surat Masuk
    Route::post('/incoming-mails/{incomingMail}/dispositions', [MailDispositionController::class, 'store'])->name('incoming-mails.dispositions.store');
    Route::resource('incoming-mails', IncomingMailController::class);

    // Tanda Tangan Digital Surat Keluar
    Route::post('/outgoing-mails/{outgoingMail}/sign', [OutgoingMailController::class, 'sign'])->name('outgoing-mails.sign');
    Route::resource('outgoing-mails', OutgoingMailController::class);

    // Jejak Audit
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
});


