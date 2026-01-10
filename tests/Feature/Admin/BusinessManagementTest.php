<?php

use App\Models\Business;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('authenticated users can view businesses index', function () {
    Business::factory()->count(3)->create();

    actingAs($this->user)
        ->get(route('admin.businesses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/businesses/index')
            ->has('businesses', 3)
        );
});

test('businesses index shows correct data', function () {
    $business = Business::factory()->create([
        'name' => 'Test Business',
        'phone_number' => '+60123456789',
        'wa_status' => 'pending_connect',
        'is_onboarded' => true,
    ]);

    actingAs($this->user)
        ->get(route('admin.businesses.index'))
        ->assertInertia(fn ($page) => $page
            ->where('businesses.0.id', $business->id)
            ->where('businesses.0.name', 'Test Business')
            ->where('businesses.0.phone_number', '+60123456789')
            ->where('businesses.0.wa_status', 'pending_connect')
            ->where('businesses.0.is_connected', false)
        );
});

test('authenticated users can view business details', function () {
    $business = Business::factory()->create([
        'name' => 'Test Business',
        'services' => [['name' => 'Service 1', 'price' => '100']],
        'areas' => ['Kuala Lumpur', 'Selangor'],
        'operating_hours' => ['monday' => ['open' => '09:00', 'close' => '18:00']],
        'booking_method' => 'Call 123-456',
    ]);

    actingAs($this->user)
        ->get(route('admin.businesses.show', $business))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/businesses/show')
            ->where('business.id', $business->id)
            ->where('business.name', 'Test Business')
            ->has('business.services', 1)
            ->has('business.areas', 2)
            ->where('business.booking_method', 'Call 123-456')
        );
});

test('business detail shows whatsapp connection status', function () {
    $business = Business::factory()->create([
        'waba_id' => '123456789',
        'phone_number_id' => '987654321',
        'display_phone_number' => '+60123456789',
        'wa_access_token' => 'test_token',
        'wa_status' => 'connected',
        'connected_at' => now(),
    ]);

    actingAs($this->user)
        ->get(route('admin.businesses.show', $business))
        ->assertInertia(fn ($page) => $page
            ->where('business.waba_id', '123456789')
            ->where('business.phone_number_id', '987654321')
            ->where('business.wa_status', 'connected')
            ->where('business.is_connected', true)
        );
});

test('unauthenticated users cannot access businesses index', function () {
    get(route('admin.businesses.index'))
        ->assertRedirect(route('login'));
});

test('unauthenticated users cannot access business details', function () {
    $business = Business::factory()->create();

    get(route('admin.businesses.show', $business))
        ->assertRedirect(route('login'));
});

test('businesses index shows empty state when no businesses exist', function () {
    actingAs($this->user)
        ->get(route('admin.businesses.index'))
        ->assertInertia(fn ($page) => $page
            ->has('businesses', 0)
        );
});
