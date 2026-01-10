<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
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
            ],
        ]);
    }
}
