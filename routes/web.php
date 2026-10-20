<?php

declare(strict_types=1);

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BroadcastEmailController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\IncomingMailController;
use App\Http\Controllers\IncomingMailDispositionController;
use App\Http\Controllers\IncomingMailReturnController;
use App\Http\Controllers\MailDispositionController;
use App\Http\Controllers\OutgoingMailController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Autentikasi
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
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
    Route::get('/incoming-mails/{incomingMail}/disposition-recipients', [IncomingMailDispositionController::class, 'show'])->name('incoming-mails.disposition-recipients.show');
    Route::post('/incoming-mails/disposition-workflow', [IncomingMailDispositionController::class, 'store'])->name('incoming-mails.disposition-workflow.store');
    Route::post('/incoming-mails/{incomingMail}/dispositions', [MailDispositionController::class, 'store'])->name('incoming-mails.dispositions.store');
    Route::post('/incoming-mails/bulk-update-status', [IncomingMailController::class, 'bulkUpdateStatus'])->name('incoming-mails.bulk-update-status');
    Route::get('/incoming-mails/{incomingMail}/return-recipients', [IncomingMailReturnController::class, 'show'])->name('incoming-mails.return-recipients.show');
    Route::post('/incoming-mails/return-workflow', [IncomingMailReturnController::class, 'store'])->name('incoming-mails.return-workflow.store');
    Route::put('/incoming-mails/batch-update/{batch_id}', [IncomingMailController::class, 'updateBatch'])->name('incoming-mails.batch-update');
    Route::get('/incoming-mails/{incomingMail}/attachments', [IncomingMailController::class, 'getAttachments'])->name('incoming-mails.attachments.index');
    Route::post('/incoming-mails/{incomingMail}/attachments', [IncomingMailController::class, 'storeAttachments'])->name('incoming-mails.attachments.store');
    Route::delete('/incoming-mails/{incomingMail}/attachments/{attachment}', [IncomingMailController::class, 'destroyAttachment'])->name('incoming-mails.attachments.destroy');
    Route::resource('incoming-mails', IncomingMailController::class);

    // Alur Status & Penerimaan Surat Keluar
    Route::post('/outgoing-mails/bulk-update-status', [OutgoingMailController::class, 'bulkUpdateStatus'])->name('outgoing-mails.bulk-update-status');
    Route::get('/outgoing-mails/{outgoingMail}/return-recipients', [\App\Http\Controllers\OutgoingMailReturnController::class, 'show'])->name('outgoing-mails.return-recipients.show');
    Route::post('/outgoing-mails/return-workflow', [\App\Http\Controllers\OutgoingMailReturnController::class, 'store'])->name('outgoing-mails.return-workflow.store');
    Route::resource('outgoing-mails', OutgoingMailController::class);

    // Jejak Audit
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
    Route::get('/audit-logs/export-trash', [AuditLogController::class, 'exportTrash'])->name('audit-logs.export-trash');

    // Profil Pengguna & Pengaturan Akun
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile/name', [ProfileController::class, 'updateName'])->name('profile.update-name');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
    // Broadcast Email (Khusus Direksi)
    Route::get('/broadcast-emails', [BroadcastEmailController::class, 'index'])->name('broadcast-emails.index');
    Route::get('/broadcast-emails/history', [BroadcastEmailController::class, 'history'])->name('broadcast-emails.history');
    Route::post('/broadcast-emails/send', [BroadcastEmailController::class, 'send'])->name('broadcast-emails.send');
    Route::get('/broadcast-emails/{broadcast}/attachments/{index}', [BroadcastEmailController::class, 'downloadAttachment'])->name('broadcast-emails.download-attachment');

    // Manajemen User (Khusus Direksi)
    Route::resource('users', UserController::class);
});
