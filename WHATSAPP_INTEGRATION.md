# WhatsApp Cloud API Integration

This document explains how to set up and use the WhatsApp Cloud API integration in this Laravel application.

## Features

- ✅ Webhook endpoint for receiving messages
- ✅ Webhook signature verification for security
- ✅ Automatic "Hello" reply to all incoming messages
- ✅ Message logging (inbound & outbound)
- ✅ Status tracking (sent, delivered, read, failed)
- ✅ Support for multiple message types (text, image, video, audio, document)

## Setup Instructions

### 1. WhatsApp Business Account Setup

1. Go to [Meta for Developers](https://developers.facebook.com/)
2. Create a new app or use an existing one
3. Add the "WhatsApp" product to your app
4. In WhatsApp settings, find:
   - **Phone Number ID**: Your WhatsApp Business phone number ID
   - **Access Token**: Temporary or permanent access token
   - **App Secret**: Found in app settings
5. Create a custom **Verify Token** (any random string you choose)

### 2. Environment Configuration

Add the following to your `.env` file:

```env
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_ACCESS_TOKEN=your_access_token
WHATSAPP_VERIFY_TOKEN=your_custom_verify_token
WHATSAPP_APP_SECRET=your_app_secret
```

### 3. Webhook Configuration

1. Your webhook URL will be: `https://yourdomain.com/webhook/whatsapp`
2. In Meta for Developers > WhatsApp > Configuration:
   - Set Callback URL to your webhook URL
   - Set Verify Token to the same value as `WHATSAPP_VERIFY_TOKEN`
   - Subscribe to webhook fields:
     - `messages` (required for receiving messages)
     - `message_status` (optional, for tracking delivery status)
3. Click "Verify and Save"

**Note**: For local development, use a tool like [ngrok](https://ngrok.com/) to expose your local server:
```bash
ngrok http 8000
# Use the ngrok URL as your webhook URL
```

### 4. Test the Integration

1. Send a message to your WhatsApp Business number
2. The application will:
   - Receive the message via webhook
   - Log it to the `whatsapp_messages` table
   - Automatically reply with "Hello"
   - Log the outbound message

## Architecture

### Database Schema

**Table: `whatsapp_messages`**
- `message_id` - WhatsApp's unique message ID
- `direction` - 'inbound' or 'outbound'
- `from` - Sender phone number
- `to` - Recipient phone number
- `message_type` - 'text', 'image', 'video', 'audio', 'document'
- `content` - JSON containing message body/media info
- `status` - 'sent', 'delivered', 'read', 'failed', 'received'
- `metadata` - Additional data from WhatsApp API

### Key Components

1. **WhatsAppService** (`app/Services/WhatsAppService.php`)
   - `sendMessage($to, $message)` - Send messages
   - `verifyWebhookSignature($signature, $payload)` - Validate webhooks
   - `handleIncomingMessage($data)` - Process incoming messages

2. **WhatsAppWebhookController** (`app/Http/Controllers/WhatsAppWebhookController.php`)
   - `verify()` - GET endpoint for webhook verification
   - `handle()` - POST endpoint for receiving webhooks
   - Auto-reply logic in `handleMessage()`

3. **WhatsAppMessage Model** (`app/Models/WhatsAppMessage.php`)
   - Eloquent model with scopes for filtering

### Routes

```php
GET  /webhook/whatsapp  - Webhook verification
POST /webhook/whatsapp  - Receive messages
```

## Usage Examples

### Sending a Message Programmatically

```php
use App\Services\WhatsAppService;

$whatsapp = app(WhatsAppService::class);
$message = $whatsapp->sendMessage('1234567890', 'Hello from Laravel!');

if ($message) {
    echo "Message sent: {$message->message_id}";
}
```

### Querying Messages

```php
use App\Models\WhatsAppMessage;

// Get all inbound messages
$inbound = WhatsAppMessage::inbound()->latest()->get();

// Get all delivered outbound messages
$delivered = WhatsAppMessage::outbound()->status('delivered')->get();

// Get messages from specific number
$messages = WhatsAppMessage::where('from', '1234567890')->get();
```

## Customizing Auto-Reply

To change the auto-reply behavior, edit the `handleMessage()` method in `WhatsAppWebhookController`:

```php
protected function handleMessage(array $messageData): void
{
    $inboundMessage = $this->whatsAppService->handleIncomingMessage($messageData);
    
    // Custom logic here
    $from = $messageData['from'];
    $text = $messageData['text']['body'] ?? '';
    
    // Example: Echo back the message
    $this->whatsAppService->sendMessage($from, "You said: {$text}");
}
```

## Monitoring & Debugging

### Check Logs

The application logs all webhook activity:

```bash
php artisan pail
```

Look for:
- `WhatsApp webhook verified successfully`
- `Inbound message logged`
- `Auto-reply sent successfully`
- Any error messages

### View Messages in Database

```bash
php artisan tinker
>>> \App\Models\WhatsAppMessage::count()
>>> \App\Models\WhatsAppMessage::latest()->first()
```

## Security

- ✅ Webhook signature verification using `X-Hub-Signature-256` header
- ✅ Verify token validation for webhook setup
- ✅ Duplicate message prevention (checks existing `message_id`)

## API Version

This integration uses **WhatsApp Cloud API v22.0**. Check the [WhatsApp Business Platform API documentation](https://developers.facebook.com/docs/whatsapp/cloud-api) for the latest version.

## Troubleshooting

### Webhook verification fails
- Ensure `WHATSAPP_VERIFY_TOKEN` matches the token in Meta dashboard
- Check that the route is accessible (not behind auth middleware)

### Messages not received
- Verify webhook is subscribed to `messages` field
- Check application logs: `php artisan pail`
- Ensure database table exists: `php artisan migrate`

### Auto-reply not sending
- Verify `WHATSAPP_ACCESS_TOKEN` is valid
- Check token permissions include `whatsapp_business_messaging`
- Review logs for API errors

### Rate Limits
WhatsApp has rate limits based on your business verification status. Start with the free tier (1,000 conversations/month) and scale as needed.

## Next Steps

Consider implementing:
- Queue jobs for async message processing
- Template messages for notifications
- Media file handling (images, videos)
- Interactive messages (buttons, lists)
- Conversation tracking by user
- Admin dashboard for viewing messages
