<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\Log;

class TenantRouterService
{
    /**
     * Get business by WhatsApp phone_number_id from webhook
     */
    public function getBusinessByPhoneNumberId(string $phoneNumberId): ?Business
    {
        $business = Business::where('phone_number_id', $phoneNumberId)
            ->where('wa_status', 'connected')
            ->first();

        if (! $business) {
            Log::warning('Unknown phone_number_id in webhook', [
                'phone_number_id' => $phoneNumberId,
            ]);
        }

        return $business;
    }

    /**
     * Get business ID by phone_number_id
     */
    public function getBusinessId(string $phoneNumberId): ?int
    {
        return $this->getBusinessByPhoneNumberId($phoneNumberId)?->id;
    }

    /**
     * Get business by phone_number_id or fallback to first onboarded business (single-tenant)
     */
    public function getBusinessByPhoneNumberIdWithFallback(string $phoneNumberId): ?Business
    {
        // Try multi-tenant lookup first
        $business = $this->getBusinessByPhoneNumberId($phoneNumberId);

        if ($business) {
            return $business;
        }

        // Fallback for single-tenant: get first onboarded business
        // This ensures backward compatibility
        $fallbackBusiness = Business::where('is_onboarded', true)->first();

        if ($fallbackBusiness) {
            Log::info('Using fallback business for single-tenant mode', [
                'phone_number_id' => $phoneNumberId,
                'business_id' => $fallbackBusiness->id,
            ]);
        }

        return $fallbackBusiness;
    }
}
