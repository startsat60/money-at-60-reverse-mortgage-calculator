<?php
/**
 * Calculator Engine Class
 * 
 * Handles all reverse mortgage calculations
 */

if (!defined('ABSPATH')) {
    exit;
}

class M60_Calculator_Engine {
    
    /**
     * Valid Australian postcodes for service areas
     * You can customize this list based on your service areas
     */
    private $valid_postcodes = array(
        // NSW postcodes (2000-2999)
        'range' => array(
            array('min' => 2000, 'max' => 2999),  // NSW
            array('min' => 3000, 'max' => 3999),  // VIC
            array('min' => 4000, 'max' => 4999),  // QLD
            array('min' => 5000, 'max' => 5999),  // SA
            array('min' => 6000, 'max' => 6999),  // WA
            array('min' => 7000, 'max' => 7999),  // TAS
            array('min' => 800, 'max' => 899),    // NT
            array('min' => 2600, 'max' => 2618),  // ACT
        )
    );
    
    /**
     * Loan-to-Value Ratio (LVR) table based on age
     * Australian reverse mortgage standard LVR percentages
     */
    private function get_lvr_by_age($age) {
        // Base LVR at age 60: 15-20%
        // Add approximately 1% per year over 60
        
        if ($age < 60) {
            return 0;
        }
        
        if ($age >= 60 && $age < 65) {
            return 0.15 + (($age - 60) * 0.01);  // 15% - 20%
        }
        
        if ($age >= 65 && $age < 70) {
            return 0.20 + (($age - 65) * 0.01);  // 20% - 25%
        }
        
        if ($age >= 70 && $age < 75) {
            return 0.25 + (($age - 70) * 0.01);  // 25% - 30%
        }
        
        if ($age >= 75 && $age < 80) {
            return 0.30 + (($age - 75) * 0.01);  // 30% - 35%
        }
        
        if ($age >= 80 && $age < 85) {
            return 0.35 + (($age - 80) * 0.01);  // 35% - 40%
        }
        
        if ($age >= 85) {
            return min(0.40 + (($age - 85) * 0.005), 0.45);  // 40% - 45% (capped)
        }
        
        return 0.20;  // Default fallback
    }
    
    /**
     * Calculate maximum loan amount
     */
    public function calculate($postcode, $property_value, $age_primary, $age_partner = null) {
        // Validate inputs
        if (!$this->validate_postcode($postcode)) {
            return array(
                'error' => true,
                'message' => 'Invalid postcode or area not serviced'
            );
        }
        
        $min_age = get_option('m60_calc_min_age', 60);
        $max_age = get_option('m60_calc_max_age', 95);
        
        if ($age_primary < $min_age || $age_primary > $max_age) {
            return array(
                'error' => true,
                'message' => "Age must be between $min_age and $max_age"
            );
        }
        
        // Use youngest age for couples
        $calculation_age = $age_primary;
        if ($age_partner !== null && $age_partner > 0) {
            $calculation_age = min($age_primary, $age_partner);
        }
        
        // Get LVR based on youngest age
        $lvr = $this->get_lvr_by_age($calculation_age);
        
        // Calculate maximum loan amount
        $max_loan = $property_value * $lvr;
        
        // Apply minimum and maximum loan constraints
        $min_loan = 10000;  // Minimum $10,000
        $max_loan = min($max_loan, 500000);  // Cap at $500,000 (adjust as needed)
        
        if ($max_loan < $min_loan) {
            return array(
                'error' => true,
                'message' => 'Property value or age does not qualify for minimum loan amount'
            );
        }
        
        // Calculate interest projections
        $interest_rate = floatval(get_option('m60_calc_interest_rate', 8.95)) / 100;
        $projections = $this->calculate_projections($max_loan, $interest_rate);
        
        return array(
            'success' => true,
            'property_value' => $property_value,
            'age_primary' => $age_primary,
            'age_partner' => $age_partner,
            'calculation_age' => $calculation_age,
            'lvr_percentage' => round($lvr * 100, 2),
            'max_loan_amount' => round($max_loan, 2),
            'interest_rate' => round($interest_rate * 100, 2),
            'projections' => $projections,
            'estimated_equity_remaining' => round($property_value - $projections['year_10'], 2),
            'breakdown' => $this->get_breakdown($property_value, $max_loan, $lvr)
        );
    }
    
