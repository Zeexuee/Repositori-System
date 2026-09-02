<?php

declare(strict_types=1);

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BetaFeedbackController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\IncomingMailController;
use App\Http\Controllers\MailDispositionController;
use App\Http\Controllers\OutgoingMailController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\UserController;
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

    // Disposisi Surat Masuk & Revisi
    Route::post('/incoming-mails/{incomingMail}/revisi', [IncomingMailController::class, 'submitRevision'])->name('incoming-mails.submit-revision');
    Route::post('/incoming-mails/{incomingMail}/dispositions', [MailDispositionController::class, 'store'])->name('incoming-mails.dispositions.store');
    Route::resource('incoming-mails', IncomingMailController::class);

    // Tanda Tangan Digital & Dokumen Menunggu (Waiting) Surat Keluar
    Route::get('/outgoing-mails/waiting', [OutgoingMailController::class, 'waiting'])->name('outgoing-mails.waiting');
    Route::post('/outgoing-mails/{outgoingMail}/sign-disposition', [OutgoingMailController::class, 'signDisposition'])->name('outgoing-mails.sign-disposition');
    Route::post('/outgoing-mails/{outgoingMail}/sign', [OutgoingMailController::class, 'sign'])->name('outgoing-mails.sign');
    Route::resource('outgoing-mails', OutgoingMailController::class);

    // Jejak Audit
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
    Route::get('/audit-logs/export-trash', [AuditLogController::class, 'exportTrash'])->name('audit-logs.export-trash');

    // Profil Pengguna & Pengaturan Akun
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile/name', [\App\Http\Controllers\ProfileController::class, 'updateName'])->name('profile.update-name');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.update-password');
    // Broadcast Email (Khusus Direksi)
    Route::get('/broadcast-emails', [\App\Http\Controllers\BroadcastEmailController::class, 'index'])->name('broadcast-emails.index');
    Route::get('/broadcast-emails/history', [\App\Http\Controllers\BroadcastEmailController::class, 'history'])->name('broadcast-emails.history');
    Route::post('/broadcast-emails/send', [\App\Http\Controllers\BroadcastEmailController::class, 'send'])->name('broadcast-emails.send');
    Route::get('/broadcast-emails/{broadcast}/attachments/{index}', [\App\Http\Controllers\BroadcastEmailController::class, 'downloadAttachment'])->name('broadcast-emails.download-attachment');

    // Manajemen User (Khusus Direksi)
    Route::resource('users', UserController::class);

    // Open Beta Feedback & Bug Reporting
    Route::post('/beta-feedback', [BetaFeedbackController::class, 'store'])->name('beta-feedback.store');
    Route::get('/beta-feedback/history', [BetaFeedbackController::class, 'history'])->name('beta-feedback.history');
    Route::put('/beta-feedback/{feedback}/status', [BetaFeedbackController::class, 'updateStatus'])->name('beta-feedback.update-status');
});


