<?php

use App\Http\Controllers\DocumentDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::middleware(['auth', 'approved'])->group(function () {
    Route::get('/documents/{document}/download', [DocumentDownloadController::class, 'show'])
        ->name('documents.download');

    Route::get('/students/{student}/documents/download', [DocumentDownloadController::class, 'student'])
        ->name('students.documents.download');
});
