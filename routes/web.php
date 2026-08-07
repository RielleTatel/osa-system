<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Moderator\EndorsementController;
use App\Http\Controllers\Moderator\OsaForm3ApprovalController;
use App\Http\Controllers\Org\ActivityRequestController;
use App\Http\Controllers\Org\DocumentUploadController;
use App\Http\Controllers\Org\OsaForm3Controller;
use App\Http\Controllers\OsaAdmin\ChecklistController;
use App\Http\Controllers\OsaAdmin\ReviewQueueController;
use App\Http\Controllers\OsaDirector\NotationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReferenceSlipController;
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
        Route::get('requests/{activityRequest}/osa-form-3', [OsaForm3Controller::class, 'create'])->name('osa-form-3.create');
        Route::post('requests/{activityRequest}/osa-form-3', [OsaForm3Controller::class, 'store'])->name('osa-form-3.store');
    });

    // Moderator
    Route::middleware('role:moderator')->prefix('moderator')->name('moderator.')->group(function () {
        Route::get('queue', [EndorsementController::class, 'index'])->name('queue');
        Route::get('requests/{activityRequest}', [EndorsementController::class, 'show'])->name('requests.show');
        Route::post('requests/{activityRequest}/decision', [EndorsementController::class, 'decide'])->name('requests.decide');
        Route::get('osa-form-3', [OsaForm3ApprovalController::class, 'index'])->name('osa-form-3.index');
        Route::get('osa-form-3/{osaForm3}', [OsaForm3ApprovalController::class, 'show'])->name('osa-form-3.show');
        Route::post('osa-form-3/{osaForm3}/decision', [OsaForm3ApprovalController::class, 'decide'])->name('osa-form-3.decide');
    });

    // OSA Admin
    Route::middleware('role:osa_admin')->prefix('osa-admin')->name('osa-admin.')->group(function () {
        Route::get('queue', [ReviewQueueController::class, 'index'])->name('queue');
        Route::get('requests/{activityRequest}', [ReviewQueueController::class, 'show'])->name('requests.show');
        Route::post('requests/{activityRequest}/start-review', [ReviewQueueController::class, 'startReview'])->name('requests.start-review');
        Route::post('requests/{activityRequest}/decision', [ReviewQueueController::class, 'decide'])->name('requests.decide');
        Route::post('requests/{activityRequest}/nudge', [ReviewQueueController::class, 'nudge'])->name('requests.nudge');
        Route::post('requests/{activityRequest}/approve', [ReviewQueueController::class, 'approve'])->name('requests.approve');
        Route::patch('checklist/{checklistItem}', [ChecklistController::class, 'update'])->name('checklist.update');
        Route::post('requests/{activityRequest}/slip', [ReferenceSlipController::class, 'generate'])->name('slip.generate');
        Route::post('slips/{referenceSlip}/claim', [ReferenceSlipController::class, 'claim'])->name('slip.claim');
    });

    // OSA Director
    Route::middleware('role:osa_director')->prefix('osa-director')->name('osa-director.')->group(function () {
        Route::get('queue', [NotationController::class, 'index'])->name('queue');
        Route::get('requests/{activityRequest}', [NotationController::class, 'show'])->name('requests.show');
        Route::post('requests/{activityRequest}/decision', [NotationController::class, 'decide'])->name('requests.decide');
    });

    // Shared authenticated PDF downloads (authorized per request in the controller)
    Route::get('requests/{activityRequest}/slip.pdf', [ReferenceSlipController::class, 'slipPdf'])->name('slip.pdf');
    Route::get('osa-form-3/{osaForm3}.pdf', [ReferenceSlipController::class, 'osaForm3Pdf'])->name('osa-form-3.pdf');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
