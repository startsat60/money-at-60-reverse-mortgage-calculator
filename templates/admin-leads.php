<?php
/**
 * Admin Leads Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$lead_manager = new M60_Lead_Manager();
$leads = $lead_manager->get_leads(50, 0);
?>

<div class="wrap">
    <h1>Calculator Leads</h1>
    
    <div class="m60-admin-leads">
        <div class="tablenav top">
            <div class="alignleft actions">
                <button type="button" class="button" id="export-leads">Export to CSV</button>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Postcode</th>
                    <th>Property Value</th>
                    <th>Age</th>
                    <th>HubSpot</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($leads)) : ?>
                    <?php foreach ($leads as $lead) : ?>
                        <tr>
                            <td><?php echo esc_html($lead['id']); ?></td>
                            <td>
                                <strong><?php echo esc_html($lead['first_name'] . ' ' . $lead['last_name']); ?></strong>
                            </td>
                            <td><?php echo esc_html($lead['email']); ?></td>
                            <td><?php echo esc_html($lead['phone'] ?: '-'); ?></td>
                            <td><?php echo esc_html($lead['postcode']); ?></td>
                            <td>$<?php echo number_format($lead['property_value'], 0); ?></td>
                            <td>
                                <?php 
                                echo esc_html($lead['age_primary']);
                                if ($lead['age_partner']) {
                                    echo ' / ' . esc_html($lead['age_partner']);
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($lead['hubspot_synced']) : ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                <?php else : ?>
                                    <span class="dashicons dashicons-minus"></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($lead['created_at']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="9">No leads yet</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('export-leads').addEventListener('click', function() {
    if (confirm('Export all leads to CSV?')) {
        window.location.href = ajaxurl + '?action=m60_export_leads&nonce=<?php echo wp_create_nonce('m60_export'); ?>';
    }
});
</script>
