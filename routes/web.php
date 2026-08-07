<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Moderator\EndorsementController;
use App\Http\Controllers\Org\ActivityRequestController;
use App\Http\Controllers\Org\DocumentUploadController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Organization officer
    Route::middleware('role:org_officer')->prefix('org')->name('org.')->group(function () {
        Route::get('/', [ActivityRequestController::class, 'index'])->name('dashboard');
        Route::get('requests/create', [ActivityRequestController::class, 'create'])->name('requests.create');
        Route::post('requests', [ActivityRequestController::class, 'store'])->name('requests.store');
        Route::get('requests/{activityRequest}', [ActivityRequestController::class, 'show'])->name('requests.show');
        Route::post('requests/{activityRequest}/documents', [DocumentUploadController::class, 'store'])->name('documents.store');
    });

    // Moderator
    Route::middleware('role:moderator')->prefix('moderator')->name('moderator.')->group(function () {
        Route::get('queue', [EndorsementController::class, 'index'])->name('queue');
        Route::get('requests/{activityRequest}', [EndorsementController::class, 'show'])->name('requests.show');
        Route::post('requests/{activityRequest}/decision', [EndorsementController::class, 'decide'])->name('requests.decide');
    });

    Route::view('/osa-admin/queue', 'osa-admin.queue')->middleware('role:osa_admin')->name('osa-admin.queue');
    Route::view('/osa-director/queue', 'osa-director.queue')->middleware('role:osa_director')->name('osa-director.queue');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
