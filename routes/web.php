<?php

use App\Http\Controllers\Admin\BusinessController;
use App\Http\Controllers\WhatsAppEmbeddedSignupController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Admin routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('businesses', [BusinessController::class, 'index'])->name('businesses.index');
        Route::get('businesses/{business}', [BusinessController::class, 'show'])->name('businesses.show');

        // WhatsApp Embedded Signup routes
        Route::post('businesses/{business}/whatsapp/initiate', [WhatsAppEmbeddedSignupController::class, 'initiate'])
            ->name('businesses.whatsapp.initiate');
        Route::post('businesses/{business}/whatsapp/disconnect', [WhatsAppEmbeddedSignupController::class, 'disconnect'])
            ->name('businesses.whatsapp.disconnect');
    });
});

// WhatsApp Webhook Routes
Route::get('/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('whatsapp.webhook.verify');
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle'])->name('whatsapp.webhook.handle');

// WhatsApp Embedded Signup Callback
Route::get('/whatsapp/signup/callback', [WhatsAppEmbeddedSignupController::class, 'callback'])
    ->name('whatsapp.signup.callback');

require __DIR__.'/settings.php';
