<?php

namespace App\Services;

use App\Models\Business;
use Carbon\Carbon;

class AutoReplyService
{
    protected array $keywordMap = [
        'PRICE' => ['harga', 'price', 'berapa', 'cost'],
        'AREA' => ['area', 'kawasan', 'location', 'lokasi'],
        'HOURS' => ['hours', 'time', 'bila', 'when', 'operating', 'open'],
        'BOOK' => ['book', 'tempah', 'appointment', 'booking'],
    ];

    /**
     * Generate auto-reply based on customer message
     */
    public function generateReply(string $message, Business $business): ?string
    {
        // Check if business is outside operating hours
        if ($this->isAfterHours($business)) {
            return $this->generateAfterHoursReply($business);
        }

        // Detect intents from message
        $detectedIntents = $this->detectIntents($message);

        if (empty($detectedIntents)) {
            // No recognized intent, send generic greeting
            return "Hello! How can we help you today?\n\nYou can ask about:\n• Our services and prices\n• Areas we cover\n• Operating hours\n• How to book";
        }

        // Bundle replies (max 3 sections)
        return $this->bundleReplies($detectedIntents, $business);
    }

    /**
     * Detect intents from customer message
     */
    protected function detectIntents(string $message): array
    {
        $message = strtolower($message);
        $detectedIntents = [];

        foreach ($this->keywordMap as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, strtolower($keyword))) {
                    $detectedIntents[] = $intent;
                    break; // Only add intent once
                }
            }
        }

        return array_unique($detectedIntents);
    }

    /**
     * Bundle replies for detected intents (max 3 sections)
     */
    protected function bundleReplies(array $intents, Business $business): string
    {
        // Limit to 3 intents
        $intents = array_slice($intents, 0, 3);

        $replies = [];

        foreach ($intents as $intent) {
            $replySection = match ($intent) {
                'PRICE' => $this->generatePriceReply($business),
                'AREA' => $this->generateAreaReply($business),
                'HOURS' => $this->generateHoursReply($business),
                'BOOK' => $this->generateBookingReply($business),
                default => null,
            };

            if ($replySection) {
                $replies[] = $replySection;
            }
        }

        return implode("\n\n", $replies);
    }

    /**
     * Generate price reply
     */
    protected function generatePriceReply(Business $business): string
    {
        $reply = "💰 *Our Services & Prices:*\n";

        if (empty($business->services)) {
            return $reply.'Please contact us for pricing details.';
        }

        foreach ($business->services as $service) {
            $reply .= "• {$service['name']}: RM{$service['price']}\n";
        }

        return trim($reply);
    }

    /**
     * Generate area reply
     */
    protected function generateAreaReply(Business $business): string
    {
        $reply = "📍 *Areas We Cover:*\n";

        if (empty($business->areas)) {
            return $reply.'We serve various locations. Contact us for details.';
        }

        $areas = implode(', ', $business->areas);
        $reply .= $areas;

        return $reply;
    }

    /**
     * Generate hours reply
     */
    protected function generateHoursReply(Business $business): string
    {
        $reply = "🕐 *Operating Hours:*\n";

        if (empty($business->operating_hours)) {
            return $reply.'Please contact us for our operating hours.';
        }

        // Format: ['monday' => ['open' => '09:00', 'close' => '18:00'], ...]
        foreach ($business->operating_hours as $day => $hours) {
            $dayName = ucfirst($day);
            if (isset($hours['closed']) && $hours['closed']) {
                $reply .= "• {$dayName}: Closed\n";
            } else {
                $reply .= "• {$dayName}: {$hours['open']} - {$hours['close']}\n";
            }
        }

        return trim($reply);
    }

    /**
     * Generate booking reply
     */
    protected function generateBookingReply(Business $business): string
    {
        $reply = "📅 *How to Book:*\n";

        if (empty($business->booking_method)) {
            return $reply.'Please contact us to make a booking.';
        }

        $reply .= $business->booking_method;

        return $reply;
    }

    /**
     * Check if current time is outside business operating hours
     */
    protected function isAfterHours(Business $business): bool
    {
        if (empty($business->operating_hours)) {
            return false; // If no hours set, assume always open
        }

        $now = Carbon::now();
        $currentDay = strtolower($now->format('l')); // e.g., 'monday'

        if (! isset($business->operating_hours[$currentDay])) {
            return true; // Day not defined, assume closed
        }

        $dayHours = $business->operating_hours[$currentDay];

        // Check if closed for the day
        if (isset($dayHours['closed']) && $dayHours['closed']) {
            return true;
        }

        // Check if within operating hours
        $currentTime = $now->format('H:i');
        $openTime = $dayHours['open'] ?? '00:00';
        $closeTime = $dayHours['close'] ?? '23:59';

        return $currentTime < $openTime || $currentTime > $closeTime;
    }

    /**
     * Generate after-hours reply
     */
    protected function generateAfterHoursReply(Business $business): string
    {
        $reply = "🌙 Thank you for your message!\n\n";
        $reply .= "We're currently closed. ";

        if (! empty($business->operating_hours)) {
            $reply .= "Our operating hours are:\n\n";

            foreach ($business->operating_hours as $day => $hours) {
                $dayName = ucfirst($day);
                if (isset($hours['closed']) && $hours['closed']) {
                    $reply .= "• {$dayName}: Closed\n";
                } else {
                    $reply .= "• {$dayName}: {$hours['open']} - {$hours['close']}\n";
                }
            }

            $reply .= "\nWe'll get back to you during business hours!";
        } else {
            $reply .= "We'll get back to you as soon as possible!";
        }

        return $reply;
    }
}
