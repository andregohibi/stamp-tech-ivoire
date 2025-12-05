<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DownloadController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    
    // Télécharger le QR Code d'un signataire
    Route::get('signatories/{signatory}/qr/download', [DownloadController::class, 'download'])
        ->name('signatories.qr.download');

    // Prévisualiser le QR Code dans le navigateur
    Route::get('signatories/{signatory}/qr/preview', [DownloadController::class, 'preview'])
        ->name('signatories.qr.preview');
});