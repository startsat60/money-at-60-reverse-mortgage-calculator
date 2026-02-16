/**
 * Money at 60 Calculator - Main JavaScript
 * Native ES5+ JavaScript (No jQuery, No libraries)
 */

(function() {
    'use strict';
    
    // Calculator state
    var calculatorState = {
        currentStep: 1,
        totalSteps: 4,
        data: {
            postcode: '',
            propertyValue: 0,
            agePrimary: 0,
            agePartner: null,
            loanPurpose: '',
            hasPartner: false
        },
        results: null
    };
    
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        initCalculator();
    });
    
    /**
     * Initialize calculator
     */
    function initCalculator() {
        // Check if calculator exists on page
        var calculator = document.querySelector('.m60-calculator-wrapper');
        if (!calculator) {
            return;
        }
        
        // Initialize event listeners
        initEventListeners();
        
        // Initialize formatters
        initFormatters();
    }
    
    /**
     * Initialize all event listeners
     */
    function initEventListeners() {
        // Step 1: Property details
        var nextStep1 = document.getElementById('m60-next-step-1');
        if (nextStep1) {
            nextStep1.addEventListener('click', handleStep1Next);
        }
        
        // Step 2: Age details
        var prevStep2 = document.getElementById('m60-prev-step-2');
        var hasPartnerCheckbox = document.getElementById('m60-has-partner');
        var calculateBtn = document.getElementById('m60-calculate');
        
        if (prevStep2) {
            prevStep2.addEventListener('click', function() {
                navigateToStep(1);
            });
        }
        
        if (hasPartnerCheckbox) {
            hasPartnerCheckbox.addEventListener('change', togglePartnerAge);
        }
        
        if (calculateBtn) {
            calculateBtn.addEventListener('click', handleCalculate);
        }
        
        // Step 3: Results
        var recalculate = document.getElementById('m60-recalculate');
        var nextStep3 = document.getElementById('m60-next-step-3');
        
        if (recalculate) {
            recalculate.addEventListener('click', function() {
                navigateToStep(1);
            });
        }
        
        if (nextStep3) {
            nextStep3.addEventListener('click', function() {
                navigateToStep(4);
            });
        }
        
        // Step 4: Lead form
        var prevStep4 = document.getElementById('m60-prev-step-4');
        var leadForm = document.getElementById('m60-lead-form');
        
        if (prevStep4) {
            prevStep4.addEventListener('click', function() {
                navigateToStep(3);
            });
        }
        
        if (leadForm) {
            leadForm.addEventListener('submit', handleLeadSubmit);
        }
        
        // Postcode validation on blur
        var postcodeInput = document.getElementById('m60-postcode');
        if (postcodeInput) {
            postcodeInput.addEventListener('blur', validatePostcode);
            postcodeInput.addEventListener('input', function() {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    }
    
    /**
     * Initialize number formatters
     */
    function initFormatters() {
        var propertyValueInput = document.getElementById('m60-property-value');
        
        if (propertyValueInput) {
            // Format on blur
            propertyValueInput.addEventListener('blur', function() {
                var value = parseFloat(this.value.replace(/[^0-9.]/g, ''));
                if (!isNaN(value)) {
                    this.value = formatCurrency(value, false);
                }
            });
            
            // Remove formatting on focus
            propertyValueInput.addEventListener('focus', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    }
    
    /**
     * Handle Step 1 Next button
     */
    function handleStep1Next() {
        var postcode = document.getElementById('m60-postcode').value;
        var propertyValue = document.getElementById('m60-property-value').value;
        
        // Validate postcode
        if (!postcode || postcode.length !== 4) {
            showError('postcode-error', 'Please enter a valid 4-digit postcode');
            return;
        }
        
        // Validate property value
        var numericValue = parseFloat(propertyValue.replace(/[^0-9]/g, ''));
        if (!numericValue || isNaN(numericValue)) {
            showError('property-value-error', 'Please enter a valid property value');
            return;
        }
        
        var minValue = m60CalcConfig.minPropertyValue;
        var maxValue = m60CalcConfig.maxPropertyValue;
        
        if (numericValue < minValue) {
            showError('property-value-error', 'Property value must be at least ' + formatCurrency(minValue));
            return;
        }
        
        if (numericValue > maxValue) {
            showError('property-value-error', 'Property value cannot exceed ' + formatCurrency(maxValue));
            return;
        }
        
        // Save data
        calculatorState.data.postcode = postcode;
        calculatorState.data.propertyValue = numericValue;
        
        // Navigate to step 2
        navigateToStep(2);
    }
    
    /**
     * Toggle partner age field
     */
    function togglePartnerAge() {
        var hasPartner = document.getElementById('m60-has-partner').checked;
        var partnerAgeGroup = document.getElementById('m60-partner-age-group');
        
        calculatorState.data.hasPartner = hasPartner;
        
        if (hasPartner) {
            partnerAgeGroup.style.display = 'block';
        } else {
            partnerAgeGroup.style.display = 'none';
            document.getElementById('m60-age-partner').value = '';
            calculatorState.data.agePartner = null;
        }
    }
    
    /**
     * Handle calculate button
     */
    function handleCalculate() {
        var agePrimary = parseInt(document.getElementById('m60-age-primary').value);
        var agePartner = document.getElementById('m60-age-partner').value;
        var loanPurpose = document.getElementById('m60-loan-purpose').value;
        
        // Validate primary age
        if (!agePrimary || isNaN(agePrimary)) {
            showError('age-primary-error', 'Please enter your age');
            return;
        }
        
        var minAge = m60CalcConfig.minAge;
        var maxAge = m60CalcConfig.maxAge;
        
        if (agePrimary < minAge || agePrimary > maxAge) {
            showError('age-primary-error', 'Age must be between ' + minAge + ' and ' + maxAge);
            return;
        }
        
        // Validate partner age if provided
        if (calculatorState.data.hasPartner && agePartner) {
            agePartner = parseInt(agePartner);
            if (isNaN(agePartner) || agePartner < minAge || agePartner > maxAge) {
                showError('age-partner-error', 'Partner age must be between ' + minAge + ' and ' + maxAge);
                return;
            }
        } else {
            agePartner = null;
        }
        
        // Save data
        calculatorState.data.agePrimary = agePrimary;
        calculatorState.data.agePartner = agePartner;
        calculatorState.data.loanPurpose = loanPurpose;
        
        // Show loading
        var calculateBtn = document.getElementById('m60-calculate');
        var spinner = calculateBtn.querySelector('.spinner-border');
        calculateBtn.disabled = true;
        spinner.classList.remove('d-none');
        
        // Make AJAX request
        var data = new FormData();
        data.append('action', 'm60_calculate');
        data.append('nonce', m60CalcConfig.nonce);
        data.append('postcode', calculatorState.data.postcode);
        data.append('property_value', calculatorState.data.propertyValue);
        data.append('age_primary', calculatorState.data.agePrimary);
        if (calculatorState.data.agePartner) {
            data.append('age_partner', calculatorState.data.agePartner);
        }
        
        fetch(m60CalcConfig.ajaxUrl, {
            method: 'POST',
            body: data
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(response) {
            calculateBtn.disabled = false;
            spinner.classList.add('d-none');
            
            if (response.success) {
                calculatorState.results = response.data;
                displayResults(response.data);
                navigateToStep(3);
            } else {
                alert('Error calculating: ' + (response.data.message || 'Unknown error'));
            }
        })
        .catch(function(error) {
            calculateBtn.disabled = false;
            spinner.classList.add('d-none');
            alert('Error: ' + error.message);
        });
    }
    
    /**
     * Display calculation results
     */
    function displayResults(results) {
        // Main amount
        document.getElementById('m60-result-amount').textContent = formatCurrency(results.max_loan_amount);
        
        // Stats
        document.getElementById('m60-result-property-value').textContent = formatCurrency(results.property_value);
        
        var ageDisplay = results.age_primary;
        if (results.age_partner) {
            ageDisplay += ' / ' + results.age_partner;
        }
        document.getElementById('m60-result-age').textContent = ageDisplay;
        document.getElementById('m60-result-lvr').textContent = results.lvr_percentage + '%';
        document.getElementById('m60-interest-rate').textContent = results.interest_rate + '%';
        
        // Projection table
        var projectionTable = document.getElementById('m60-projection-table');
        projectionTable.innerHTML = '';
        
        var years = [1, 5, 10, 15, 20];
        years.forEach(function(year) {
            var balance = results.projections['year_' + year];
            if (balance) {
                var row = document.createElement('tr');
                row.innerHTML = '<td>Year ' + year + '</td><td>' + formatCurrency(balance) + '</td>';
                projectionTable.appendChild(row);
            }
        });
    }
    
    /**
     * Handle lead form submission
     */
    function handleLeadSubmit(e) {
        e.preventDefault();
        
        var firstName = document.getElementById('m60-first-name').value;
        var lastName = document.getElementById('m60-last-name').value;
        var email = document.getElementById('m60-email').value;
        var phone = document.getElementById('m60-phone').value;
        var consent = document.getElementById('m60-consent').checked;
        
        // Validate
        if (!firstName || !lastName || !email) {
            showFormError('Please fill in all required fields');
            return;
        }
        
        if (!consent) {
            showFormError('Please agree to be contacted');
            return;
        }
        
        if (m60CalcConfig.requirePhone && !phone) {
            showFormError('Phone number is required');
            return;
        }
        
        // Show loading
        var submitBtn = document.getElementById('m60-submit-lead');
        var spinner = submitBtn.querySelector('.spinner-border');
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
        
        // Prepare data
        var data = new FormData();
        data.append('action', 'm60_submit_lead');
        data.append('nonce', m60CalcConfig.nonce);
        data.append('first_name', firstName);
        data.append('last_name', lastName);
        data.append('email', email);
        data.append('phone', phone);
        data.append('postcode', calculatorState.data.postcode);
        data.append('property_value', calculatorState.data.propertyValue);
        data.append('age_primary', calculatorState.data.agePrimary);
        if (calculatorState.data.agePartner) {
            data.append('age_partner', calculatorState.data.agePartner);
        }
        if (calculatorState.data.loanPurpose) {
            data.append('loan_purpose', calculatorState.data.loanPurpose);
        }
        if (calculatorState.results) {
            data.append('estimated_amount', calculatorState.results.max_loan_amount);
        }
        
        // Submit
        fetch(m60CalcConfig.ajaxUrl, {
            method: 'POST',
            body: data
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(response) {
            submitBtn.disabled = false;
            spinner.classList.add('d-none');
            
            if (response.success) {
                showFormSuccess(response.data.message || 'Thank you! We\'ll be in touch soon.');
                document.getElementById('m60-lead-form').reset();
            } else {
                showFormError(response.data.message || 'Error submitting form');
            }
        })
        .catch(function(error) {
            submitBtn.disabled = false;
            spinner.classList.add('d-none');
            showFormError('Error: ' + error.message);
        });
    }
    
    /**
     * Validate postcode via AJAX
     */
    function validatePostcode() {
        var postcodeInput = document.getElementById('m60-postcode');
        var postcode = postcodeInput.value;
        
        if (!postcode || postcode.length !== 4) {
            return;
        }
        
        var data = new FormData();
        data.append('action', 'm60_validate_postcode');
        data.append('nonce', m60CalcConfig.nonce);
        data.append('postcode', postcode);
        
        fetch(m60CalcConfig.ajaxUrl, {
            method: 'POST',
            body: data
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(response) {
            if (response.success && !response.data.valid) {
                showError('postcode-error', response.data.message);
                postcodeInput.classList.add('is-invalid');
            } else {
                hideError('postcode-error');
                postcodeInput.classList.remove('is-invalid');
            }
        });
    }
    
    /**
     * Navigate to specific step
     */
    function navigateToStep(stepNumber) {
        // Hide all steps
        var steps = document.querySelectorAll('.m60-calc-step');
        steps.forEach(function(step) {
            step.classList.remove('active');
        });
        
        // Show target step
        var targetStep = document.getElementById('m60-step-' + stepNumber);
        if (targetStep) {
            targetStep.classList.add('active');
        }
        
        // Update progress
        updateProgress(stepNumber);
        
        // Update state
        calculatorState.currentStep = stepNumber;
        
        // Scroll to top
        var calculator = document.querySelector('.m60-calculator-wrapper');
        if (calculator) {
            calculator.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
    
    /**
     * Update progress indicator
     */
    function updateProgress(currentStep) {
        // Update step indicators
        var progressSteps = document.querySelectorAll('.m60-progress-step');
        progressSteps.forEach(function(step, index) {
            if (index + 1 < currentStep) {
                step.classList.add('completed');
                step.classList.remove('active');
            } else if (index + 1 === currentStep) {
                step.classList.add('active');
                step.classList.remove('completed');
            } else {
                step.classList.remove('active', 'completed');
            }
        });
        
        // Update progress bar
        var progressBar = document.querySelector('.m60-progress-bar-fill');
        if (progressBar) {
            var percentage = (currentStep / calculatorState.totalSteps) * 100;
            progressBar.style.width = percentage + '%';
        }
    }
    
    /**
     * Format currency
     */
    function formatCurrency(amount, showCents) {
        if (typeof showCents === 'undefined') {
            showCents = false;
        }
        
        var formatted = Number(amount).toLocaleString('en-AU', {
            minimumFractionDigits: showCents ? 2 : 0,
            maximumFractionDigits: showCents ? 2 : 0
        });
        
        return '$' + formatted;
    }
    
    /**
     * Show error message
     */
    function showError(elementId, message) {
        var errorElement = document.getElementById(elementId);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }
    
    /**
     * Hide error message
     */
    function hideError(elementId) {
        var errorElement = document.getElementById(elementId);
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
    }
    
    /**
     * Show form error
     */
    function showFormError(message) {
        var errorDiv = document.getElementById('m60-form-error');
        var successDiv = document.getElementById('m60-form-success');
        
        if (successDiv) {
            successDiv.classList.add('d-none');
        }
        
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.remove('d-none');
        }
    }
    
    /**
     * Show form success
     */
    function showFormSuccess(message) {
        var errorDiv = document.getElementById('m60-form-error');
        var successDiv = document.getElementById('m60-form-success');
        
        if (errorDiv) {
            errorDiv.classList.add('d-none');
        }
        
        if (successDiv) {
            successDiv.textContent = message;
            successDiv.classList.remove('d-none');
        }
    }
    
})();
