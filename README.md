# Money at 60 Reverse Mortgage Calculator

A superior WordPress plugin for calculating reverse mortgage borrowing capacity with lead capture and HubSpot integration.

## Features

### Calculator Features
- **Multi-step Interface**: Clean, user-friendly 4-step process
- **Smart Calculations**: Age-based LVR calculations following Australian reverse mortgage standards
- **Real-time Validation**: Postcode and property value validation
- **Projections**: Shows loan balance projections over 1, 5, 10, 15, and 20 years
- **Mobile Responsive**: Works perfectly on all devices
- **Bootstrap 4.6**: Professional styling out of the box

### Lead Management
- **Lead Capture**: Collects prospect information after calculation
- **Database Storage**: All leads stored in WordPress database
- **Email Notifications**: Instant notifications for new leads
- **Export to CSV**: Download leads for external processing
- **HubSpot Integration**: Automatic CRM sync (optional)

### Technical Features
- **Native JavaScript**: No jQuery dependencies (ES5+ compatible)
- **AJAX Capable**: Smooth async calculations via admin-ajax.php
- **Shortcode Based**: Easy implementation with customizable options
- **Custom Styling**: SCSS/CSS customization supported
- **Clean Code**: PSR-compliant PHP, documented JavaScript

## Installation

### Requirements
- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.7 or higher

### Steps

1. **Upload Plugin**
   ```
   Upload the `money-at-60-calculator` folder to `/wp-content/plugins/`
   ```

2. **Activate**
   ```
   Navigate to Plugins → Installed Plugins
   Activate "Money at 60 Reverse Mortgage Calculator"
   ```

3. **Configure Settings**
   ```
   Go to M60 Calculator → Settings
   Configure your calculation parameters
   Set notification email
   ```

4. **Add to Page**
   ```
   Add shortcode to any page or post:
   [m60_calculator]
   ```

## Shortcode Usage

### Basic Usage
```
[m60_calculator]
```

### With Custom Options
```
[m60_calculator 
    title="Calculate Your Home Equity"
    subtitle="Find out how much you could access"
    button_color="primary"
    theme="light"
    show_title="yes"
]
```

### Shortcode Parameters

| Parameter | Options | Default | Description |
|-----------|---------|---------|-------------|
| `title` | Any text | "Calculate Your Home Equity" | Main heading |
| `subtitle` | Any text | "Find out how much..." | Subheading |
| `show_title` | yes/no | yes | Show/hide title section |
| `button_color` | primary/success/danger | primary | Bootstrap button color |
| `button_text` | Any text | "Calculate Now" | Button text |
| `theme` | light/dark | light | Color scheme |

## Configuration

### General Settings

Navigate to **M60 Calculator → Settings**

#### Calculation Parameters
- **Minimum Age**: Default 60 (Australian standard)
- **Maximum Age**: Default 95
- **Minimum Property Value**: Default $200,000
- **Maximum Property Value**: Default $10,000,000
- **Interest Rate**: Annual rate for projections (default 8.95%)

#### Lead Management
- **Notification Email**: Where to send lead notifications
- **Require Phone**: Make phone number mandatory

### HubSpot Integration

1. **Enable HubSpot**
   - Check "Enable HubSpot" in Settings
   - Enter your HubSpot API key (Private App Access Token)
   - Click "Test Connection" to verify

2. **Create Custom Properties**
   - The plugin will automatically create these HubSpot contact properties:
     - `age_primary`: Primary Applicant Age
     - `age_partner`: Partner Age
     - `property_value`: Property Value
     - `loan_purpose`: Loan Purpose
     - `estimated_loan_amount`: Estimated Loan Amount

3. **Lead Sync**
   - Leads are automatically synced to HubSpot when submitted
   - Check the "Leads" page to see sync status

## Calculation Logic

### LVR (Loan-to-Value Ratio) by Age

The plugin calculates maximum borrowing capacity based on the youngest applicant's age:

- **Age 60-64**: 15-20% LVR
- **Age 65-69**: 20-25% LVR
- **Age 70-74**: 25-30% LVR
- **Age 75-79**: 30-35% LVR
- **Age 80-84**: 35-40% LVR
- **Age 85+**: 40-45% LVR (capped)

### Formula
```
Maximum Loan = Property Value × LVR
```

### For Couples
The calculation uses the **younger** age to determine LVR, following Australian reverse mortgage standards.

### Interest Projections
Future loan balances are calculated using compound interest:
```
Future Balance = Initial Loan × (1 + Interest Rate)^Years
```

## Lead Management

### Viewing Leads

Navigate to **M60 Calculator → Leads** to view all leads.

**Information Captured:**
- Full Name
- Email & Phone
- Property Postcode
- Property Value
- Age(s)
- Loan Purpose (optional)
- Estimated Loan Amount
- IP Address & User Agent
- Submission Date/Time
- HubSpot Sync Status

### Exporting Leads

Click "Export to CSV" on the Leads page to download all leads in CSV format.

