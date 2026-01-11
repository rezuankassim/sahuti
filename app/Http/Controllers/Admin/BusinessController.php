<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    /**
     * Display a listing of businesses
     */
    public function index(): Response
    {
        $businesses = Business::orderBy('created_at', 'desc')
            ->get()
            ->map(function ($business) {
                return [
                    'id' => $business->id,
                    'name' => $business->name,
                    'phone_number' => $business->phone_number,
                    'display_phone_number' => $business->display_phone_number,
                    'wa_status' => $business->wa_status ?? 'pending_connect',
                    'is_onboarded' => $business->is_onboarded,
                    'connected_at' => $business->connected_at?->format('Y-m-d H:i:s'),
                    'created_at' => $business->created_at->format('Y-m-d H:i:s'),
                    'is_connected' => $business->isWhatsAppConnected(),
                ];
            });

        return Inertia::render('admin/businesses/index', [
            'businesses' => $businesses,
        ]);
    }

    /**
     * Display the specified business
     */
    public function show(Business $business): Response
    {
        return Inertia::render('admin/businesses/show', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'phone_number' => $business->phone_number,
                'services' => $business->services,
                'areas' => $business->areas,
                'operating_hours' => $business->operating_hours,
                'booking_method' => $business->booking_method,
                'is_onboarded' => $business->is_onboarded,
                'llm_enabled' => $business->llm_enabled,
                'waba_id' => $business->waba_id,
                'phone_number_id' => $business->phone_number_id,
                'display_phone_number' => $business->display_phone_number,
                'wa_status' => $business->wa_status ?? 'pending_connect',
                'connected_at' => $business->connected_at?->format('Y-m-d H:i:s'),
                'created_at' => $business->created_at->format('Y-m-d H:i:s'),
                'is_connected' => $business->isWhatsAppConnected(),
                'can_send_messages' => $business->canSendMessages(),
                'onboarding_phone' => $business->onboarding_phone,
                'meta_app_id' => $business->meta_app_id,
                'webhook_verify_token' => $business->webhook_verify_token,
            ],
        ]);
    }

    /**
     * Update WhatsApp configuration for a business
     */
    public function updateWhatsApp(Request $request, Business $business): RedirectResponse
    {
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

        Log::info('WhatsApp configuration updated for business', [
            'business_id' => $business->id,
        ]);

        return back()->with('success', 'WhatsApp configuration updated successfully');
    }

    /**
     * Reset onboarding lock for a business
     */
    public function resetOnboarding(Business $business): RedirectResponse
    {
        $business->update([
            'onboarding_phone' => null,
            'is_onboarded' => false,
        ]);

        Log::info('Onboarding lock reset for business', [
            'business_id' => $business->id,
        ]);

        return back()->with('success', 'Onboarding lock reset successfully');
    }
}
