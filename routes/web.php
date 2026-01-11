<?php

use App\Http\Controllers\Admin\BusinessController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Privacy Policy
Route::get('/privacy-policy', [PrivacyPolicyController::class, 'show'])->name('privacy-policy');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Admin routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('businesses', [BusinessController::class, 'index'])->name('businesses.index');
        Route::get('businesses/{business}', [BusinessController::class, 'show'])->name('businesses.show');

        // WhatsApp Configuration routes
        Route::patch('businesses/{business}/whatsapp', [BusinessController::class, 'updateWhatsApp'])
            ->name('businesses.whatsapp.update');
        Route::post('businesses/{business}/whatsapp/reset-onboarding', [BusinessController::class, 'resetOnboarding'])
            ->name('businesses.whatsapp.reset-onboarding');
    });
});

// WhatsApp Webhook Routes
Route::get('/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('whatsapp.webhook.verify');
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle'])->name('whatsapp.webhook.handle');

require __DIR__.'/settings.php';