**Export Includes:**
- All lead information
- Can be imported into any CRM
- Filtered by date range (coming in v1.1)

## Customization

### Custom Styling

1. **Override Default Colors**

Add to your theme's CSS:
```css
:root {
    --m60-primary: #YOUR_COLOR;
    --m60-secondary: #YOUR_COLOR;
    --m60-accent: #YOUR_COLOR;
}
```

2. **Custom CSS File**

Create `custom-calculator.css` in your theme:
```css
.m60-calculator-wrapper {
    /* Your custom styles */
}
```

Enqueue in `functions.php`:
```php
function my_custom_calculator_styles() {
    wp_enqueue_style('custom-calc', get_stylesheet_directory_uri() . '/custom-calculator.css');
}
add_action('wp_enqueue_scripts', 'my_custom_calculator_styles');
```

### Modifying Calculations

Edit `/includes/class-calculator-engine.php`:

```php
private function get_lvr_by_age($age) {
    // Modify LVR logic here
}
```

### Custom Validation

Add custom postcode validation in `class-calculator-engine.php`:

```php
private $valid_postcodes = array(
    'range' => array(
        array('min' => 2000, 'max' => 2999),  // NSW
        // Add your specific postcodes
    )
);
```

## Troubleshooting

### Calculator Not Displaying

1. Check shortcode spelling: `[m60_calculator]`
2. Verify plugin is activated
3. Clear WordPress cache
4. Check browser console for JavaScript errors

### Leads Not Saving

1. Check database table exists: `wp_m60_leads`
2. Verify AJAX URL is correct
3. Check error log: `/wp-content/debug.log`
4. Enable WordPress debug mode

### HubSpot Not Syncing

1. Test API connection in Settings
2. Verify API key has correct permissions
3. Check HubSpot custom properties exist
4. Review error logs

### Styling Issues

1. Check Bootstrap 4.6 is loading
2. Verify no theme CSS conflicts
3. Inspect element to see overriding styles
4. Try adding `!important` to custom CSS

## Development

### File Structure

```
money-at-60-calculator/
├── money-at-60-calculator.php     # Main plugin file
├── includes/
│   ├── class-calculator-engine.php
│   ├── class-lead-manager.php
│   ├── class-hubspot-integration.php
│   └── class-admin-settings.php
├── templates/
│   ├── calculator-template.php
│   ├── admin-dashboard.php
│   ├── admin-leads.php
│   └── admin-settings.php
├── assets/
│   ├── js/
│   │   └── calculator.js              # Native JavaScript
│   └── css/
│       ├── calculator.css
│       └── admin.css
└── README.md
```

### Hooks & Filters

**Actions:**
```php
do_action('m60_before_calculation', $data);
do_action('m60_after_lead_submit', $lead_id, $lead_data);
do_action('m60_hubspot_sync_success', $lead_id, $contact_id);
do_action('m60_hubspot_sync_error', $lead_id, $error);
```

**Filters:**
```php
$result = apply_filters('m60_calculation_result', $result, $data);
$lead_data = apply_filters('m60_lead_data_before_save', $lead_data);
$lvr = apply_filters('m60_lvr_by_age', $lvr, $age);
```

### Extending the Plugin

**Example: Custom Validation**

```php
add_filter('m60_lead_data_before_save', 'my_custom_validation');

function my_custom_validation($lead_data) {
    // Add custom validation
    if ($lead_data['property_value'] < 300000) {
        wp_die('Minimum property value is $300,000');
    }
    return $lead_data;
}
```

**Example: Custom Email Template**

```php
add_filter('m60_notification_email_body', 'my_custom_email');

function my_custom_email($message) {
    // Customize email body
    return $message;
}
```

## Security

- **Nonce Verification**: All AJAX requests verified
- **Input Sanitization**: All user input sanitized
- **SQL Injection Prevention**: Prepared statements used
- **XSS Protection**: Output escaped
- **CSRF Protection**: WordPress nonces
- **Capability Checks**: Admin functions restricted

## Performance

- **Optimized Queries**: Indexed database columns
- **Minimal Assets**: Only loads on pages with shortcode
- **No External Dependencies**: Self-contained plugin
- **Caching Ready**: Compatible with WordPress caching plugins

## Browser Support

- Chrome (latest 2 versions)
- Firefox (latest 2 versions)
- Safari (latest 2 versions)
- Edge (latest 2 versions)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Changelog

### Version 1.0.0
- Initial release
- Multi-step calculator interface
- Lead capture and management
- HubSpot integration
- Admin dashboard
- CSV export

## Support

For support, please contact:
- **Email**: support@moneyat60.com.au
- **Website**: https://moneyat60.com.au

## Credits

- **Author**: Money at 60
- **Bootstrap**: v4.6.2
- **Icons**: Dashicons (WordPress)

## License

GPL v2 or later
https://www.gnu.org/licenses/gpl-2.0.html

---

**Money at 60** - Specialist Reverse Mortgage Broker
