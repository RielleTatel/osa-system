<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::view('/org', 'org.dashboard')->middleware('role:org_officer')->name('org.dashboard');
    Route::view('/moderator/queue', 'moderator.queue')->middleware('role:moderator')->name('moderator.queue');
    Route::view('/osa-admin/queue', 'osa-admin.queue')->middleware('role:osa_admin')->name('osa-admin.queue');
    Route::view('/osa-director/queue', 'osa-director.queue')->middleware('role:osa_director')->name('osa-director.queue');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
