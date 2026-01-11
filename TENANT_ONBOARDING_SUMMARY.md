# Tenant Onboarding Feature - Implementation Summary

## Overview

Replaced the embedded signup feature with a new system where tenants create their own Meta app and configure credentials through the settings page. Implemented first-number-only onboarding restriction for security.

## Key Features

### 1. Tenant-Specific Meta App Configuration
- Tenants input their own Meta app credentials in Settings > WhatsApp
- Required fields:
  - Meta App ID
  - Meta App Secret
  - Phone Number ID
  - WhatsApp Access Token
  - Webhook Verify Token
- Optional fields:
  - WhatsApp Business Account ID
  - Display Phone Number

### 2. First-Number-Only Onboarding
- Only the **first** phone number that sends "ONBOARDING" can complete the process
- System locks onboarding to that specific number
- Other numbers receive no response (silently ignored)
- Prevents unauthorized access to business setup

### 3. Onboarding Reset Capability
- Business owners can reset the onboarding lock via Settings > WhatsApp
- Allows switching to a different phone number if needed
- Requires confirmation to prevent accidental resets

## Database Changes

### Migration: `2026_01_11_103632_add_meta_app_credentials_to_businesses_table`

Added fields to `businesses` table:
- `meta_app_id` (string, nullable) - Meta app identifier
- `meta_app_secret` (text, nullable, encrypted) - Meta app secret for webhook verification
- `onboarding_phone` (string, nullable, indexed) - Locked phone number for onboarding

## Modified Files

### Backend (Laravel)

1. **app/Models/Business.php**
   - Added new fields to fillable and casts
   - Added `getMetaAppSecret()` helper method
   - Added `isOnboardingLocked()` to check lock status
   - Added `canOnboard($phoneNumber)` to validate access

2. **app/Services/OnboardingService.php**
   - Added `$business` parameter to `startOnboarding()`
   - Added `$business` parameter to `processResponse()`
   - Implemented onboarding lock on first "ONBOARDING" message
   - Block subsequent attempts from different numbers
   - Updated to work with tenant-specific businesses

3. **app/Services/WhatsAppService.php**
   - Added `$business` parameter to `verifyWebhookSignature()`
   - Uses tenant-specific app secret for signature verification

4. **app/Http/Controllers/WhatsAppWebhookController.php**
   - Enhanced `verify()` to support tenant-specific webhook verify tokens
   - Added business lookup for signature verification
   - Passes business context to onboarding service methods

5. **app/Http/Controllers/Settings/WhatsAppController.php** (NEW)
   - `edit()` - Display WhatsApp configuration page
   - `update()` - Save Meta app credentials
   - `resetOnboarding()` - Clear onboarding lock

### Frontend (React)

1. **resources/js/pages/settings/whatsapp.tsx** (NEW)
   - Form for Meta app credential input
   - Connection status display
   - Setup instructions
   - Onboarding lock management
   - Reset functionality

### Routes

**routes/settings.php**
- `GET /settings/whatsapp` - Show configuration page
- `PATCH /settings/whatsapp` - Update credentials
- `POST /settings/whatsapp/reset-onboarding` - Reset lock

### Tests

**tests/Feature/TenantOnboardingTest.php** (NEW)
- First phone number locking behavior
- Second number blocking
- Onboarding continuation for locked number
- Business data updates
- Reset functionality
- Helper method validation

All tests passing ✓

## How It Works

### Step 1: Configuration
1. Tenant creates Meta app at developers.facebook.com
2. Adds WhatsApp product to app
3. Configures webhook in Meta app
4. Enters credentials in Settings > WhatsApp

### Step 2: Onboarding
1. Tenant messages their business WhatsApp number
2. Sends "ONBOARDING" text
3. System locks onboarding to that phone number
4. Tenant completes onboarding questionnaire
5. Business profile is created/updated

### Step 3: Security
- Other numbers attempting "ONBOARDING" are silently ignored
- Only locked number can interact with onboarding flow
- Reset available via settings if needed

## Security Features

1. **Encrypted Credentials**
   - `meta_app_secret` stored with Laravel encryption
   - `wa_access_token` stored with Laravel encryption

2. **Webhook Verification**
   - Supports both global and tenant-specific verify tokens
   - Signature verification uses tenant's app secret

3. **Onboarding Lock**
   - First-number-only restriction
   - Silent failure for unauthorized attempts
   - Manual reset required to change locked number

## Migration Path

From embedded signup:
1. Global credentials still work as fallback
2. Tenants can configure their own app when ready
3. System automatically uses tenant credentials when available
4. Backward compatible with existing setups

## Documentation

- **TENANT_SETUP_GUIDE.md** - Complete setup instructions for tenants
- **TENANT_ONBOARDING_SUMMARY.md** (this file) - Technical overview

## API Credentials Required

Tenants need from Meta:
1. **Meta App ID** - App identification
2. **Meta App Secret** - Webhook signature verification
3. **Phone Number ID** - WhatsApp number identifier
4. **Access Token** - API authentication
5. **Webhook Verify Token** - Webhook endpoint verification

## Testing

Run tests:
```bash
php artisan test --filter=TenantOnboardingTest
```

7 tests, 27 assertions - All passing ✓

## Next Steps for Users

1. Review TENANT_SETUP_GUIDE.md for detailed instructions
2. Create Meta app following guide
3. Configure credentials in Settings > WhatsApp
4. Message business number with "ONBOARDING"
5. Complete business profile setup

## Support

For issues:
- Check application logs for detailed errors
- Verify webhook configuration in Meta
- Ensure credentials are correct
- Review Meta app permissions
