<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'name',
        'services',
        'areas',
        'operating_hours',
        'booking_method',
        'profile_data',
        'is_onboarded',
        'llm_enabled',
        'waba_id',
        'phone_number_id',
        'display_phone_number',
        'wa_access_token',
        'webhook_verify_token',
        'wa_status',
        'connected_at',
        'meta_app_id',
        'meta_app_secret',
        'onboarding_phone',
    ];

    protected $casts = [
        'services' => 'array',
        'areas' => 'array',
        'operating_hours' => 'array',
        'profile_data' => 'array',
        'is_onboarded' => 'boolean',
        'wa_access_token' => 'encrypted',
        'meta_app_secret' => 'encrypted',
        'connected_at' => 'datetime',
    ];

    /**
     * Check if WhatsApp is connected and ready
     */
    public function isWhatsAppConnected(): bool
    {
        return $this->wa_status === 'connected'
            && ! empty($this->phone_number_id)
            && ! empty($this->wa_access_token);
    }

    /**
     * Check if business can send WhatsApp messages
     */
    public function canSendMessages(): bool
    {
        return $this->isWhatsAppConnected() && $this->is_onboarded;
    }

    /**
     * Get WhatsApp access token (with fallback to global config)
     */
    public function getWhatsAppAccessToken(): ?string
    {
        return $this->wa_access_token ?? config('services.whatsapp.access_token');
    }

    /**
     * Get WhatsApp phone number ID (with fallback to global config)
     */
    public function getWhatsAppPhoneNumberId(): ?string
    {
        return $this->phone_number_id ?? config('services.whatsapp.phone_number_id');
    }

    /**
     * Get Meta app secret (with fallback to global config)
     */
    public function getMetaAppSecret(): ?string
    {
        return $this->meta_app_secret ?? config('services.meta.app_secret');
    }

    /**
     * Check if onboarding is locked to a specific phone number
     */
    public function isOnboardingLocked(): bool
    {
        return ! empty($this->onboarding_phone);
    }

    /**
     * Check if phone number can perform onboarding
     */
    public function canOnboard(string $phoneNumber): bool
    {
        return ! $this->isOnboardingLocked() || $this->onboarding_phone === $phoneNumber;
    }
}
