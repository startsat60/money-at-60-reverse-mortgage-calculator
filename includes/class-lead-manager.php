<?php
/**
 * Lead Manager Class
 * 
 * Handles lead submission, storage, and notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class M60_Lead_Manager {
    
    /**
     * Submit lead to database
     */
    public function submit_lead($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'm60_leads';
        
        // Validate required fields
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
            return array(
                'success' => false,
                'message' => 'Please provide all required fields'
            );
        }
        
        // Validate email
        if (!is_email($data['email'])) {
            return array(
                'success' => false,
                'message' => 'Please provide a valid email address'
            );
        }
        
        // Check if phone is required
        $require_phone = get_option('m60_calc_require_phone', false);
        if ($require_phone && empty($data['phone'])) {
            return array(
                'success' => false,
                'message' => 'Phone number is required'
            );
        }
        
        // Sanitize and prepare data
        $lead_data = array(
            'first_name' => sanitize_text_field($data['first_name']),
            'last_name' => sanitize_text_field($data['last_name']),
            'email' => sanitize_email($data['email']),
            'phone' => !empty($data['phone']) ? sanitize_text_field($data['phone']) : null,
            'postcode' => sanitize_text_field($data['postcode']),
            'property_value' => floatval($data['property_value']),
            'age_primary' => intval($data['age_primary']),
            'age_partner' => !empty($data['age_partner']) ? intval($data['age_partner']) : null,
            'loan_purpose' => !empty($data['loan_purpose']) ? sanitize_text_field($data['loan_purpose']) : null,
            'estimated_amount' => !empty($data['estimated_amount']) ? floatval($data['estimated_amount']) : null,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => !empty($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : null,
            'created_at' => current_time('mysql')
        );
        
        // Insert lead
        $inserted = $wpdb->insert(
            $table_name,
            $lead_data,
            array('%s', '%s', '%s', '%s', '%s', '%f', '%d', '%d', '%s', '%f', '%s', '%s', '%s')
        );
        
        if (!$inserted) {
            return array(
                'success' => false,
                'message' => 'Error saving lead. Please try again.'
            );
        }
        
        $lead_id = $wpdb->insert_id;
        
        // Send notification email
        $this->send_notification_email($lead_id, $lead_data);
        
        // Sync to HubSpot if enabled
        if (get_option('m60_calc_hubspot_enabled', false)) {
            $hubspot = new M60_HubSpot_Integration();
            $hubspot_result = $hubspot->sync_lead($lead_id, $lead_data);
            
            if ($hubspot_result['success']) {
                $wpdb->update(
                    $table_name,
                    array(
                        'hubspot_contact_id' => $hubspot_result['contact_id'],
                        'hubspot_synced' => 1
                    ),
                    array('id' => $lead_id),
                    array('%s', '%d'),
                    array('%d')
                );
            }
        }
        
        return array(
            'success' => true,
            'message' => 'Thank you! We\'ll be in touch soon.',
            'lead_id' => $lead_id
        );
    }
    
    /**
     * Send notification email
     */
    private function send_notification_email($lead_id, $lead_data) {
        $to = get_option('m60_calc_notification_email', get_option('admin_email'));
        $subject = 'New Reverse Mortgage Inquiry - ' . $lead_data['first_name'] . ' ' . $lead_data['last_name'];
        
        $message = "New reverse mortgage calculator lead:\n\n";
        $message .= "Lead ID: " . $lead_id . "\n";
        $message .= "Name: " . $lead_data['first_name'] . ' ' . $lead_data['last_name'] . "\n";
        $message .= "Email: " . $lead_data['email'] . "\n";
        $message .= "Phone: " . ($lead_data['phone'] ? $lead_data['phone'] : 'Not provided') . "\n";
        $message .= "Postcode: " . $lead_data['postcode'] . "\n";
        $message .= "Property Value: $" . number_format($lead_data['property_value'], 0) . "\n";
        $message .= "Age: " . $lead_data['age_primary'];
        
        if ($lead_data['age_partner']) {
            $message .= " (Partner: " . $lead_data['age_partner'] . ")";
        }
        $message .= "\n";
        
        if ($lead_data['loan_purpose']) {
            $message .= "Loan Purpose: " . $lead_data['loan_purpose'] . "\n";
        }
        
        if ($lead_data['estimated_amount']) {
            $message .= "Estimated Amount: $" . number_format($lead_data['estimated_amount'], 0) . "\n";
        }
        
        $message .= "\n";
        $message .= "Submitted: " . $lead_data['created_at'] . "\n";
        $message .= "IP Address: " . $lead_data['ip_address'] . "\n";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Get all leads
     */
    public function get_leads($limit = 50, $offset = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'm60_leads';
        
        $leads = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            ),
            ARRAY_A
        );
        
        return $leads;
    }
    
    /**
     * Get lead by ID
     */
    public function get_lead($lead_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'm60_leads';
        
        $lead = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $lead_id),
            ARRAY_A
        );
        
        return $lead;
    }
    
    /**
     * Export leads to CSV
     */
    public function export_to_csv($filters = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'm60_leads';
        
        $where = array('1=1');
        
        if (!empty($filters['date_from'])) {
            $where[] = $wpdb->prepare("created_at >= %s", $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = $wpdb->prepare("created_at <= %s", $filters['date_to']);
        }
        
        $query = "SELECT * FROM $table_name WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
        $leads = $wpdb->get_results($query, ARRAY_A);
        
        if (empty($leads)) {
            return false;
        }
        
        // Create CSV
        $filename = 'leads-export-' . date('Y-m-d-His') . '.csv';
        $filepath = wp_upload_dir()['path'] . '/' . $filename;
        
        $fp = fopen($filepath, 'w');
        
        // Headers
        fputcsv($fp, array(
            'ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Postcode',
            'Property Value', 'Age Primary', 'Age Partner', 'Loan Purpose',
            'Estimated Amount', 'IP Address', 'HubSpot Synced', 'Created At'
        ));
        
        // Data
        foreach ($leads as $lead) {
            fputcsv($fp, array(
                $lead['id'],
                $lead['first_name'],
                $lead['last_name'],
                $lead['email'],
                $lead['phone'],
                $lead['postcode'],
                $lead['property_value'],
                $lead['age_primary'],
                $lead['age_partner'],
                $lead['loan_purpose'],
                $lead['estimated_amount'],
                $lead['ip_address'],
                $lead['hubspot_synced'] ? 'Yes' : 'No',
                $lead['created_at']
            ));
        }
        
        fclose($fp);
        
        return array(
            'filepath' => $filepath,
            'filename' => $filename,
            'url' => wp_upload_dir()['url'] . '/' . $filename
        );
    }
}
