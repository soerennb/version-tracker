<?php

use App\Http\Controllers\Public\DownloadController;
use App\Http\Controllers\PublicSecurityController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/security', [PublicSecurityController::class, 'index'])->name('public.security');
Route::get('/downloads/{version}/{fileAttachment}', DownloadController::class)
    ->middleware('throttle:api')
    ->name('public.download');

Route::view('/{any}', 'welcome')->where('any', '^(?!admin|api).*$');
