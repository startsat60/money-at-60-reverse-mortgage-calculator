<?php

/**
 * Calculator Engine Class
 * 
 * Handles all reverse mortgage calculations
 */

if (!defined('ABSPATH')) {
	exit;
}

class M60_Calculator_Engine
{
	/**
	 * Valid Australian postcodes
	 */
	private $valid_postcodes;

	/**
	 * Constructor: Load the postcode data
	 */
	public function __construct()
	{
		// Use __DIR__ to ensure we look in the current directory, regardless of where this script is called from
		$file_path = __DIR__ . '/postcodes.php';

		if (file_exists($file_path)) {
			$this->valid_postcodes = require $file_path;
		} else {
			// Fallback or error logging if file is missing
			$this->valid_postcodes = [];
			error_log('M60 Calculator: postcodes.php not found.');
		}
	}

	/**
	 * Get Postcode Status
	 * * Returns the status string (e.g., 'Approved', 'Refer', 'Ineligible') 
	 * or FALSE if the postcode does not exist in the database.
	 * * @param string|int $postcode
	 * @return string|false
	 */
	public function get_postcode_status($postcode)
	{
		// Clean inputs: Remove non-digits
		$clean_code = preg_replace('/[^0-9]/', '', $postcode);

		// Ensure 4 digits (e.g. 800 becomes "0800")
		$code_string = str_pad($clean_code, 4, '0', STR_PAD_LEFT);

		// Return the value from the map, or false if not found
		return $this->valid_postcodes[$code_string] ?? false;
	}

	/**
	 * Loan-to-Value Ratio (LVR) table based on age
	 * Australian reverse mortgage standard LVR percentages
	 */
	private function get_lvr_by_age($age)
	{
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
	public function calculate($postcode, $property_value, $age_primary, $age_partner = null)
	{
		$status = $this->get_postcode_status($postcode);

		if ($status === false) {
			return array(
				'error' => true,
				'message' => 'Postcode not found. Please check and try again.'
			);
		}

		if ($status === 'Ineligible') {
			return array(
				'error' => true,
				'message' => 'Unfortunately, we do not currently lend in this postcode.'
			);
		}

		if ($status === 'Refer') {
			return array(
				'error' => true,
				'message' => 'Your postcode requires further review. Please contact us for more information.'
			);
		}

		$min_age = get_option('m60_calc_min_age', 60);
		//$max_age = get_option('m60_calc_max_age', 95);

		if (
			$age_primary < $min_age
			// || $age_primary > $max_age
		) {
			return array(
				'error' => true,
				//'message' => "Age must be between $min_age and $max_age"
				'message' => "Age must be at least $min_age"
			);
		}

		// Use youngest age for couples
		$calculation_age = $age_primary;
		if ($age_partner !== null && $age_partner > 0) {
			$calculation_age = min($age_primary, $age_partner);
		}

		// Get LVR based on youngest age
		// $lvr = $this->get_lvr_by_age($calculation_age);
		$lvr = $calculation_age - 40;

		// Calculate maximum loan amount
		$max_loan = $property_value * (0.01 * $lvr);

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
			'lvr_percentage' => round($lvr, 2),
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
	private function calculate_projections($initial_loan, $interest_rate)
	{
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
	private function get_breakdown($property_value, $max_loan, $lvr)
	{
		return array(
			'property_value' => $property_value,
			'max_loan' => $max_loan,
			'lvr' => round($lvr * 100, 2),
			'equity_retained' => round($property_value - $max_loan, 2),
			'equity_retained_percentage' => round((1 - $lvr) * 100, 2)
		);
	}

	/**
	 * Validates a postcode against the mapped list.
	 * * @param string|int $selectedPostcode The input postcode (e.g. "0800" or 800)
	 * @param array $map The associative array of postcodes
	 * @return string
	 */
	private static function getPostcodeStatus($selectedPostcode, $map)
	{
		// 1. Clean the input: ensure it is a string and pad to 4 digits
		//    (e.g. turns int 800 into string "0800")
		$code = str_pad((string)$selectedPostcode, 4, '0', STR_PAD_LEFT);

		// 2. Return the value if it exists, or the default
		//    The null coalescing operator (??) handles the "undefined index" check safely
		return $map[$code] ?? 'no match found';
	}

	/**
	 * Format currency
	 */
	public function format_currency($amount)
	{
		return '$' . number_format($amount, 0, '.', ',');
	}

	/**
	 * Get calculator statistics for admin dashboard
	 */
	public function get_statistics()
	{
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
