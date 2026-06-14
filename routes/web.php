<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Webhooks\SquareController as SquareWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public booking flow
|--------------------------------------------------------------------------
*/

Route::get('/', [BookingController::class, 'services'])->name('booking.services');
Route::get('/service/{service:slug}', [BookingController::class, 'create'])->name('booking.create');
Route::post('/service/{service:slug}', [BookingController::class, 'store'])
    ->middleware('throttle:10,1')->name('booking.store');

Route::get('/api/services/{service:slug}/availability', AvailabilityController::class)
    ->name('booking.availability');

Route::get('/booking/{booking:reference}/pay', [PaymentController::class, 'show'])->name('booking.pay');
Route::post('/booking/{booking:reference}/pay', [PaymentController::class, 'redirect'])
    ->middleware('throttle:10,1')->name('booking.pay.redirect');
Route::get('/booking/{booking:reference}/return', [PaymentController::class, 'return'])
    ->name('booking.payment.return');

Route::get('/booking/{booking:reference}', [BookingController::class, 'show'])->name('booking.show');
Route::post('/booking/{booking:reference}/verify', [BookingController::class, 'verify'])
    ->middleware('throttle:10,1')->name('booking.verify');

Route::post('/webhooks/square', SquareWebhookController::class)->name('webhooks.square');

Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/privacy/collection-notice', [LegalController::class, 'collectionNotice'])->name('legal.collection-notice');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/admin/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/admin/forgot-password', [PasswordResetController::class, 'showRequestForm'])->name('password.request');
Route::post('/admin/forgot-password', [PasswordResetController::class, 'sendResetLink'])
    ->middleware('throttle:5,1')->name('password.email');
Route::get('/admin/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/admin/reset-password', [PasswordResetController::class, 'resetPassword'])
    ->middleware('throttle:5,1')->name('password.update');

Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/calendar', [Admin\CalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/events', [Admin\CalendarController::class, 'events'])->name('calendar.events');

    Route::post('/bookings', [Admin\BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}', [Admin\BookingController::class, 'show'])->name('bookings.show');
    Route::patch('/bookings/{booking}', [Admin\BookingController::class, 'update'])->name('bookings.update');

    Route::get('/services', [Admin\ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/create', [Admin\ServiceController::class, 'create'])->name('services.create');
    Route::post('/services', [Admin\ServiceController::class, 'store'])->name('services.store');
    Route::get('/services/{service:id}/edit', [Admin\ServiceController::class, 'edit'])->name('services.edit');
    Route::patch('/services/{service:id}', [Admin\ServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service:id}', [Admin\ServiceController::class, 'destroy'])->name('services.destroy');

    Route::post('/services/categories', [Admin\ServiceController::class, 'storeCategory'])->name('services.categories.store');
    Route::delete('/services/categories/{category}', [Admin\ServiceController::class, 'destroyCategory'])->name('services.categories.destroy');

    Route::get('/availability', [Admin\AvailabilityController::class, 'index'])->name('availability.index');
    Route::post('/availability/settings', [Admin\AvailabilityController::class, 'updateSettings'])->name('availability.settings');
    Route::post('/availability/rules', [Admin\AvailabilityController::class, 'storeRule'])->name('availability.rules.store');
    Route::delete('/availability/rules/{rule}', [Admin\AvailabilityController::class, 'destroyRule'])->name('availability.rules.destroy');
    Route::post('/availability/blocks', [Admin\AvailabilityController::class, 'storeBlock'])->name('availability.blocks.store');
    Route::delete('/availability/blocks/{block}', [Admin\AvailabilityController::class, 'destroyBlock'])->name('availability.blocks.destroy');

    Route::post('/availability/recurring-blocks', [Admin\AvailabilityController::class, 'storeRecurringBlock'])
        ->name('availability.recurring-blocks.store');
    Route::delete('/availability/recurring-blocks/{recurringBlock}', [Admin\AvailabilityController::class, 'destroyRecurringBlock'])
        ->name('availability.recurring-blocks.destroy');
    Route::post('/availability/recurring-blocks/lunch-preset', [Admin\AvailabilityController::class, 'addLunchPreset'])
        ->name('availability.recurring-blocks.lunch-preset');
});
