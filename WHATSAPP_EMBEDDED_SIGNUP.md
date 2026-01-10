# WhatsApp Embedded Signup Integration

This document explains how to set up and use the WhatsApp Embedded Signup feature, which allows each business to connect their own WhatsApp Business Account to the platform.

## Table of Contents

- [Overview](#overview)
- [Prerequisites](#prerequisites)
- [Environment Setup](#environment-setup)
- [Flow Explanation](#flow-explanation)
- [Usage Guide](#usage-guide)
- [Technical Details](#technical-details)
- [Troubleshooting](#troubleshooting)
- [Security Considerations](#security-considerations)

## Overview

The WhatsApp Embedded Signup feature enables businesses to:
- Connect their own WhatsApp Business Account (WABA)
- Attach a phone number for sending/receiving messages
- Manage connections through an admin interface
- Securely store credentials (encrypted at rest)

This is the compliant way for SaaS platforms to onboard customers to WhatsApp Business API.

## Prerequisites

Before you can use this feature, you need:

### 1. Meta Developer Account
- Create an account at [developers.facebook.com](https://developers.facebook.com)
- Create a Meta App (type: Business)

### 2. Embedded Signup Configuration
- In your Meta App dashboard, go to WhatsApp > Configuration
- Create an Embedded Signup Configuration
- Note down the **Configuration ID** (you'll need this)

### 3. App Credentials
From your Meta App dashboard, collect:
- **App ID**: Found in App Settings > Basic
- **App Secret**: Found in App Settings > Basic (click "Show")
- **Configuration ID**: From the Embedded Signup configuration

### 4. Webhook Configuration
- Set your webhook URL: `https://yourdomain.com/webhook/whatsapp`
- Set your webhook verify token (from your `.env` file)
- Subscribe to `messages` webhook field

## Environment Setup

1. **Add Meta credentials to your `.env` file:**

```env
# Meta App Configuration
META_APP_ID=your_app_id_here
META_APP_SECRET=your_app_secret_here
META_CONFIG_ID=your_embedded_signup_config_id_here

# WhatsApp Webhook (for webhook verification)
WHATSAPP_VERIFY_TOKEN=your_random_secure_token_here
```

2. **Run migrations (if not already done):**

```bash
php artisan migrate
```

The migration adds WhatsApp-related fields to the `businesses` table:
- `waba_id`: WhatsApp Business Account ID
- `phone_number_id`: Phone Number ID (used for API calls)
- `display_phone_number`: Human-readable phone number
- `wa_access_token`: Encrypted access token
- `webhook_verify_token`: Optional custom verify token per business
- `wa_status`: Connection status (pending_connect, connected, disabled)
- `connected_at`: Timestamp when connected

## Flow Explanation

### User Flow

1. **Business Onboarding**
   - Customer completes onboarding via WhatsApp
   - Business record created with `wa_status = pending_connect`

2. **Admin Connects WhatsApp**
   - Admin navigates to Dashboard > View All Businesses
   - Clicks on a business to view details
   - Clicks "Connect WhatsApp" button

3. **Meta Embedded Signup Modal**
   - Facebook SDK loads and opens signup modal
   - User signs in with Facebook (if not already)
   - Selects WhatsApp Business Account
   - Chooses phone number to connect
   - Grants necessary permissions

4. **Credential Storage**
   - Meta returns authorization code
   - Backend exchanges code for access token
   - Credentials stored securely (encrypted)
   - Business status updated to `connected`

### Technical Flow

```
Frontend                  Backend                   Meta API
   |                         |                         |
   |-- Click "Connect" ----->|                         |
   |                         |                         |
   |<-- Return config -------|                         |
   |    (app_id, state)      |                         |
   |                         |                         |
   |-- Open FB Modal ------->|                         |
   |                         |                         |
   |<-- User grants ------- Meta Signup Modal         |
   |    permissions          |                         |
   |                         |                         |
   |-- Redirect with code -->|                         |
   |    and state            |                         |
   |                         |                         |
   |                         |-- Exchange code ------->|
   |                         |    for token            |
   |                         |                         |
   |                         |<-- Return token --------|
   |                         |    waba_id, phone_id    |
   |                         |                         |
   |                         |-- Store credentials     |
   |                         |                         |
   |<-- Redirect to ---------|                         |
   |    business page        |                         |
```

## Usage Guide

### For Administrators

1. **Access Admin Panel**
   ```
   Navigate to: Dashboard > View All Businesses
   ```

2. **View Business List**
   - See all businesses with their connection status
   - Status badges:
     - 🟡 **Pending**: Not yet connected
     - 🟢 **Connected**: Active WhatsApp connection
     - 🔴 **Disabled**: Previously connected, now disconnected

3. **Connect WhatsApp**
   - Click "View Details" on a business
   - In the WhatsApp Connection card, click "Connect WhatsApp"
   - Follow the Meta signup flow
   - Wait for confirmation

4. **Disconnect WhatsApp**
   - On a connected business detail page
   - Click "Disconnect WhatsApp"
   - Confirm the action

### For Developers

#### Testing Locally

1. **Use ngrok for webhook testing:**
   ```bash
   ngrok http 8000
   ```

2. **Update webhook URL in Meta dashboard:**
   ```
   https://your-ngrok-url.ngrok.io/webhook/whatsapp
   ```

3. **Test the flow:**
   ```bash
   # Start dev server
   composer dev
   
   # In another terminal
   php artisan test --filter=WhatsAppEmbeddedSignupTest
   ```

## Technical Details

### Backend Components

#### Services

**WhatsAppEmbeddedSignupService** (`app/Services/WhatsAppEmbeddedSignupService.php`)
- `generateState()`: Create CSRF-protected state token
- `verifyState($state)`: Verify state matches session
- `exchangeCodeForToken($code)`: Exchange auth code for access token
- `storeCredentials($business, ...)`: Save credentials to database
- `disconnect($business)`: Remove WhatsApp connection
- `getWhatsAppBusinessAccount($wabaId, $token)`: Fetch WABA details

#### Controllers

**WhatsAppEmbeddedSignupController** (`app/Http/Controllers/WhatsAppEmbeddedSignupController.php`)
- `initiate($business)`: Start signup flow, return config
- `callback(Request)`: Handle OAuth callback, exchange code
- `disconnect($business)`: Disconnect WhatsApp

**BusinessController** (`app/Http/Controllers/Admin/BusinessController.php`)
- `index()`: List all businesses with status
- `show($business)`: Show business details and connection info

### Frontend Components

#### Pages

- `resources/js/pages/admin/businesses/index.tsx`: Business list
- `resources/js/pages/admin/businesses/show.tsx`: Business detail

#### Hooks

**useWhatsAppEmbeddedSignup** (`resources/js/hooks/use-whatsapp-embedded-signup.ts`)
- `initiateSignup(businessId)`: Call backend, load FB SDK, open modal
- `disconnect(businessId)`: Disconnect with confirmation
- Returns: `{ isLoading, error }`

### API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/admin/businesses` | List all businesses |
| GET | `/admin/businesses/{id}` | View business details |
| POST | `/admin/businesses/{id}/whatsapp/initiate` | Start signup flow |
| GET | `/whatsapp/signup/callback` | OAuth callback handler |
| POST | `/admin/businesses/{id}/whatsapp/disconnect` | Disconnect WhatsApp |

### Meta API Endpoints Used

- **Token Exchange**: `POST https://graph.facebook.com/v18.0/oauth/access_token`
  - Parameters: `client_id`, `client_secret`, `code`, `redirect_uri`
  - Returns: `access_token`, `waba_id`, `phone_number_id`

- **WABA Details**: `GET https://graph.facebook.com/v18.0/{waba_id}`
  - Parameters: `fields=id,name,phone_numbers`
  - Returns: Business account details

## Troubleshooting

### Common Issues

#### 1. "Invalid state parameter" error
**Cause**: Session state mismatch (CSRF protection triggered)

**Solutions**:
- Ensure cookies are enabled
- Check `SESSION_DRIVER` in `.env` (should be `database` or `redis`)
- Don't open signup in incognito/private mode
- Complete signup within 10 minutes of clicking "Connect"

#### 2. "Failed to connect WhatsApp" error
**Cause**: Token exchange failed

**Solutions**:
- Verify `META_APP_ID` and `META_APP_SECRET` are correct
- Check Meta App is in "Live" mode (not Development)
- Ensure redirect URI matches: `https://yourdomain.com/whatsapp/signup/callback`
- Check Meta App has WhatsApp product added

#### 3. "Missing WABA ID or Phone Number ID" error
**Cause**: Meta response doesn't include required IDs

**Solutions**:
- User must complete the full signup flow (don't cancel)
- Ensure Embedded Signup Configuration is properly set up
- Check Meta dashboard for any account issues

#### 4. Webhook not receiving messages
**Cause**: Webhook configuration or business not properly connected

**Solutions**:
- Verify webhook URL is accessible from internet
- Check `WHATSAPP_VERIFY_TOKEN` matches Meta dashboard
- Ensure business `phone_number_id` is correct
- Test webhook with Meta's "Test" button

### Debugging

Enable detailed logging:

```php
// In .env
LOG_LEVEL=debug
```

Check logs at:
```bash
php artisan pail  # Real-time log viewer
# or
tail -f storage/logs/laravel.log
```

Look for:
- `WhatsApp credentials stored` (success)
- `Meta token exchange failed` (API errors)
- `Invalid state in WhatsApp signup callback` (CSRF errors)

## Security Considerations

### 1. Access Token Storage
- Tokens are encrypted at rest using Laravel's `encrypted` cast
- Database: `wa_access_token` column is encrypted
- Never log or expose tokens in responses

### 2. CSRF Protection
- State parameter prevents CSRF attacks
- State is random (40 characters) and single-use
- Stored in session, verified on callback

### 3. Authentication
- All admin endpoints require authentication
- Use middleware: `auth`, `verified`
- Only authenticated admins can connect/disconnect

### 4. Rate Limiting
- Consider adding rate limiting to signup endpoints
- Prevent abuse of Meta API calls

### 5. Token Refresh
- Current implementation uses long-lived tokens (60 days+)
- **Future enhancement**: Implement token refresh logic
- Monitor token expiration and refresh proactively

### 6. Webhook Security
- Verify webhook signature (implement in webhook handler)
- Use Meta's `X-Hub-Signature` header
- Validate `WHATSAPP_APP_SECRET` against signature

## Future Enhancements

1. **Token Refresh Strategy**
   - Implement automatic token refresh before expiration
   - Add cron job to check token validity

2. **Multi-Admin Roles**
   - Add role-based access control
   - Limit who can connect/disconnect businesses

3. **Audit Logging**
   - Track all connection/disconnection events
   - Store admin user who performed action

4. **Health Checks**
   - Periodic checks of WhatsApp connection status
   - Alert admins if connection is lost

5. **Business Owner Self-Service**
   - Allow business owners to manage their own connections
   - Remove need for admin intervention

## Support

For issues related to:
- **Meta/WhatsApp API**: Check [Meta Business Help Center](https://business.facebook.com/help)
- **This implementation**: File an issue or contact development team
- **Embedded Signup**: See [Meta Embedded Signup Docs](https://developers.facebook.com/docs/whatsapp/embedded-signup)

---

**Last Updated**: January 10, 2026  
**Version**: 1.0.0
