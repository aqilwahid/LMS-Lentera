<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassAccessController;
use App\Http\Controllers\GuestClassController;
use App\Http\Controllers\LearningController;
use App\Http\Middleware\EnsureActiveUser;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::get('/kelas/{token}', [GuestClassController::class, 'create'])->name('class.join');
Route::post('/kelas/{token}', [GuestClassController::class, 'store'])->middleware('throttle:class-forms')->name('class.register');
Route::get('/kelas/{token}/kegiatan', [GuestClassController::class, 'show'])->name('class.show');
Route::get('/kelas/{token}/l1', [GuestClassController::class, 'edit'])->name('class.l1.edit');
Route::post('/kelas/{token}/l1', [GuestClassController::class, 'submit'])->middleware('throttle:class-forms')->name('class.l1.store');
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'create'])->name('login');
    Route::post('/masuk', [AuthController::class, 'store'])->middleware('throttle:30,1');
});
Route::post('/keluar', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware(['auth', EnsureActiveUser::class])->group(function () {
    Route::get('/pengelola/sesi/{session}/akses', [ClassAccessController::class, 'show'])->name('class.access');
    Route::get('/pengelola/sesi/{session}/qr', [ClassAccessController::class, 'qr'])->name('class.qr');
    Route::get('/beranda', [LearningController::class, 'dashboard'])->name('dashboard');
    Route::get('/kegiatan/{activity}', [LearningController::class, 'activity'])->name('activities.show');
    Route::get('/sesi/{session}/l1', [LearningController::class, 'edit'])->name('l1.edit');
    Route::post('/sesi/{session}/l1', [LearningController::class, 'store'])->middleware('throttle:30,1')->name('l1.store');
});