    /**
     * Calculate loan balance projections over time
     */
    private function calculate_projections($initial_loan, $interest_rate) {
        $projections = array();
        $balance = $initial_loan;
        
        // Calculate for years 1, 5, 10, 15, 20
        $years = array(1, 5, 10, 15, 20);
        
        foreach ($years as $year) {
            $balance = $initial_loan * pow(1 + $interest_rate, $year);
            $projections['year_' . $year] = round($balance, 2);
        }
        
        // Add monthly compound interest detail for year 1
        $monthly_rate = $interest_rate / 12;
        $projections['year_1_monthly'] = round($initial_loan * pow(1 + $monthly_rate, 12), 2);
        
        return $projections;
    }
    
    /**
     * Get detailed breakdown
     */
    private function get_breakdown($property_value, $max_loan, $lvr) {
        return array(
            'property_value' => $property_value,
            'max_loan' => $max_loan,
            'lvr' => round($lvr * 100, 2),
            'equity_retained' => round($property_value - $max_loan, 2),
            'equity_retained_percentage' => round((1 - $lvr) * 100, 2)
        );
    }
    
    /**
     * Validate Australian postcode
     */
    public function validate_postcode($postcode) {
        // Clean postcode
        $postcode = preg_replace('/[^0-9]/', '', $postcode);
        
        if (strlen($postcode) !== 4) {
            return false;
        }
        
        $postcode_num = intval($postcode);
        
        // Check against valid ranges
        foreach ($this->valid_postcodes['range'] as $range) {
            if ($postcode_num >= $range['min'] && $postcode_num <= $range['max']) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get state from postcode
     */
    public function get_state_from_postcode($postcode) {
        $postcode_num = intval($postcode);
        
        if ($postcode_num >= 2000 && $postcode_num <= 2999) {
            return 'NSW';
        } elseif ($postcode_num >= 3000 && $postcode_num <= 3999) {
            return 'VIC';
        } elseif ($postcode_num >= 4000 && $postcode_num <= 4999) {
            return 'QLD';
        } elseif ($postcode_num >= 5000 && $postcode_num <= 5999) {
            return 'SA';
        } elseif ($postcode_num >= 6000 && $postcode_num <= 6999) {
            return 'WA';
        } elseif ($postcode_num >= 7000 && $postcode_num <= 7999) {
            return 'TAS';
        } elseif ($postcode_num >= 800 && $postcode_num <= 899) {
            return 'NT';
        } elseif ($postcode_num >= 2600 && $postcode_num <= 2618) {
            return 'ACT';
        }
        
        return 'Unknown';
    }
    
    /**
     * Format currency
     */
    public function format_currency($amount) {
        return '$' . number_format($amount, 0, '.', ',');
    }
    
    /**
     * Get calculator statistics for admin dashboard
     */
    public function get_statistics() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'm60_leads';
        
        $stats = array();
        
        // Total calculations (leads)
        $stats['total_calculations'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Average property value
        $stats['avg_property_value'] = $wpdb->get_var("SELECT AVG(property_value) FROM $table_name");
        
        // Average age
        $stats['avg_age'] = $wpdb->get_var("SELECT AVG(age_primary) FROM $table_name");
        
        // Calculations this month
        $stats['calculations_this_month'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_name 
            WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
            AND YEAR(created_at) = YEAR(CURRENT_DATE())"
        );
        
        // Top postcodes
        $stats['top_postcodes'] = $wpdb->get_results(
            "SELECT postcode, COUNT(*) as count 
            FROM $table_name 
            GROUP BY postcode 
            ORDER BY count DESC 
            LIMIT 10",
            ARRAY_A
        );
        
        return $stats;
    }
}
