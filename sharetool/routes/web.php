<?php

use App\Http\Controllers\ShareController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Volt::route('shares', 'pages.share-overview')
    ->middleware(['auth', 'verified'])
    ->name('share.overview');

Volt::route('share/{shareId}', 'pages.share-details')
    ->middleware(['auth', 'verified'])
    ->name('share.details');

Volt::route('share/{shareId}/login', 'pages.share-login')
    ->middleware(['auth', 'verified'])
    ->name('share.login');

Route::get('share/{shareId}/file/{fileId}/download', [ShareController::class, 'downloadFile'])
    ->middleware(['auth', 'verified'])
    ->name('share.file.download');

Volt::route('share/{shareId}/{publicToken}', 'pages.share-details')
    ->name('publicShare.details');

Volt::route('share/{shareId}/{publicToken}/login', 'pages.share-login')
    ->name('publicShare.login');

Route::get('public-share/{shareId}/{publicToken}/file/{fileId}/download', [ShareController::class, 'downloadFilePublic'])
    ->name('publicShare.file.download');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
