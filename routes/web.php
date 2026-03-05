<?php

use App\Http\Controllers\Admin\BackgroundController;
use App\Http\Controllers\FrameController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrameController::class, 'index'])->name('frame.index');
Route::post('/generate', [FrameController::class, 'generate'])->name('frame.generate');
Route::get('/background-preview', [FrameController::class, 'backgroundPreview'])->name('background.preview');

Route::middleware('auth')->get('/dashboard', fn () => to_route('admin.background.edit'))->name('dashboard');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', fn () => to_route('admin.background.edit'))->name('index');
    Route::get('/background', [BackgroundController::class, 'edit'])->name('background.edit');
    Route::post('/background', [BackgroundController::class, 'update'])->name('background.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
