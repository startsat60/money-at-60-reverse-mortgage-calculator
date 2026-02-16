<?php
/**
 * Admin Settings Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Calculator Settings</h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('m60_calc_settings');
        do_settings_sections('m60_calc_settings');
        submit_button();
        ?>
    </form>
</div>

<style>
.m60-admin-dashboard {
    margin-top: 20px;
}

.m60-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.m60-stat-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.m60-stat-card h3 {
    margin: 0 0 10px;
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
    font-weight: 600;
}

.m60-stat-number {
    font-size: 32px;
    font-weight: 700;
    color: #2C5F8D;
    margin: 0;
}

.m60-admin-section {
    background: white;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
}

.m60-admin-section h2 {
    margin-top: 0;
}

.m60-admin-section code {
    background: #f5f5f5;
    padding: 4px 8px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
}
</style>
