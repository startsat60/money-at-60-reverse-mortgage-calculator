<?php
/**
 * HubSpot Integration Class
 * 
 * Handles syncing leads to HubSpot CRM
 */

if (!defined('ABSPATH')) {
    exit;
}

class M60_HubSpot_Integration {
    
    private $api_key;
    private $api_endpoint = 'https://api.hubapi.com';
    
    public function __construct() {
        $this->api_key = get_option('m60_calc_hubspot_api_key', '');
    }
    
    /**
     * Sync lead to HubSpot
     */
    public function sync_lead($lead_id, $lead_data) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'HubSpot API key not configured'
            );
        }
        
        // Prepare contact properties
        $properties = array(
            'email' => $lead_data['email'],
            'firstname' => $lead_data['first_name'],
            'lastname' => $lead_data['last_name'],
            'phone' => $lead_data['phone'],
            'zip' => $lead_data['postcode'],
            'property_value' => $lead_data['property_value'],
            'age_primary' => $lead_data['age_primary'],
            'lifecyclestage' => 'lead'
        );
        
        // Add custom properties
        if ($lead_data['age_partner']) {
            $properties['age_partner'] = $lead_data['age_partner'];
        }
        
        if ($lead_data['loan_purpose']) {
            $properties['loan_purpose'] = $lead_data['loan_purpose'];
        }
        
        if ($lead_data['estimated_amount']) {
            $properties['estimated_loan_amount'] = $lead_data['estimated_amount'];
        }
        
        // Create or update contact
        $result = $this->create_or_update_contact($properties);
        
        if ($result['success']) {
            // Log successful sync
            error_log('HubSpot sync successful for lead ID: ' . $lead_id);
            
            return array(
                'success' => true,
                'contact_id' => $result['contact_id']
            );
        }
        
        return array(
            'success' => false,
            'message' => $result['message']
        );
    }
    
    /**
     * Create or update HubSpot contact
     */
    private function create_or_update_contact($properties) {
        $url = $this->api_endpoint . '/contacts/v1/contact/createOrUpdate/email/' . urlencode($properties['email']);
        
        $data = array(
            'properties' => array()
        );
        
        foreach ($properties as $key => $value) {
            if ($value !== null && $value !== '') {
                $data['properties'][] = array(
                    'property' => $key,
                    'value' => $value
                );
            }
        }
        
        $response = $this->make_request($url, 'POST', $data);
        
        if ($response['success']) {
            return array(
                'success' => true,
                'contact_id' => $response['data']['vid']
            );
        }
        
        return array(
            'success' => false,
            'message' => $response['message']
        );
    }
    
    /**
     * Make HTTP request to HubSpot API
     */
    private function make_request($url, $method = 'GET', $data = null) {
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            )
        );
        
        if ($data !== null) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code >= 200 && $status_code < 300) {
            return array(
                'success' => true,
                'data' => $data
            );
        }
        
        return array(
            'success' => false,
            'message' => !empty($data['message']) ? $data['message'] : 'Unknown error',
            'status_code' => $status_code
        );
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'API key not configured'
            );
        }
        
        $url = $this->api_endpoint . '/crm/v3/objects/contacts?limit=1';
        $response = $this->make_request($url, 'GET');
        
        if ($response['success']) {
            return array(
                'success' => true,
                'message' => 'Connection successful'
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Connection failed: ' . $response['message']
        );
    }
    
    /**
     * Get HubSpot custom properties
     * Useful for admin setup
     */
    public function get_custom_properties() {
        $url = $this->api_endpoint . '/properties/v1/contacts/properties';
        $response = $this->make_request($url, 'GET');
        
        if ($response['success']) {
            return $response['data'];
        }
        
        return array();
    }
    
    /**
     * Create custom properties if they don't exist
     * This is useful for first-time setup
     */
    public function create_custom_properties() {
        $custom_props = array(
            array(
                'name' => 'age_primary',
                'label' => 'Primary Applicant Age',
                'type' => 'number',
                'fieldType' => 'number',
                'groupName' => 'contactinformation'
            ),
            array(
                'name' => 'age_partner',
                'label' => 'Partner Age',
                'type' => 'number',
                'fieldType' => 'number',
                'groupName' => 'contactinformation'
            ),
            array(
                'name' => 'property_value',
                'label' => 'Property Value',
                'type' => 'number',
                'fieldType' => 'number',
                'groupName' => 'contactinformation'
            ),
            array(
                'name' => 'loan_purpose',
                'label' => 'Loan Purpose',
                'type' => 'string',
                'fieldType' => 'text',
                'groupName' => 'contactinformation'
            ),
            array(
                'name' => 'estimated_loan_amount',
                'label' => 'Estimated Loan Amount',
                'type' => 'number',
                'fieldType' => 'number',
                'groupName' => 'contactinformation'
            )
        );
        
        $results = array();
        
        foreach ($custom_props as $prop) {
            $url = $this->api_endpoint . '/properties/v1/contacts/properties';
            $response = $this->make_request($url, 'POST', $prop);
            $results[] = array(
                'property' => $prop['name'],
                'success' => $response['success'],
                'message' => $response['success'] ? 'Created' : $response['message']
            );
        }
        
        return $results;
    }
}
