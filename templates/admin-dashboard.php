<?php
/**
 * Admin Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$calculator = new M60_Calculator_Engine();
$stats = $calculator->get_statistics();
?>

<div class="wrap">
    <h1>Money at 60 Calculator Dashboard</h1>
    
    <div class="m60-admin-dashboard">
        <div class="m60-stats-grid">
            <div class="m60-stat-card">
                <h3>Total Calculations</h3>
                <p class="m60-stat-number"><?php echo number_format($stats['total_calculations']); ?></p>
            </div>
            
            <div class="m60-stat-card">
                <h3>This Month</h3>
                <p class="m60-stat-number"><?php echo number_format($stats['calculations_this_month']); ?></p>
            </div>
            
            <div class="m60-stat-card">
                <h3>Avg Property Value</h3>
                <p class="m60-stat-number">$<?php echo number_format($stats['avg_property_value'], 0); ?></p>
            </div>
            
            <div class="m60-stat-card">
                <h3>Avg Age</h3>
                <p class="m60-stat-number"><?php echo round($stats['avg_age']); ?></p>
            </div>
        </div>
        
        <div class="m60-admin-section">
            <h2>Top Postcodes</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Postcode</th>
                        <th>Calculations</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($stats['top_postcodes'])) : ?>
                        <?php foreach ($stats['top_postcodes'] as $postcode) : ?>
                            <tr>
                                <td><?php echo esc_html($postcode['postcode']); ?></td>
                                <td><?php echo esc_html($postcode['count']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="2">No data available yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="m60-admin-section">
            <h2>Quick Start</h2>
            <p>Use the following shortcode to add the calculator to any page or post:</p>
            <code>[m60_calculator]</code>
            
            <h3>Shortcode Options:</h3>
            <ul>
                <li><code>title="Your Custom Title"</code> - Set custom title</li>
                <li><code>subtitle="Your subtitle"</code> - Set custom subtitle</li>
                <li><code>show_title="no"</code> - Hide title section</li>
                <li><code>button_color="primary|success|danger"</code> - Change button color</li>
                <li><code>theme="light|dark"</code> - Set theme</li>
            </ul>
            
            <h3>Example:</h3>
            <code>[m60_calculator title="Calculate Your Home Equity" button_color="success"]</code>
        </div>
    </div>
</div>
