<?php
/**
 * Plugin Name: Money at 60 Reverse Mortgage Calculator
 * Plugin URI: https://moneyat60.com.au
 * Description: Superior reverse mortgage calculator with multi-step interface, lead capture, and HubSpot integration
 * Version: 1.0.0
 * Author: Money at 60
 * Author URI: https://moneyat60.com.au
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: money-at-60-calc
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('M60_CALC_VERSION', '1.0.0');
define('M60_CALC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('M60_CALC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('M60_CALC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Money_At_60_Calculator {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->includes();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Shortcode
        add_shortcode('m60_calculator', array($this, 'calculator_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_m60_calculate', array($this, 'ajax_calculate'));
        add_action('wp_ajax_nopriv_m60_calculate', array($this, 'ajax_calculate'));
        add_action('wp_ajax_m60_submit_lead', array($this, 'ajax_submit_lead'));
        add_action('wp_ajax_nopriv_m60_submit_lead', array($this, 'ajax_submit_lead'));
        add_action('wp_ajax_m60_validate_postcode', array($this, 'ajax_validate_postcode'));
        add_action('wp_ajax_nopriv_m60_validate_postcode', array($this, 'ajax_validate_postcode'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once M60_CALC_PLUGIN_DIR . 'includes/class-calculator-engine.php';
        require_once M60_CALC_PLUGIN_DIR . 'includes/class-lead-manager.php';
        require_once M60_CALC_PLUGIN_DIR . 'includes/class-hubspot-integration.php';
        require_once M60_CALC_PLUGIN_DIR . 'includes/class-admin-settings.php';
    }
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Create leads table
        global $wpdb;
        $table_name = $wpdb->prefix . 'm60_leads';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) DEFAULT NULL,
            postcode varchar(10) NOT NULL,
            property_value decimal(12,2) NOT NULL,
            age_primary int(3) NOT NULL,
            age_partner int(3) DEFAULT NULL,
            loan_purpose varchar(255) DEFAULT NULL,
            estimated_amount decimal(12,2) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            hubspot_contact_id varchar(50) DEFAULT NULL,
            hubspot_synced tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY email (email),
            KEY postcode (postcode),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Set default options
        add_option('m60_calc_min_age', 60);
        add_option('m60_calc_max_age', 95);
        add_option('m60_calc_min_property_value', 200000);
        add_option('m60_calc_max_property_value', 10000000);
        add_option('m60_calc_interest_rate', 8.95);
        add_option('m60_calc_require_phone', false);
        add_option('m60_calc_hubspot_enabled', false);
        add_option('m60_calc_hubspot_api_key', '');
        add_option('m60_calc_notification_email', get_option('admin_email'));
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Cleanup if needed
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Bootstrap 4.6 CSS
        wp_enqueue_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css',
            array(),
            '4.6.2'
        );
        
        // Plugin CSS
        wp_enqueue_style(
            'm60-calculator-css',
            M60_CALC_PLUGIN_URL . 'assets/css/calculator.css',
            array('bootstrap'),
            M60_CALC_VERSION
        );
        
        // Plugin JavaScript
        wp_enqueue_script(
            'm60-calculator-js',
            M60_CALC_PLUGIN_URL . 'assets/js/calculator.js',
            array(),
            M60_CALC_VERSION,
            true
        );
        
        // Localize script with AJAX URL and settings
        wp_localize_script('m60-calculator-js', 'm60CalcConfig', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('m60_calculator_nonce'),
            'minAge' => get_option('m60_calc_min_age', 60),
            'maxAge' => get_option('m60_calc_max_age', 95),
            'minPropertyValue' => get_option('m60_calc_min_property_value', 200000),
            'maxPropertyValue' => get_option('m60_calc_max_property_value', 10000000),
            'requirePhone' => get_option('m60_calc_require_phone', false),
            'currency' => array(
                'symbol' => '$',
                'decimals' => 0,
                'thousand_sep' => ',',
                'decimal_sep' => '.'
            )
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_m60-calculator' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'm60-calculator-admin-css',
            M60_CALC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            M60_CALC_VERSION
        );
    }
    
    /**
     * Calculator shortcode
     */
    public function calculator_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Calculate Your Home Equity',
            'subtitle' => 'Find out how much you could access from your home',
            'show_title' => 'yes',
            'button_text' => 'Calculate Now',
            'button_color' => 'primary',
            'theme' => 'light'
        ), $atts, 'm60_calculator');
        
        ob_start();
        include M60_CALC_PLUGIN_DIR . 'templates/calculator-template.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX: Calculate loan amount
     */
    public function ajax_calculate() {
        check_ajax_referer('m60_calculator_nonce', 'nonce');
        
        $postcode = sanitize_text_field($_POST['postcode']);
        $property_value = floatval($_POST['property_value']);
        $age_primary = intval($_POST['age_primary']);
        $age_partner = isset($_POST['age_partner']) ? intval($_POST['age_partner']) : null;
        
        $calculator = new M60_Calculator_Engine();
        $result = $calculator->calculate($postcode, $property_value, $age_primary, $age_partner);
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Submit lead
     */
    public function ajax_submit_lead() {
        check_ajax_referer('m60_calculator_nonce', 'nonce');
        
        $lead_manager = new M60_Lead_Manager();
        $result = $lead_manager->submit_lead($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Validate postcode
     */
    public function ajax_validate_postcode() {
        check_ajax_referer('m60_calculator_nonce', 'nonce');
        
        $postcode = sanitize_text_field($_POST['postcode']);
        $calculator = new M60_Calculator_Engine();
        $is_valid = $calculator->validate_postcode($postcode);
        
        wp_send_json_success(array(
            'valid' => $is_valid,
            'message' => $is_valid ? 'Postcode is valid' : 'Sorry, we don\'t currently service this area'
        ));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'M60 Calculator',
            'M60 Calculator',
            'manage_options',
            'm60-calculator',
            array($this, 'admin_page'),
            'dashicons-calculator',
            30
        );
        
        add_submenu_page(
            'm60-calculator',
            'Leads',
            'Leads',
            'manage_options',
            'm60-calculator-leads',
            array($this, 'leads_page')
        );
        
        add_submenu_page(
            'm60-calculator',
            'Settings',
            'Settings',
            'manage_options',
            'm60-calculator-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        $settings = new M60_Admin_Settings();
        $settings->register();
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        include M60_CALC_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
    
    /**
     * Leads page
     */
    public function leads_page() {
        include M60_CALC_PLUGIN_DIR . 'templates/admin-leads.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        include M60_CALC_PLUGIN_DIR . 'templates/admin-settings.php';
    }
}

// Initialize the plugin
function m60_calculator_init() {
    return Money_At_60_Calculator::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'm60_calculator_init');

/**
 * Register Activation/Deactivation Hooks OUTSIDE of the init function
 * This ensures WP sees them immediately upon activation.
 */
register_activation_hook(__FILE__, array('Money_At_60_Calculator', 'activate'));
register_deactivation_hook(__FILE__, array('Money_At_60_Calculator', 'deactivate'));