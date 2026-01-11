# Meta App Setup Guide 2026 - WhatsApp Embedded Signup

**Updated for Meta's "Use Cases" Interface (2026)**

This guide reflects Meta's current interface which uses **"Use Cases"** instead of the old "Products" approach.

## Quick Diagnosis

If you're seeing **"Error retrieving login status, fetch cancelled"**, it means the Facebook SDK cannot initialize. This is almost always a **Meta App configuration issue**.

## Step-by-Step Setup (Updated for 2026)

### 1. Create Meta App with Use Case

1. Go to [Meta for Developers](https://developers.facebook.com/)
2. Click **"My Apps"** > **"Create App"**
3. **Select Use Case**: Choose **"Other"** (or if available, "Business Messaging")
4. Click **"Next"**
5. **App Type**: Select **"Business"**
6. Click **"Next"**
7. **App Details**:
   - **App name**: "Sahuti" (or your business name)
   - **App contact email**: your email
   - **Business Portfolio**: Select or create one
8. Click **"Create App"**

### 2. Add WhatsApp Use Case

1. In your app dashboard, look for **"Add use case"** or **"Use cases"** in the left sidebar
2. Find **"WhatsApp"** and click **"Add"** or **"Set up"**
3. This adds WhatsApp messaging capabilities to your app

### 3. Get Your App Credentials

#### App ID & App Secret
1. Go to **Settings** > **Basic** (left sidebar)
2. Copy your **App ID** (e.g., `123456789012345`)
3. Click **"Show"** next to **App Secret** and copy it
4. Add to your `.env`:
   ```env
   META_APP_ID=your_app_id_here
   META_APP_SECRET=your_app_secret_here
   ```

### 4. Configure Embedded Signup

1. In left sidebar, go to **WhatsApp** > **Configuration**
2. Scroll to **"Embedded Signup"** section
3. Click **"Create Configuration"**
4. **Configuration details**:
   - **Name**: "Sahuti Embedded Signup"
   - **Callback URL**: Leave blank (handled in code)
5. Click **"Create"**
6. **Copy the Configuration ID** (looks like `987654321098765`)
7. Add to `.env`:
   ```env
   META_CONFIG_ID=your_config_id_here
   ```

### 5. Domain Configuration (CRITICAL!)

#### Add App Domains
1. Go to **Settings** > **Basic**
2. Scroll to **"App Domains"**
3. Click **"+ Add Domain"**
4. Enter your domain **WITHOUT http/https**:
   - Production: `yourdomain.com`
   - ngrok (for local): `your-subdomain.ngrok.io`
5. Click **"Save Changes"**

#### Set Site URL
1. Still in **Settings** > **Basic**
2. Scroll to **"Website"** section
3. If no website platform exists, click **"+ Add Platform"** > **"Website"**
4. Set **Site URL** (WITH http/https):
   - Production: `https://yourdomain.com`
   - ngrok: `https://your-subdomain.ngrok.io`
   - localhost: `http://localhost:8000` (testing only)
5. Click **"Save Changes"**

### 6. OAuth Redirect URIs (CRITICAL!)

1. Go to **Settings** > **Advanced** (tab at top)
2. Scroll to **"Security"** section  
3. Find **"Valid OAuth Redirect URIs"**
4. Click **"+ Add URI"**
5. Enter **exact callback URL**:
   - Production: `https://yourdomain.com/whatsapp/signup/callback`
   - ngrok: `https://your-subdomain.ngrok.io/whatsapp/signup/callback`
6. Must include the exact path: `/whatsapp/signup/callback`
7. Click **"Save Changes"**

### 7. Switch App to Live Mode

**IMPORTANT**: For production use, your app must be in **Live** mode.

1. Look at top-right corner of app dashboard
2. If it says **"Development"**:
   - Click the switch/toggle
   - Select **"Switch to Live"**
   - You may need to complete some requirements first

**For Development/Testing**:
- You can stay in Development mode
- But you MUST add **Test Users**:
  1. Go to **Roles** > **Test Users**
  2. Click **"Add Test Users"**
  3. Use test accounts for testing

### 8. Required Permissions

For WhatsApp Embedded Signup, you need:
- `whatsapp_business_management` - Manage WhatsApp Business accounts
- `whatsapp_business_messaging` - Send/receive messages

These are typically included when you add the WhatsApp use case. Check:
1. Go to **Use cases** (left sidebar)
2. Click on **WhatsApp**
3. Verify permissions are listed

### 9. Verify Your `.env` File

```env
# Meta App Configuration
META_APP_ID=123456789012345          # From Settings > Basic
META_APP_SECRET=abc123def456...      # From Settings > Basic (click Show)
META_CONFIG_ID=987654321098765       # From WhatsApp > Configuration > Embedded Signup

# Your app URL (must match Meta settings EXACTLY)
APP_URL=https://your-subdomain.ngrok.io   # Or production domain
```

### 10. HTTPS Requirement

✅ **Facebook SDK requires HTTPS** (except localhost)

**For Local Development**:
```bash
# Install ngrok
brew install ngrok  # or download from ngrok.com

# Run ngrok
ngrok http 8000

# Copy the HTTPS URL (e.g., https://abc123.ngrok.io)
```

**Update all settings**:
- App Domains: `abc123.ngrok.io` (no https)
- Site URL: `https://abc123.ngrok.io` (with https)
- OAuth Redirect URI: `https://abc123.ngrok.io/whatsapp/signup/callback`
- `.env` APP_URL: `https://abc123.ngrok.io`

## Common Errors & Solutions

### "Error retrieving login status, fetch cancelled"

**Causes**:
1. ❌ App ID mismatch
2. ❌ Domain not in App Domains
3. ❌ Site URL incorrect
4. ❌ Using HTTP instead of HTTPS
5. ❌ OAuth Redirect URI missing

**Solutions**:
- ✅ Double-check `META_APP_ID` matches Meta dashboard
- ✅ Add domain to App Domains (without http/https)
- ✅ Set Site URL correctly (with https)
- ✅ Use ngrok for local development
- ✅ Add exact callback URL to OAuth Redirect URIs

### "Given URL is not allowed"

**Cause**: Domain not whitelisted

**Solution**:
- Add domain to **App Domains** in Settings > Basic
- Ensure Site URL is set correctly

### "Redirect URI mismatch"

**Cause**: Callback URL not in OAuth settings

**Solution**:
- Go to Settings > Advanced
- Add exact URL to **Valid OAuth Redirect URIs**
- Must include `/whatsapp/signup/callback` path

### "Invalid app_id"

**Cause**: Wrong App ID or typo

**Solution**:
- Copy App ID directly from Settings > Basic
- Check for extra spaces in `.env`
- App ID should be numbers only

## Testing Checklist

- [ ] App created with WhatsApp use case
- [ ] App ID copied to `.env` (no spaces)
- [ ] App Secret copied to `.env` (click Show to see it)
- [ ] Embedded Signup Config ID created and copied
- [ ] Domain added to App Domains (no http/https)
- [ ] Site URL set (with https)
- [ ] OAuth Redirect URI added (full path with https)
- [ ] Using HTTPS (ngrok for local)
- [ ] App in Live mode OR using Test Users
- [ ] Browser console shows no CORS errors
- [ ] Can access admin panel when logged in

## Local Development Example

**1. Start ngrok**:
```bash
ngrok http 8000
```

**2. Copy ngrok URL** (e.g., `https://abc123def456.ngrok.io`)

**3. Meta App Settings**:
- **App Domains**: `abc123def456.ngrok.io`
- **Site URL**: `https://abc123def456.ngrok.io`
- **OAuth Redirect URI**: `https://abc123def456.ngrok.io/whatsapp/signup/callback`

**4. `.env` file**:
```env
APP_URL=https://abc123def456.ngrok.io
META_APP_ID=123456789012345
META_APP_SECRET=your_secret_here
META_CONFIG_ID=987654321098765
```

**5. Start Laravel**:
```bash
php artisan serve
```

**6. Access via ngrok URL**:
```
https://abc123def456.ngrok.io
```

## Production Example

**Meta App Settings**:
- **App Domains**: `yourdomain.com`
- **Site URL**: `https://yourdomain.com`
- **OAuth Redirect URI**: `https://yourdomain.com/whatsapp/signup/callback`
- **App Mode**: Live

**`.env` file**:
```env
APP_URL=https://yourdomain.com
META_APP_ID=123456789012345
META_APP_SECRET=your_secret_here
META_CONFIG_ID=987654321098765
```

## Debugging Steps

### 1. Check Browser Console
```
F12 > Console tab
```
Look for:
- "Invalid app_id"
- "Given URL is not allowed"
- CORS errors
- Mixed content warnings

### 2. Test Backend Config Endpoint

Open browser console and run:
```javascript
fetch('/admin/businesses/1/whatsapp/initiate', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    credentials: 'include'
}).then(r => r.json()).then(console.log).catch(console.error)
```

Should return config with `app_id`, `config_id`, `state`.

### 3. Verify Facebook SDK Loads

In browser console:
```javascript
window.FB
```

If `undefined`, SDK didn't load. Check:
- Network tab for script errors
- Console for JavaScript errors
- Mixed content (HTTP/HTTPS) issues

### 4. Check Laravel Logs

```bash
php artisan pail
```

Look for errors when clicking "Connect WhatsApp".

## Still Not Working?

1. **Clear browser cache**: Hard refresh (Cmd+Shift+R / Ctrl+Shift+R)
2. **Try incognito mode**: Rules out browser extensions
3. **Try different browser**: Test in Chrome, Firefox, Safari
4. **Wait 5-10 minutes**: Meta settings can take time to propagate
5. **Recreate the app**: Sometimes apps get into a bad state

## Key Differences from Old Interface

| Old (Products) | New (Use Cases) |
|---|---|
| "Add Product" | "Add Use Case" |
| Products sidebar | Use Cases sidebar |
| Product-focused permissions | Bundled use case permissions |
| Manual permission requests | Automatic with use case |

## Support Resources

- [Meta WhatsApp Business Platform](https://developers.facebook.com/docs/whatsapp/)
- [Embedded Signup Documentation](https://developers.facebook.com/docs/whatsapp/embedded-signup)
- [Meta Developer Community](https://developers.facebook.com/community/)

---

**Last Updated**: January 11, 2026  
**Interface Version**: Use Cases (2026)
