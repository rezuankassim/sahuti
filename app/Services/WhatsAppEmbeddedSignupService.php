<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class WhatsAppEmbeddedSignupService
{
    /**
     * Generate a secure state token for CSRF protection
     */
    public function generateState(): string
    {
        $state = Str::random(40);
        Session::put('whatsapp_signup_state', $state);

        return $state;
    }

    /**
     * Verify the state token matches the session
     */
    public function verifyState(string $state): bool
    {
        $sessionState = Session::get('whatsapp_signup_state');

        if (! $sessionState || $sessionState !== $state) {
            return false;
        }

        // Clear the state after verification
        Session::forget('whatsapp_signup_state');

        return true;
    }

    /**
     * Exchange authorization code for access token with Meta Graph API
     *
     * @return array{access_token: string, waba_id: string, phone_number_id: string}|null
     */
    public function exchangeCodeForToken(string $code): ?array
    {
        $appId = config('services.meta.app_id');
        $appSecret = config('services.meta.app_secret');
        $redirectUri = route('whatsapp.signup.callback');

        try {
            // Exchange code for access token
            $response = Http::post('https://graph.facebook.com/v18.0/oauth/access_token', [
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ]);

            if (! $response->successful()) {
                Log::error('Meta token exchange failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            // The response should include access_token
            if (! isset($data['access_token'])) {
                Log::error('No access token in Meta response', ['data' => $data]);

                return null;
            }

            // Extract credentials from the response
            // Note: Meta's Embedded Signup response structure may include waba_id and phone_number_id
            // in the initial callback or require a separate API call
            return [
                'access_token' => $data['access_token'],
                'waba_id' => $data['waba_id'] ?? null,
                'phone_number_id' => $data['phone_number_id'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Exception during token exchange', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Store WhatsApp credentials for a business
     */
    public function storeCredentials(
        Business $business,
        string $wabaId,
        string $phoneNumberId,
        string $accessToken,
        ?string $displayPhoneNumber = null
    ): bool {
        try {
            $business->update([
                'waba_id' => $wabaId,
                'phone_number_id' => $phoneNumberId,
                'wa_access_token' => $accessToken,
                'display_phone_number' => $displayPhoneNumber,
                'wa_status' => 'connected',
                'connected_at' => now(),
            ]);

            Log::info('WhatsApp credentials stored', [
                'business_id' => $business->id,
                'business_name' => $business->name,
                'phone_number_id' => $phoneNumberId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to store WhatsApp credentials', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Disconnect WhatsApp account from a business
     */
    public function disconnect(Business $business): bool
    {
        try {
            $business->update([
                'wa_status' => 'disabled',
                'waba_id' => null,
                'phone_number_id' => null,
                'wa_access_token' => null,
                'display_phone_number' => null,
                'webhook_verify_token' => null,
                'connected_at' => null,
            ]);

            Log::info('WhatsApp disconnected', [
                'business_id' => $business->id,
                'business_name' => $business->name,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to disconnect WhatsApp', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get WhatsApp Business Account details from Meta API
     */
    public function getWhatsAppBusinessAccount(string $wabaId, string $accessToken): ?array
    {
        try {
            $response = Http::withToken($accessToken)
                ->get("https://graph.facebook.com/v18.0/{$wabaId}", [
                    'fields' => 'id,name,phone_numbers',
                ]);

            if (! $response->successful()) {
                Log::error('Failed to fetch WABA details', [
                    'waba_id' => $wabaId,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Exception fetching WABA details', [
                'waba_id' => $wabaId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
