<?php

use App\Livewire\Admin\ChildrenMonitoring;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\DevTools;
use App\Livewire\Admin\FamilyManagement;
use App\Livewire\Admin\GiftDelivery;
use App\Livewire\Admin\GiftReception;
use App\Livewire\Admin\LabelGeneration;
use App\Livewire\Admin\SeasonManagement;
use App\Livewire\Admin\SendConfirmations;
use App\Livewire\Admin\SettingsManagement;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\Validation;
use App\Livewire\Family\GiftRequestForm;
use App\Livewire\Family\Home;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

// Family routes (public)
Route::get('/', Home::class)->name('home');
Route::get('/cadeau/{token}', GiftRequestForm::class)->name('gift.form');

// Admin routes
Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Dashboard - accessible to all authenticated users with any role
    Route::get('/', Dashboard::class)
        ->middleware('any.role:'.Role::ADMIN.','.Role::VALIDATOR.','.Role::ORGANIZER.','.Role::RECEPTION)
        ->name('admin.dashboard');

    // Validation - Validator or Admin
    Route::get('/validation', Validation::class)
        ->middleware('any.role:'.Role::VALIDATOR.','.Role::ADMIN)
        ->name('admin.validation');

    // Label generation - Organizer or Admin
    Route::get('/etiquettes', LabelGeneration::class)
        ->middleware('any.role:'.Role::ORGANIZER.','.Role::ADMIN)
        ->name('admin.labels');

    // Gift reception - Reception or Admin
    Route::get('/reception', GiftReception::class)
        ->middleware('any.role:'.Role::RECEPTION.','.Role::ADMIN)
        ->name('admin.reception');

    // Gift delivery - Reception or Admin
    Route::get('/remise', GiftDelivery::class)
        ->middleware('any.role:'.Role::RECEPTION.','.Role::ADMIN)
        ->name('admin.delivery');

    // Children monitoring - Organizer or Admin
    Route::get('/suivi', ChildrenMonitoring::class)
        ->middleware('any.role:'.Role::ORGANIZER.','.Role::ADMIN)
        ->name('admin.monitoring');

    // Send confirmations - Organizer or Admin
    Route::get('/confirmations', SendConfirmations::class)
        ->middleware('any.role:'.Role::ORGANIZER.','.Role::ADMIN)
        ->name('admin.confirmations');

    // Family management - Organizer or Admin
    Route::get('/familles', FamilyManagement::class)
        ->middleware('any.role:'.Role::ORGANIZER.','.Role::ADMIN)
        ->name('admin.families');

    // Admin only routes
    Route::middleware('role:'.Role::ADMIN)->group(function () {
        Route::get('/saisons', SeasonManagement::class)->name('admin.seasons');
        Route::get('/utilisateurs', UserManagement::class)->name('admin.users');
        Route::get('/parametres', SettingsManagement::class)->name('admin.settings');
        Route::get('/dev-tools', DevTools::class)->name('admin.dev-tools');
    });
});

// Redirect old dashboard route
Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
