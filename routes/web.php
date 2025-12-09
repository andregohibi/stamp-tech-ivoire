<?php

use App\Models\QrStamp;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\QrVerificationController;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/stamptechivoire/{token}/qr/verify/', [QrVerificationController::class, 'verify'])
        ->name('qr.verify');

Route::middleware(['auth'])->group(function () {
    
    // Télécharger le QR Code d'un signataire
    Route::get('signatories/{signatory}/qr/download', [DownloadController::class, 'download'])
        ->name('signatories.qr.download');

    // Prévisualiser le QR Code dans le navigateur
    Route::get('signatories/qr/{signatory}/preview', [DownloadController::class, 'preview'])
        ->name('signatories.qr.preview');

    Route::get('/signatories/qrcodes/preview/{qr}', function (QrStamp $qr) {
    return view('qr.qr-preview', [
        'qr' => $qr->load(['signatory', 'company']),
    ]);
    })->name('qr.preview');
   
});