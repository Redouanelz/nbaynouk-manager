<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingPeriodController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('projects', ProjectController::class)->except('restore');
    Route::patch('/projects/{project}/notes', [ProjectController::class, 'updateNotes'])->name('projects.notes');
    Route::resource('clients', ClientController::class);
    Route::get('/clients/{client}/businesses/create', [BusinessController::class, 'create'])->name('businesses.create');
    Route::post('/businesses', [BusinessController::class, 'store'])->name('businesses.store');
    Route::get('/businesses/{business}/edit', [BusinessController::class, 'edit'])->name('businesses.edit');
    Route::put('/businesses/{business}', [BusinessController::class, 'update'])->name('businesses.update');
    Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'destroy']);
    Route::get('/billing', [BillingPeriodController::class, 'index'])->name('billing.index');
    Route::get('/projects/{project}/billing/create', [BillingPeriodController::class, 'create'])->name('billing.create');
    Route::post('/projects/{project}/billing', [BillingPeriodController::class, 'store'])->name('billing.store');
    Route::post('/projects/{project}/billing/next', [BillingPeriodController::class, 'next'])->name('billing.next');
    Route::get('/team', [TeamMemberController::class, 'index'])->name('team.index');
    Route::post('/team', [TeamMemberController::class, 'store'])->name('team.store');
    Route::get('/team/{teamMember}', [TeamMemberController::class, 'show'])->name('team.show');
    Route::put('/team/{teamMember}', [TeamMemberController::class, 'update'])->name('team.update');
    Route::patch('/team/{teamMember}/toggle', [TeamMemberController::class, 'toggle'])->name('team.toggle');
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/search', SearchController::class)->middleware('throttle:60,1')->name('search');
});
