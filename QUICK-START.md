# Quick Start Guide - Money at 60 Calculator

## Installation (5 Minutes)

### Step 1: Upload Plugin
1. Download the `money-at-60-calculator` folder
2. Upload to `/wp-content/plugins/` via FTP or WordPress admin
3. OR: Zip the folder and upload via Plugins → Add New → Upload

### Step 2: Activate
1. Go to WordPress Admin → Plugins
2. Find "Money at 60 Reverse Mortgage Calculator"
3. Click "Activate"

### Step 3: Configure (Required)
1. Go to **M60 Calculator → Settings**
2. Set your **Notification Email** (where leads will be sent)
3. Review calculation parameters (defaults are good for most)
4. Click "Save Changes"

### Step 4: Add to Page
1. Create or edit a page
2. Add this shortcode: `[m60_calculator]`
3. Publish the page
4. Done! Your calculator is live

## Optional: HubSpot Integration

### Step 1: Get HubSpot API Key
1. Log into your HubSpot account
2. Go to Settings → Integrations → Private Apps
3. Create a new Private App
4. Give it permissions for Contacts (Read & Write)
5. Copy the Access Token

### Step 2: Configure Plugin
1. Go to **M60 Calculator → Settings**
2. Scroll to "HubSpot Integration"
3. Check "Enable HubSpot"
4. Paste your Access Token
5. Click "Test Connection"
6. Click "Save Changes"

All leads will now automatically sync to HubSpot!

## Testing Your Calculator

1. Visit the page with your calculator
2. Enter test data:
   - Postcode: 3000 (Melbourne)
   - Property Value: 750000
   - Age: 65
3. Complete the calculation
4. Submit a test lead with your email
5. Check your inbox for notification email
6. Check **M60 Calculator → Leads** to see the lead

## Customization Quick Tips

### Change Colors
Add to your theme's CSS:
```css
:root {
    --m60-primary: #YOUR_BLUE;
    --m60-accent: #YOUR_ORANGE;
}
```

### Change Title
Use shortcode parameter:
```
[m60_calculator title="Your Custom Title"]
```

### Hide Title Section
```
[m60_calculator show_title="no"]
```

## Common Issues

**Calculator not showing?**
- Check shortcode spelling: `[m60_calculator]` (no spaces)
- Clear cache (WP Rocket, W3 Total Cache, etc.)
- Check plugin is activated

**Leads not arriving?**
- Check spam folder
- Verify notification email in Settings
- Check **M60 Calculator → Leads** page

**HubSpot not syncing?**
- Test connection in Settings
- Verify API key has Contacts permissions
- Check error log

## Support

Email: support@moneyat60.com.au
Website: https://moneyat60.com.au

---

**That's it! You're ready to start capturing leads.**
