<?php
/**
 * Admin Settings Class
 * 
 * Handles plugin settings and configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

class M60_Admin_Settings {
    
    public function register() {
        // Register settings
        register_setting('m60_calc_settings', 'm60_calc_min_age');
        register_setting('m60_calc_settings', 'm60_calc_max_age');
        register_setting('m60_calc_settings', 'm60_calc_min_property_value');
        register_setting('m60_calc_settings', 'm60_calc_max_property_value');
        register_setting('m60_calc_settings', 'm60_calc_interest_rate');
        register_setting('m60_calc_settings', 'm60_calc_require_phone');
        register_setting('m60_calc_settings', 'm60_calc_notification_email');
        register_setting('m60_calc_settings', 'm60_calc_hubspot_enabled');
        register_setting('m60_calc_settings', 'm60_calc_hubspot_api_key');
        register_setting('m60_calc_settings', 'm60_calc_valid_postcodes');
        
        // Add settings sections
        add_settings_section(
            'm60_calc_general_settings',
            'General Settings',
            array($this, 'general_settings_callback'),
            'm60_calc_settings'
        );
        
        add_settings_section(
            'm60_calc_calculation_settings',
            'Calculation Settings',
            array($this, 'calculation_settings_callback'),
            'm60_calc_settings'
        );
        
        add_settings_section(
            'm60_calc_hubspot_settings',
            'HubSpot Integration',
            array($this, 'hubspot_settings_callback'),
            'm60_calc_settings'
        );
        
        // Add settings fields
        $this->add_general_fields();
        $this->add_calculation_fields();
        $this->add_hubspot_fields();
    }
    
    private function add_general_fields() {
        add_settings_field(
            'm60_calc_notification_email',
            'Notification Email',
            array($this, 'notification_email_field'),
            'm60_calc_settings',
            'm60_calc_general_settings'
        );
        
        add_settings_field(
            'm60_calc_require_phone',
            'Require Phone Number',
            array($this, 'require_phone_field'),
            'm60_calc_settings',
            'm60_calc_general_settings'
        );
    }
    
    private function add_calculation_fields() {
        add_settings_field(
            'm60_calc_min_age',
            'Minimum Age',
            array($this, 'min_age_field'),
            'm60_calc_settings',
            'm60_calc_calculation_settings'
        );
        
        add_settings_field(
            'm60_calc_max_age',
            'Maximum Age',
            array($this, 'max_age_field'),
            'm60_calc_settings',
            'm60_calc_calculation_settings'
        );
        
        add_settings_field(
            'm60_calc_min_property_value',
            'Minimum Property Value',
            array($this, 'min_property_value_field'),
            'm60_calc_settings',
            'm60_calc_calculation_settings'
        );
        
        add_settings_field(
            'm60_calc_max_property_value',
            'Maximum Property Value',
            array($this, 'max_property_value_field'),
            'm60_calc_settings',
            'm60_calc_calculation_settings'
        );
        
        add_settings_field(
            'm60_calc_interest_rate',
            'Annual Interest Rate (%)',
            array($this, 'interest_rate_field'),
            'm60_calc_settings',
            'm60_calc_calculation_settings'
        );
    }
    
    private function add_hubspot_fields() {
        add_settings_field(
            'm60_calc_hubspot_enabled',
            'Enable HubSpot',
            array($this, 'hubspot_enabled_field'),
            'm60_calc_settings',
            'm60_calc_hubspot_settings'
        );
        
        add_settings_field(
            'm60_calc_hubspot_api_key',
            'HubSpot API Key',
            array($this, 'hubspot_api_key_field'),
            'm60_calc_settings',
            'm60_calc_hubspot_settings'
        );
    }
    
    public function general_settings_callback() {
        echo '<p>Configure general plugin settings.</p>';
    }
    
    public function calculation_settings_callback() {
        echo '<p>Configure calculation parameters for the reverse mortgage calculator.</p>';
    }
    
    public function hubspot_settings_callback() {
        echo '<p>Configure HubSpot CRM integration to automatically sync leads.</p>';
    }
    
    public function notification_email_field() {
        $value = get_option('m60_calc_notification_email', get_option('admin_email'));
        echo '<input type="email" name="m60_calc_notification_email" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Email address to receive lead notifications</p>';
    }
    
    public function require_phone_field() {
        $value = get_option('m60_calc_require_phone', false);
        echo '<input type="checkbox" name="m60_calc_require_phone" value="1" ' . checked($value, true, false) . ' />';
        echo '<label>Make phone number required for lead submissions</label>';
    }
    
    public function min_age_field() {
        $value = get_option('m60_calc_min_age', 60);
        echo '<input type="number" name="m60_calc_min_age" value="' . esc_attr($value) . '" min="50" max="100" />';
        echo '<p class="description">Minimum eligible age (typically 60)</p>';
    }
    
    public function max_age_field() {
        $value = get_option('m60_calc_max_age', 95);
        echo '<input type="number" name="m60_calc_max_age" value="' . esc_attr($value) . '" min="60" max="100" />';
        echo '<p class="description">Maximum eligible age (typically 95)</p>';
    }
    
    public function min_property_value_field() {
        $value = get_option('m60_calc_min_property_value', 200000);
        echo '<input type="number" name="m60_calc_min_property_value" value="' . esc_attr($value) . '" step="10000" />';
        echo '<p class="description">Minimum property value in dollars</p>';
    }
    
    public function max_property_value_field() {
        $value = get_option('m60_calc_max_property_value', 10000000);
        echo '<input type="number" name="m60_calc_max_property_value" value="' . esc_attr($value) . '" step="100000" />';
        echo '<p class="description">Maximum property value in dollars</p>';
    }
    
    public function interest_rate_field() {
        $value = get_option('m60_calc_interest_rate', 8.95);
        echo '<input type="number" name="m60_calc_interest_rate" value="' . esc_attr($value) . '" step="0.01" min="0" max="20" />';
        echo '<p class="description">Annual interest rate for projections (e.g., 8.95 for 8.95%)</p>';
    }
    
    public function hubspot_enabled_field() {
        $value = get_option('m60_calc_hubspot_enabled', false);
        echo '<input type="checkbox" name="m60_calc_hubspot_enabled" value="1" ' . checked($value, true, false) . ' />';
        echo '<label>Enable automatic lead sync to HubSpot</label>';
    }
    
    public function hubspot_api_key_field() {
        $value = get_option('m60_calc_hubspot_api_key', '');
        echo '<input type="text" name="m60_calc_hubspot_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Your HubSpot private app access token or API key</p>';
        
        // Test connection button
        if (!empty($value)) {
            echo '<p><button type="button" class="button" id="test-hubspot-connection">Test Connection</button></p>';
            echo '<div id="hubspot-test-result"></div>';
        }
    }
}
