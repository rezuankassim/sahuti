<?php

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('initiate endpoint returns configuration for pending business', function () {
    $business = Business::factory()->create(['wa_status' => 'pending_connect']);

    config([
        'services.meta.app_id' => 'test_app_id',
        'services.meta.config_id' => 'test_config_id',
    ]);

    actingAs($this->user)
        ->post(route('admin.businesses.whatsapp.initiate', $business))
        ->assertOk()
        ->assertJson([
            'success' => true,
            'config' => [
                'app_id' => 'test_app_id',
                'config_id' => 'test_config_id',
            ],
        ]);

    // Verify state is stored in session
    expect(session()->has('whatsapp_signup_state'))->toBeTrue();
    expect(session()->get('whatsapp_signup_business_id'))->toBe($business->id);
});

test('initiate endpoint returns error for already connected business', function () {
    $business = Business::factory()->create([
        'wa_status' => 'connected',
        'phone_number_id' => '123456',
        'wa_access_token' => 'test_token',
    ]);

    actingAs($this->user)
        ->post(route('admin.businesses.whatsapp.initiate', $business))
        ->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'WhatsApp is already connected to this business.',
        ]);
});

test('callback endpoint exchanges code for token and stores credentials', function () {
    $business = Business::factory()->create(['wa_status' => 'pending_connect']);

    // Set up session
    session([
        'whatsapp_signup_state' => 'test_state',
        'whatsapp_signup_business_id' => $business->id,
    ]);

    // Mock Meta API responses
    Http::fake([
        'graph.facebook.com/v18.0/oauth/access_token' => Http::response([
            'access_token' => 'test_access_token',
            'waba_id' => 'test_waba_id',
            'phone_number_id' => 'test_phone_number_id',
        ]),
        'graph.facebook.com/v18.0/test_waba_id*' => Http::response([
            'id' => 'test_waba_id',
            'name' => 'Test WABA',
            'phone_numbers' => [
                ['display_phone_number' => '+60123456789'],
            ],
        ]),
    ]);

    actingAs($this->user)
        ->get(route('whatsapp.signup.callback', [
            'code' => 'test_code',
            'state' => 'test_state',
        ]))
        ->assertRedirect(route('admin.businesses.show', $business))
        ->assertSessionHas('success', 'WhatsApp connected successfully!');

    // Verify credentials are stored
    $business->refresh();
    expect($business->waba_id)->toBe('test_waba_id');
    expect($business->phone_number_id)->toBe('test_phone_number_id');
    expect($business->wa_status)->toBe('connected');
    expect($business->connected_at)->not->toBeNull();

    // Verify session is cleaned up
    expect(session()->has('whatsapp_signup_state'))->toBeFalse();
    expect(session()->has('whatsapp_signup_business_id'))->toBeFalse();
});

test('callback endpoint rejects invalid state', function () {
    session(['whatsapp_signup_state' => 'correct_state']);

    actingAs($this->user)
        ->get(route('whatsapp.signup.callback', [
            'code' => 'test_code',
            'state' => 'wrong_state',
        ]))
        ->assertRedirect(route('admin.businesses.index'))
        ->assertSessionHas('error', 'Invalid state parameter. Please try again.');
});

test('callback endpoint handles missing business id in session', function () {
    session(['whatsapp_signup_state' => 'test_state']);

    actingAs($this->user)
        ->get(route('whatsapp.signup.callback', [
            'code' => 'test_code',
            'state' => 'test_state',
        ]))
        ->assertRedirect(route('admin.businesses.index'))
        ->assertSessionHas('error', 'Session expired. Please try again.');
});

test('callback endpoint handles token exchange failure', function () {
    $business = Business::factory()->create(['wa_status' => 'pending_connect']);

    session([
        'whatsapp_signup_state' => 'test_state',
        'whatsapp_signup_business_id' => $business->id,
    ]);

    // Mock failed API response
    Http::fake([
        'graph.facebook.com/v18.0/oauth/access_token' => Http::response(['error' => 'Invalid code'], 400),
    ]);

    actingAs($this->user)
        ->get(route('whatsapp.signup.callback', [
            'code' => 'invalid_code',
            'state' => 'test_state',
        ]))
        ->assertRedirect(route('admin.businesses.show', $business))
        ->assertSessionHas('error', 'Failed to connect WhatsApp. Please try again.');
});

test('disconnect endpoint disconnects whatsapp from business', function () {
    $business = Business::factory()->create([
        'wa_status' => 'connected',
        'waba_id' => 'test_waba_id',
        'phone_number_id' => 'test_phone_number_id',
        'wa_access_token' => 'test_token',
        'connected_at' => now(),
    ]);

    actingAs($this->user)
        ->post(route('admin.businesses.whatsapp.disconnect', $business))
        ->assertRedirect(route('admin.businesses.show', $business))
        ->assertSessionHas('success', 'WhatsApp disconnected successfully.');

    $business->refresh();
    expect($business->wa_status)->toBe('disabled');
    expect($business->waba_id)->toBeNull();
    expect($business->phone_number_id)->toBeNull();
    expect($business->wa_access_token)->toBeNull();
    expect($business->connected_at)->toBeNull();
});

test('disconnect endpoint returns error for not connected business', function () {
    $business = Business::factory()->create(['wa_status' => 'pending_connect']);

    actingAs($this->user)
        ->post(route('admin.businesses.whatsapp.disconnect', $business))
        ->assertRedirect(route('admin.businesses.show', $business))
        ->assertSessionHas('error', 'WhatsApp is not connected to this business.');
});

test('callback handles missing waba_id or phone_number_id gracefully', function () {
    $business = Business::factory()->create(['wa_status' => 'pending_connect']);

    session([
        'whatsapp_signup_state' => 'test_state',
        'whatsapp_signup_business_id' => $business->id,
    ]);

    // Mock response without waba_id and phone_number_id
    Http::fake([
        'graph.facebook.com/v18.0/oauth/access_token' => Http::response([
            'access_token' => 'test_access_token',
        ]),
    ]);

    actingAs($this->user)
        ->get(route('whatsapp.signup.callback', [
            'code' => 'test_code',
            'state' => 'test_state',
        ]))
        ->assertRedirect(route('admin.businesses.show', $business))
        ->assertSessionHas('error');
});
