<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppController extends Controller
{
    /**
     * Show WhatsApp configuration page
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        // Get or create business for this user
        $business = Business::firstOrCreate(
            ['phone_number' => $user->phone ?? 'pending'],
            [
                'name' => 'My Business',
                'wa_status' => 'pending_connect',
            ]
        );

        return Inertia::render('settings/whatsapp', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'phone_number' => $business->phone_number,
                'display_phone_number' => $business->display_phone_number,
                'wa_status' => $business->wa_status,
                'is_onboarded' => $business->is_onboarded,
                'onboarding_phone' => $business->onboarding_phone,
                'meta_app_id' => $business->meta_app_id,
                'webhook_verify_token' => $business->webhook_verify_token,
                'phone_number_id' => $business->phone_number_id,
                'waba_id' => $business->waba_id,
                'connected_at' => $business->connected_at,
            ],
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update WhatsApp configuration
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $business = Business::firstOrCreate(
            ['phone_number' => $user->phone ?? 'pending'],
            [
                'name' => 'My Business',
                'wa_status' => 'pending_connect',
            ]
        );

        $validated = $request->validate([
            'meta_app_id' => ['required', 'string', 'max:255'],
            'meta_app_secret' => ['required', 'string', 'max:1000'],
            'phone_number_id' => ['required', 'string', 'max:255', Rule::unique('businesses', 'phone_number_id')->ignore($business->id)],
            'wa_access_token' => ['required', 'string', 'max:1000'],
            'webhook_verify_token' => ['required', 'string', 'max:255'],
            'waba_id' => ['nullable', 'string', 'max:255'],
            'display_phone_number' => ['nullable', 'string', 'max:255'],
        ]);

        $business->update([
            'meta_app_id' => $validated['meta_app_id'],
            'meta_app_secret' => $validated['meta_app_secret'],
            'phone_number_id' => $validated['phone_number_id'],
            'wa_access_token' => $validated['wa_access_token'],
            'webhook_verify_token' => $validated['webhook_verify_token'],
            'waba_id' => $validated['waba_id'] ?? null,
            'display_phone_number' => $validated['display_phone_number'] ?? null,
            'wa_status' => 'connected',
            'connected_at' => $business->connected_at ?? now(),
        ]);

        Log::info('WhatsApp configuration updated', [
            'business_id' => $business->id,
            'user_id' => $user->id,
        ]);

        return to_route('whatsapp.edit')->with('status', 'whatsapp-updated');
    }

    /**
     * Reset onboarding lock
     */
    public function resetOnboarding(Request $request): RedirectResponse
    {
        $user = $request->user();

        $business = Business::where('phone_number', $user->phone ?? 'pending')->first();

        if (! $business) {
            return back()->withErrors(['error' => 'Business not found']);
        }

        $business->update([
            'onboarding_phone' => null,
            'is_onboarded' => false,
        ]);

        Log::info('Onboarding lock reset', [
            'business_id' => $business->id,
            'user_id' => $user->id,
        ]);

        return to_route('whatsapp.edit')->with('status', 'onboarding-reset');
    }
}
