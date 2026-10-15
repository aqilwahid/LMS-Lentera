<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LearningController;
use App\Http\Middleware\EnsureActiveUser;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/masuk');
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'create'])->name('login');
    Route::post('/masuk', [AuthController::class, 'store'])->middleware('throttle:30,1');
});
Route::post('/keluar', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware(['auth', EnsureActiveUser::class])->group(function () {
    Route::get('/beranda', [LearningController::class, 'dashboard'])->name('dashboard');
    Route::get('/kegiatan/{activity}', [LearningController::class, 'activity'])->name('activities.show');
    Route::get('/sesi/{session}/l1', [LearningController::class, 'edit'])->name('l1.edit');
    Route::post('/sesi/{session}/l1', [LearningController::class, 'store'])->middleware('throttle:30,1')->name('l1.store');
});
