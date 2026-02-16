<?php
/**
 * Calculator Template
 * 
 * Main template for the reverse mortgage calculator
 * Variables available: $atts (shortcode attributes)
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="m60-calculator-wrapper" data-theme="<?php echo esc_attr($atts['theme']); ?>">
    <div class="container">
        
        <?php if ($atts['show_title'] === 'yes') : ?>
        <div class="m60-calc-header text-center mb-4">
            <h2 class="m60-calc-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php if (!empty($atts['subtitle'])) : ?>
            <p class="m60-calc-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="m60-calculator-container">
            
            <!-- Progress Indicator -->
            <div class="m60-progress-wrapper">
                <div class="m60-progress-steps">
                    <div class="m60-progress-step active" data-step="1">
                        <div class="m60-progress-step-circle">1</div>
                        <div class="m60-progress-step-label">Property</div>
                    </div>
                    <div class="m60-progress-step" data-step="2">
                        <div class="m60-progress-step-circle">2</div>
                        <div class="m60-progress-step-label">Age</div>
                    </div>
                    <div class="m60-progress-step" data-step="3">
                        <div class="m60-progress-step-circle">3</div>
                        <div class="m60-progress-step-label">Results</div>
                    </div>
                    <div class="m60-progress-step" data-step="4">
                        <div class="m60-progress-step-circle">4</div>
                        <div class="m60-progress-step-label">Contact</div>
                    </div>
                </div>
                <div class="m60-progress-bar">
                    <div class="m60-progress-bar-fill" style="width: 25%"></div>
                </div>
            </div>
            
            <!-- Step 1: Property Details -->
            <div class="m60-calc-step active" id="m60-step-1">
                <div class="m60-step-content">
                    <h3 class="m60-step-title">Tell us about your property</h3>
                    
                    <div class="form-group">
                        <label for="m60-postcode">Property Postcode *</label>
                        <input 
                            type="text" 
                            class="form-control form-control-lg" 
                            id="m60-postcode" 
                            placeholder="e.g., 3000"
                            maxlength="4"
                            required
                        />
                        <div class="invalid-feedback" id="postcode-error"></div>
                        <small class="form-text text-muted">We need this to confirm we service your area</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="m60-property-value">Estimated Property Value *</label>
                        <div class="input-group input-group-lg">
                            <div class="input-group-prepend">
                                <span class="input-group-text">$</span>
                            </div>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="m60-property-value" 
                                placeholder="e.g., 750,000"
                                required
                            />
                        </div>
                        <div class="invalid-feedback" id="property-value-error"></div>
                        <small class="form-text text-muted">Enter your best estimate of your home's current value</small>
                    </div>
                    
                    <div class="m60-step-actions">
                        <button type="button" class="btn btn-<?php echo esc_attr($atts['button_color']); ?> btn-lg" id="m60-next-step-1">
                            Continue <span class="arrow-right">→</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Step 2: Age Details -->
            <div class="m60-calc-step" id="m60-step-2">
                <div class="m60-step-content">
                    <h3 class="m60-step-title">Tell us about yourself</h3>
                    
                    <div class="form-group">
                        <label for="m60-age-primary">Your Age *</label>
                        <input 
                            type="number" 
                            class="form-control form-control-lg" 
                            id="m60-age-primary" 
                            placeholder="e.g., 65"
                            min="60"
                            max="95"
                            required
                        />
                        <div class="invalid-feedback" id="age-primary-error"></div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input 
                            type="checkbox" 
                            class="form-check-input" 
                            id="m60-has-partner"
                        />
                        <label class="form-check-label" for="m60-has-partner">
                            I have a spouse/partner who will also be on the loan
                        </label>
                    </div>
                    
                    <div class="form-group" id="m60-partner-age-group" style="display: none;">
                        <label for="m60-age-partner">Partner's Age</label>
                        <input 
                            type="number" 
                            class="form-control form-control-lg" 
                            id="m60-age-partner" 
                            placeholder="e.g., 63"
                            min="60"
                            max="95"
                        />
                        <div class="invalid-feedback" id="age-partner-error"></div>
                        <small class="form-text text-muted">
                            For couples, we calculate based on the younger age
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="m60-loan-purpose">What would you use the funds for? (Optional)</label>
                        <select class="form-control form-control-lg" id="m60-loan-purpose">
                            <option value="">Select a purpose...</option>
                            <option value="Home Renovations">Home Renovations</option>
                            <option value="Debt Consolidation">Debt Consolidation</option>
                            <option value="Living Expenses">Living Expenses</option>
                            <option value="Travel">Travel</option>
                            <option value="Healthcare">Healthcare</option>
                            <option value="Help Family">Help Family</option>
                            <option value="Aged Care">Aged Care</option>
                            <option value="Investment">Investment</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="m60-step-actions">
                        <button type="button" class="btn btn-outline-secondary btn-lg" id="m60-prev-step-2">
                            <span class="arrow-left">←</span> Back
                        </button>
                        <button type="button" class="btn btn-<?php echo esc_attr($atts['button_color']); ?> btn-lg" id="m60-calculate">
                            Calculate My Estimate <span class="spinner-border spinner-border-sm d-none ml-2"></span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Results -->
            <div class="m60-calc-step" id="m60-step-3">
                <div class="m60-step-content">
                    <h3 class="m60-step-title">Your Estimated Borrowing Capacity</h3>
                    
                    <div class="m60-results-container">
                        <div class="m60-result-highlight">
                            <div class="m60-result-label">You could access up to</div>
                            <div class="m60-result-amount" id="m60-result-amount">$0</div>
                            <div class="m60-result-note">from your home equity</div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="m60-result-stat">
                                    <div class="m60-stat-label">Property Value</div>
                                    <div class="m60-stat-value" id="m60-result-property-value">$0</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="m60-result-stat">
                                    <div class="m60-stat-label">Your Age</div>
                                    <div class="m60-stat-value" id="m60-result-age">0</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="m60-result-stat">
                                    <div class="m60-stat-label">LVR Available</div>
                                    <div class="m60-stat-value" id="m60-result-lvr">0%</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="m60-projections mt-4">
                            <h4>Loan Balance Projections</h4>
                            <p class="text-muted small">
                                These projections assume no repayments are made and show how compound interest affects your loan balance over time.
                            </p>
                            
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Year</th>
                                            <th>Projected Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody id="m60-projection-table">
                                        <!-- Populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <strong>Important:</strong> This is an estimate only. Your actual borrowing capacity will depend on our lender assessment and your specific circumstances. Interest rate used: <span id="m60-interest-rate">0%</span> p.a.
                            </div>
                        </div>
                    </div>
                    
                    <div class="m60-step-actions">
                        <button type="button" class="btn btn-outline-secondary btn-lg" id="m60-recalculate">
                            <span class="arrow-left">←</span> Recalculate
                        </button>
                        <button type="button" class="btn btn-<?php echo esc_attr($atts['button_color']); ?> btn-lg" id="m60-next-step-3">
                            Get My Personalised Quote <span class="arrow-right">→</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Step 4: Contact Details -->
            <div class="m60-calc-step" id="m60-step-4">
                <div class="m60-step-content">
                    <h3 class="m60-step-title">Get Your Personalised Quote</h3>
                    <p class="text-muted">One of our specialist brokers will be in touch to discuss your options</p>
                    
                    <form id="m60-lead-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="m60-first-name">First Name *</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="m60-first-name" 
                                        required
                                    />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="m60-last-name">Last Name *</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="m60-last-name" 
                                        required
                                    />
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="m60-email">Email Address *</label>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="m60-email" 
                                required
                            />
                        </div>
                        
                        <div class="form-group">
                            <label for="m60-phone">Phone Number <span id="m60-phone-required"></span></label>
                            <input 
                                type="tel" 
                                class="form-control" 
                                id="m60-phone" 
                                placeholder="e.g., 0412 345 678"
                            />
                        </div>
                        
                        <div class="form-check mb-3">
                            <input 
                                type="checkbox" 
                                class="form-check-input" 
                                id="m60-consent" 
                                required
                            />
                            <label class="form-check-label" for="m60-consent">
                                I agree to be contacted about my reverse mortgage inquiry *
                            </label>
                        </div>
                        
                        <div class="alert alert-danger d-none" id="m60-form-error"></div>
                        <div class="alert alert-success d-none" id="m60-form-success"></div>
                        
                        <div class="m60-step-actions">
                            <button type="button" class="btn btn-outline-secondary btn-lg" id="m60-prev-step-4">
                                <span class="arrow-left">←</span> Back
                            </button>
                            <button type="submit" class="btn btn-<?php echo esc_attr($atts['button_color']); ?> btn-lg" id="m60-submit-lead">
                                Submit <span class="spinner-border spinner-border-sm d-none ml-2"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</div>
