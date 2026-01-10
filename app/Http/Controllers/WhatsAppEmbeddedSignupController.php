<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\WhatsAppEmbeddedSignupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppEmbeddedSignupController extends Controller
{
    public function __construct(
        protected WhatsAppEmbeddedSignupService $signupService
    ) {}

    /**
     * Initiate the Embedded Signup flow
     */
    public function initiate(Business $business): JsonResponse
    {
        // Check if business is already connected
        if ($business->isWhatsAppConnected()) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp is already connected to this business.',
            ], 400);
        }

        // Generate state for CSRF protection
        $state = $this->signupService->generateState();

        // Store business ID in session for callback
        session(['whatsapp_signup_business_id' => $business->id]);

        return response()->json([
            'success' => true,
            'config' => [
                'app_id' => config('services.meta.app_id'),
                'config_id' => config('services.meta.config_id'),
                'state' => $state,
                'redirect_uri' => route('whatsapp.signup.callback'),
            ],
        ]);
    }

    /**
     * Handle the OAuth callback from Meta
     */
    public function callback(Request $request): RedirectResponse
    {
        $code = $request->input('code');
        $state = $request->input('state');

        // Verify state to prevent CSRF
        if (! $this->signupService->verifyState($state)) {
            Log::warning('Invalid state in WhatsApp signup callback', [
                'state' => $state,
            ]);

            return redirect()->route('admin.businesses.index')
                ->with('error', 'Invalid state parameter. Please try again.');
        }

        // Get business ID from session
        $businessId = session('whatsapp_signup_business_id');
        if (! $businessId) {
            Log::error('No business ID in session during WhatsApp callback');

            return redirect()->route('admin.businesses.index')
                ->with('error', 'Session expired. Please try again.');
        }

        $business = Business::find($businessId);
        if (! $business) {
            Log::error('Business not found during WhatsApp callback', [
                'business_id' => $businessId,
            ]);

            return redirect()->route('admin.businesses.index')
                ->with('error', 'Business not found.');
        }

        // Exchange code for token
        $credentials = $this->signupService->exchangeCodeForToken($code);
        if (! $credentials) {
            Log::error('Failed to exchange code for token', [
                'business_id' => $business->id,
            ]);

            return redirect()->route('admin.businesses.show', $business)
                ->with('error', 'Failed to connect WhatsApp. Please try again.');
        }

        // Store credentials
        $wabaId = $credentials['waba_id'];
        $phoneNumberId = $credentials['phone_number_id'];
        $accessToken = $credentials['access_token'];

        // If waba_id or phone_number_id are not in the token response,
        // fetch them from the Graph API
        if (! $wabaId || ! $phoneNumberId) {
            // In Meta's Embedded Signup, these details are typically provided
            // in the callback URL parameters or require a separate API call
            // For now, we'll check the request parameters
            $wabaId = $wabaId ?? $request->input('waba_id');
            $phoneNumberId = $phoneNumberId ?? $request->input('phone_number_id');
        }

        if (! $wabaId || ! $phoneNumberId) {
            Log::error('Missing waba_id or phone_number_id after token exchange', [
                'business_id' => $business->id,
                'credentials' => $credentials,
                'request_params' => $request->all(),
            ]);

            return redirect()->route('admin.businesses.show', $business)
                ->with('error', 'Incomplete WhatsApp setup. Missing WABA ID or Phone Number ID.');
        }

        // Optionally fetch display phone number from Meta API
        $displayPhoneNumber = null;
        if ($wabaId && $accessToken) {
            $wabaDetails = $this->signupService->getWhatsAppBusinessAccount($wabaId, $accessToken);
            if ($wabaDetails && isset($wabaDetails['phone_numbers'][0]['display_phone_number'])) {
                $displayPhoneNumber = $wabaDetails['phone_numbers'][0]['display_phone_number'];
            }
        }

        // Store credentials in database
        $success = $this->signupService->storeCredentials(
            $business,
            $wabaId,
            $phoneNumberId,
            $accessToken,
            $displayPhoneNumber
        );

        // Clear session
        session()->forget(['whatsapp_signup_business_id', 'whatsapp_signup_state']);

        if ($success) {
            return redirect()->route('admin.businesses.show', $business)
                ->with('success', 'WhatsApp connected successfully!');
        }

        return redirect()->route('admin.businesses.show', $business)
            ->with('error', 'Failed to save WhatsApp credentials.');
    }

    /**
     * Disconnect WhatsApp from a business
     */
    public function disconnect(Business $business): RedirectResponse
    {
        if (! $business->isWhatsAppConnected()) {
            return redirect()->route('admin.businesses.show', $business)
                ->with('error', 'WhatsApp is not connected to this business.');
        }

        $success = $this->signupService->disconnect($business);

        if ($success) {
            return redirect()->route('admin.businesses.show', $business)
                ->with('success', 'WhatsApp disconnected successfully.');
        }

        return redirect()->route('admin.businesses.show', $business)
            ->with('error', 'Failed to disconnect WhatsApp.');
    }
}
