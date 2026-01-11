# Tenant WhatsApp Setup Guide

This guide explains how tenants can set up their own Meta app and configure WhatsApp integration for their business.

## Overview

Instead of using embedded signup, tenants now create their own Meta app and provide the necessary credentials through the settings page. This gives tenants full control over their WhatsApp integration.

## Prerequisites

- A Meta (Facebook) account
- A WhatsApp Business phone number
- Access to the tenant settings page

## Step 1: Create a Meta App

1. Go to [Meta for Developers](https://developers.facebook.com)
2. Click **My Apps** in the top right
3. Click **Create App**
4. Select **Business** as the app type
5. Fill in the app details:
   - **App Name**: Your business name (e.g., "MyBusiness WhatsApp")
   - **App Contact Email**: Your business email
6. Click **Create App**

## Step 2: Add WhatsApp Product

1. In your app dashboard, find **WhatsApp** in the products list
2. Click **Set Up**
3. Follow the setup wizard to add your WhatsApp Business phone number
4. Complete the verification process for your phone number

## Step 3: Get Your Credentials

You'll need to collect several credentials from your Meta app:

### Meta App ID
1. Go to your app dashboard
2. Find **App ID** in the top left under your app name
3. Copy this value

### Meta App Secret
1. In app dashboard, go to **Settings** > **Basic**
2. Click **Show** next to **App Secret**
3. Copy this value (keep it secure!)

### Phone Number ID
1. Go to **WhatsApp** > **API Setup**
2. Find your **Phone number ID** under your phone number
3. Copy this value

### WhatsApp Access Token
1. In **WhatsApp** > **API Setup**
2. Find **Temporary access token** or create a **System User Access Token**
3. For production, create a permanent token:
   - Go to **Business Settings** > **System Users**
   - Create a new system user
   - Add assets (your app and WhatsApp Business Account)
   - Generate a token with `whatsapp_business_messaging` permission
4. Copy the access token

### Webhook Verify Token
1. Create your own secure random string (e.g., `my_secure_token_abc123xyz`)
2. This can be any string you choose - you'll use it in both Meta and the settings page

### WhatsApp Business Account ID (Optional)
1. Go to **WhatsApp** > **API Setup**
2. Find **WhatsApp Business Account ID** at the top
3. Copy this value

## Step 4: Configure Webhook in Meta

1. In your Meta app, go to **WhatsApp** > **Configuration**
2. Click **Edit** next to **Webhook**
3. Enter the callback URL:
   ```
   https://your-domain.com/whatsapp/webhook
   ```
4. Enter your **Webhook Verify Token** (the one you created)
5. Click **Verify and Save**
6. Subscribe to webhook fields:
   - ✅ messages
   - ✅ message_status

## Step 5: Configure in Sahuti Settings

1. Log in to your Sahuti account
2. Go to **Settings** > **WhatsApp**
3. Fill in all the credentials you collected:
   - **Meta App ID**: From Step 3
   - **Meta App Secret**: From Step 3
   - **Phone Number ID**: From Step 3
   - **WhatsApp Access Token**: From Step 3
   - **Webhook Verify Token**: From Step 3
   - **WhatsApp Business Account ID** (optional): From Step 3
   - **Display Phone Number** (optional): Your WhatsApp number (e.g., +1234567890)
4. Click **Save configuration**

## Step 6: Start Onboarding

1. Using WhatsApp, message your business phone number
2. Send the text: **ONBOARDING**
3. The system will guide you through the onboarding process
4. Answer each question to set up your business profile

**Important**: Only the **first phone number** that sends "ONBOARDING" will be able to complete the onboarding process. This prevents unauthorized access.

## Onboarding Flow

Once you send "ONBOARDING", you'll be asked:

1. **Business Name**: What's your business called?
2. **Services**: What services do you offer? (Format: Service Name - Price)
3. **Areas**: Which areas do you cover?
4. **Operating Hours**: When are you open?
5. **Booking Method**: How should customers book?
6. **Confirmation**: Review and confirm your information

## Resetting Onboarding Lock

If you need to allow a different phone number to complete onboarding:

1. Go to **Settings** > **WhatsApp**
2. Scroll to **Onboarding lock** section
3. Click **Reset onboarding lock**
4. The new phone number can now send "ONBOARDING" to start

## Credential Information

### What is Each Credential Used For?

- **Meta App ID**: Identifies your app with Meta
- **Meta App Secret**: Used to verify webhook signatures (security)
- **Phone Number ID**: Identifies which WhatsApp number to send/receive from
- **Access Token**: Authenticates API requests to send messages
- **Webhook Verify Token**: Verifies your webhook endpoint (security)
- **WABA ID**: Links to your WhatsApp Business Account (optional)
- **Display Phone Number**: Shows your formatted phone number (optional)

### Security Best Practices

1. **Never share** your App Secret or Access Token
2. Use a **strong, random** Webhook Verify Token
3. For production, use **System User tokens** (permanent) instead of temporary tokens
4. Regularly **rotate** your access tokens
5. Monitor your app's **activity** in Meta Business Manager

## Troubleshooting

### Webhook Not Receiving Messages

1. Verify your webhook URL is correct and accessible
2. Check that webhook fields are subscribed (messages, message_status)
3. Ensure your Webhook Verify Token matches in both Meta and Sahuti
4. Check application logs for webhook errors

### Cannot Send Messages

1. Verify your Access Token is valid and has correct permissions
2. Check that Phone Number ID is correct
3. Ensure your WhatsApp Business number is verified
4. Review Meta app's messaging limits

### Onboarding Not Working

1. Confirm you saved your configuration correctly
2. Ensure your business phone number is the one configured in Meta
3. Check that only one number has sent "ONBOARDING"
4. If locked to wrong number, use Reset Onboarding Lock

### Signature Verification Failed

1. Ensure Meta App Secret is correct
2. Check that webhook payload is not being modified
3. Verify HTTPS is properly configured

## Support

For additional help:
- Check [Meta WhatsApp Business API Documentation](https://developers.facebook.com/docs/whatsapp/cloud-api)
- Review application logs for detailed error messages
- Contact support if issues persist

## Migration from Embedded Signup

If you were previously using embedded signup:

1. Your existing connection may still work with global credentials
2. To switch to your own app, follow this guide completely
3. The system will use your tenant-specific credentials once configured
4. Test thoroughly before going live

## Next Steps

After setup is complete:

1. Test sending messages through WhatsApp
2. Configure your auto-reply settings
3. Set up your business hours and services
4. Monitor message logs and analytics
5. Train your team on using the system
