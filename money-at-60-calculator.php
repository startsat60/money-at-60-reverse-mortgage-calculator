<?php
/**
 * Plugin Name: M60 Reverse Mortgage Lead Calculator
 * Description: Shortcode-based reverse mortgage borrowing capacity calculator + lead capture.
 * Version: 1.0.0
 * Author: Money at 60 (Internal)
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit;

final class M60_RM_Calculator_Plugin {
  const SHORTCODE = 'm60_reverse_mortgage_calculator';
  const CPT_LEAD  = 'm60_rm_lead';

  public static function boot(): void {
    add_action('init', [__CLASS__, 'register_cpt']);
    add_shortcode(self::SHORTCODE, [__CLASS__, 'render_shortcode']);
    add_action('wp_enqueue_scripts', [__CLASS__, 'maybe_enqueue_assets']);

    add_action('wp_ajax_m60_rm_calculate', [__CLASS__, 'ajax_calculate']);
    add_action('wp_ajax_nopriv_m60_rm_calculate', [__CLASS__, 'ajax_calculate']);

    add_action('wp_ajax_m60_rm_submit_lead', [__CLASS__, 'ajax_submit_lead']);
    add_action('wp_ajax_nopriv_m60_rm_submit_lead', [__CLASS__, 'ajax_submit_lead']);
  }

  public static function register_cpt(): void {
    register_post_type(self::CPT_LEAD, [
      'label' => 'RM Leads',
      'public' => false,
      'show_ui' => true,
      'menu_icon' => 'dashicons-id',
      'supports' => ['title', 'custom-fields'],
      'capability_type' => 'post',
    ]);
  }

  public static function render_shortcode(array $atts = []): string {
    $atts = shortcode_atts([
      'min_age' => 60,
      'min_property_value' => 600000,
      'min_loan' => 50000,
      'max_loan' => 2000000,
      'max_lvr' => 0.50,
      'notify_email' => get_option('admin_email'),
      'enable_leads' => '1',
    ], $atts, self::SHORTCODE);

    $uid = 'm60rm_' . wp_generate_uuid4();

    $config = [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('m60_rm_nonce'),
      'minAge' => (int)$atts['min_age'],
      'minPropertyValue' => (int)$atts['min_property_value'],
      'minLoan' => (int)$atts['min_loan'],
      'maxLoan' => (int)$atts['max_loan'],
      'maxLvr' => (float)$atts['max_lvr'],
      'notifyEmail' => sanitize_email($atts['notify_email']),
      'enableLeads' => $atts['enable_leads'] === '1',
    ];

    $html = '<div class="m60-rm-calculator" id="'.esc_attr($uid).'" data-config="'.esc_attr(wp_json_encode($config)).'">
      <div class="m60-rm-card">
        <div class="m60-rm-steps">
          <div class="m60-rm-step is-active" data-step="1">
            <h3 class="m60-rm-h">Calculate your home wealth</h3>

            <label class="m60-rm-label">What is the postcode of the property? <span class="m60-rm-req">*</span></label>
            <input class="m60-rm-input" type="text" inputmode="numeric" maxlength="4" name="postcode" placeholder="e.g. 3000" />

            <label class="m60-rm-label">What is the estimated value? <span class="m60-rm-req">*</span></label>
            <input class="m60-rm-input" type="text" inputmode="numeric" name="property_value" placeholder="e.g. 900,000" />

            <div class="m60-rm-actions">
              <button class="m60-rm-btn" data-action="next">Next</button>
            </div>

            <div class="m60-rm-error" role="alert" aria-live="polite"></div>
          </div>

          <div class="m60-rm-step" data-step="2">
            <h3 class="m60-rm-h">A few more details</h3>

            <label class="m60-rm-label">Age of youngest borrower <span class="m60-rm-req">*</span></label>
            <input class="m60-rm-input" type="text" inputmode="numeric" maxlength="3" name="age" placeholder="e.g. 67" />

            <div class="m60-rm-actions">
              <button class="m60-rm-btn m60-rm-btn--ghost" data-action="back">Back</button>
              <button class="m60-rm-btn" data-action="calculate">Calculate</button>
            </div>

            <div class="m60-rm-error" role="alert" aria-live="polite"></div>
          </div>

          <div class="m60-rm-step" data-step="3">
            <h3 class="m60-rm-h">Your estimate</h3>

            <div class="m60-rm-result">
              <div class="m60-rm-result__label">Estimated amount you could unlock</div>
              <div class="m60-rm-result__value" data-bind="amount">$0</div>
              <div class="m60-rm-result__meta" data-bind="meta"></div>
            </div>

            <div class="m60-rm-disclaimer" data-bind="disclaimer">
              This is an estimate only and does not constitute financial advice or a credit assessment.
            </div>

            <div class="m60-rm-lead" data-section="lead">
              <h4 class="m60-rm-h4">Want to discuss your options?</h4>

              <label class="m60-rm-label">Full name <span class="m60-rm-req">*</span></label>
              <input class="m60-rm-input" type="text" name="full_name" />

              <label class="m60-rm-label">Email <span class="m60-rm-req">*</span></label>
              <input class="m60-rm-input" type="email" name="email" />

              <label class="m60-rm-label">Phone <span class="m60-rm-req">*</span></label>
              <input class="m60-rm-input" type="tel" name="phone" />

              <label class="m60-rm-check">
                <input type="checkbox" name="consent" />
                <span>I agree to be contacted about my enquiry.</span>
              </label>

              <div class="m60-rm-actions">
                <button class="m60-rm-btn m60-rm-btn--ghost" data-action="backToInputs">Edit inputs</button>
                <button class="m60-rm-btn" data-action="submitLead">Request a call</button>
              </div>

              <div class="m60-rm-error" role="alert" aria-live="polite"></div>
              <div class="m60-rm-success" role="status" aria-live="polite"></div>
            </div>

            <div class="m60-rm-actions">
              <button class="m60-rm-btn m60-rm-btn--ghost" data-action="restart">Start over</button>
            </div>
          </div>
        </div>
      </div>
    </div>';

    return $html;
  }

  public static function maybe_enqueue_assets(): void {
    if (!is_singular() && !is_page()) return;

    $post = get_post();
    if (!$post || !has_shortcode($post->post_content ?? '', self::SHORTCODE)) return;

    wp_register_style('m60-rm-inline', false, [], '1.0.0');
    wp_enqueue_style('m60-rm-inline');
    wp_add_inline_style('m60-rm-inline', self::css());

    wp_register_script('m60-rm-inline', '', [], '1.0.0', true);
    wp_enqueue_script('m60-rm-inline');
    wp_add_inline_script('m60-rm-inline', self::js());
  }

  public static function ajax_calculate(): void {
    self::require_nonce();

    $postcode = self::sanitize_postcode($_POST['postcode'] ?? '');
    $property_value = self::sanitize_money($_POST['property_value'] ?? '');
    $age = self::sanitize_int($_POST['age'] ?? '');

    $min_age = (int)($_POST['minAge'] ?? 60);
    $min_property_value = (int)($_POST['minPropertyValue'] ?? 600000);
    $min_loan = (int)($_POST['minLoan'] ?? 50000);
    $max_loan = (int)($_POST['maxLoan'] ?? 2000000);
    $max_lvr = (float)($_POST['maxLvr'] ?? 0.50);

    $errors = [];

    if ($postcode === null) $errors[] = 'Please enter a valid 4-digit Australian postcode.';
    if ($property_value === null || $property_value <= 0) $errors[] = 'Please enter a valid property value.';
    if ($age === null || $age <= 0) $errors[] = 'Please enter a valid age.';

    if (!$errors) {
      if ($age < $min_age) $errors[] = 'Minimum age is '.$min_age.'.';
      if ($property_value < $min_property_value) $errors[] = 'Minimum property value is '.self::format_currency($min_property_value).'.';
    }

    if ($errors) {
      wp_send_json_error(['errors' => $errors], 400);
    }

    // Base rule (publicly documented in a Household Capital overview PDF):
    // 20% at age 60, +1% per year of age. Exposed to filters for exact replication.
    $base_lvr = 0.20 + max(0, ($age - 60)) * 0.01;
    $base_lvr = min($base_lvr, $max_lvr);

    $lvr = (float) apply_filters('m60_rm_lvr', $base_lvr, [
      'postcode' => $postcode,
      'age' => $age,
      'property_value' => $property_value,
      'min_age' => $min_age,
      'max_lvr' => $max_lvr,
    ]);

    $lvr = max(0.0, min($lvr, 0.99));

    $amount = (int) floor($property_value * $lvr);
    $amount = min($amount, $max_loan);

    $messages = [];
    if ($amount < $min_loan) {
      $messages[] = 'Based on these inputs, the estimated amount is below the minimum loan size of '.self::format_currency($min_loan).'.';
    } else {
      $messages[] = 'Estimated using an age-based maximum LVR of '.round($lvr * 100, 1).'%.';
    }

    $disclaimer = (string) apply_filters('m60_rm_disclaimer', 'This is an estimate only and does not constitute financial advice or a credit assessment.');

    wp_send_json_success([
      'amount' => $amount,
      'amountFormatted' => self::format_currency($amount),
      'lvr' => $lvr,
      'meta' => implode(' ', $messages),
      'disclaimer' => $disclaimer,
    ]);
  }

  public static function ajax_submit_lead(): void {
    self::require_nonce();

    $enable_leads = (bool) ($_POST['enableLeads'] ?? true);
    if (!$enable_leads) {
      wp_send_json_error(['errors' => ['Lead capture is disabled.']], 403);
    }

    $payload = [
      'postcode' => self::sanitize_postcode($_POST['postcode'] ?? ''),
      'property_value' => self::sanitize_money($_POST['property_value'] ?? ''),
      'age' => self::sanitize_int($_POST['age'] ?? ''),
      'amount' => self::sanitize_int($_POST['amount'] ?? ''),
      'full_name' => sanitize_text_field($_POST['full_name'] ?? ''),
      'email' => sanitize_email($_POST['email'] ?? ''),
      'phone' => sanitize_text_field($_POST['phone'] ?? ''),
      'consent' => (string)($_POST['consent'] ?? '') === '1',
      'notify_email' => sanitize_email($_POST['notifyEmail'] ?? get_option('admin_email')),
      'page_url' => esc_url_raw($_POST['pageUrl'] ?? ''),
      'user_ip' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
      'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
    ];

    $errors = [];
    if (empty($payload['full_name'])) $errors[] = 'Please enter your name.';
    if (empty($payload['email']) || !is_email($payload['email'])) $errors[] = 'Please enter a valid email.';
    if (empty($payload['phone'])) $errors[] = 'Please enter a phone number.';
    if (!$payload['consent']) $errors[] = 'Please confirm you agree to be contacted.';
    if ($payload['postcode'] === null) $errors[] = 'Postcode is missing or invalid.';
    if ($payload['property_value'] === null) $errors[] = 'Property value is missing or invalid.';
    if ($payload['age'] === null) $errors[] = 'Age is missing or invalid.';

    if ($errors) {
      wp_send_json_error(['errors' => $errors], 400);
    }

    $title = $payload['full_name'].' — '.$payload['email'];

    $lead_id = wp_insert_post([
      'post_type' => self::CPT_LEAD,
      'post_status' => 'publish',
      'post_title' => $title,
    ], true);

    if (is_wp_error($lead_id)) {
      wp_send_json_error(['errors' => ['Could not save lead. Please try again.']], 500);
    }

    foreach ($payload as $k => $v) {
      update_post_meta($lead_id, $k, $v);
    }

    $subject = 'New Reverse Mortgage Calculator Lead';
    $body = "A new lead was submitted:\n\n"
      . "Name: {$payload['full_name']}\n"
      . "Email: {$payload['email']}\n"
      . "Phone: {$payload['phone']}\n\n"
      . "Inputs:\n"
      . "Postcode: {$payload['postcode']}\n"
      . "Property value: ".self::format_currency((int)$payload['property_value'])."\n"
      . "Age (youngest): {$payload['age']}\n"
      . "Estimated amount: ".self::format_currency((int)$payload['amount'])."\n\n"
      . "Page: {$payload['page_url']}\n"
      . "IP: {$payload['user_ip']}\n";

    wp_mail($payload['notify_email'], $subject, $body);

    wp_send_json_success([
      'message' => 'Thanks — we’ll be in touch shortly.',
      'leadId' => $lead_id,
    ]);
  }

  private static function require_nonce(): void {
    $nonce = $_POST['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'm60_rm_nonce')) {
      wp_send_json_error(['errors' => ['Security check failed. Please refresh and try again.']], 403);
    }
  }

  private static function sanitize_postcode(string $v): ?string {
    $v = preg_replace('/\s+/', '', $v);
    if (!preg_match('/^\d{4}$/', $v)) return null;
    return $v;
  }

  private static function sanitize_money(string $v): ?int {
    $v = preg_replace('/[^\d.]/', '', $v);
    if ($v === '' || !is_numeric($v)) return null;
    $n = (int) round((float)$v);
    if ($n < 0) return null;
    return $n;
  }

  private static function sanitize_int(string $v): ?int {
    $v = preg_replace('/[^\d-]/', '', $v);
    if ($v === '' || !is_numeric($v)) return null;
    return (int)$v;
  }

  private static function format_currency(int $amount): string {
    return '$' . number_format($amount, 0, '.', ',');
  }

  private static function css(): string {
    return <<<CSS
.m60-rm-calculator { width: 100%; }
.m60-rm-card { border-radius: 16px; padding: 18px; border: 1px solid rgba(0,0,0,.12); }
.m60-rm-h { margin: 0 0 14px; font-size: 20px; }
.m60-rm-h4 { margin: 18px 0 10px; font-size: 16px; }
.m60-rm-label { display:block; margin: 12px 0 6px; font-weight: 600; }
.m60-rm-req { color: currentColor; opacity: .7; }
.m60-rm-input { width: 100%; padding: 12px 12px; border-radius: 10px; border: 1px solid rgba(0,0,0,.18); }
.m60-rm-actions { display:flex; gap: 10px; margin-top: 14px; flex-wrap: wrap; }
.m60-rm-btn { padding: 12px 16px; border-radius: 999px; border: 1px solid rgba(0,0,0,.18); background: #111; color: #fff; cursor: pointer; }
.m60-rm-btn--ghost { background: transparent; color: inherit; }
.m60-rm-error { margin-top: 10px; color: #b00020; }
.m60-rm-success { margin-top: 10px; }
.m60-rm-step { display:none; }
.m60-rm-step.is-active { display:block; }
.m60-rm-result { padding: 14px; border-radius: 12px; border: 1px solid rgba(0,0,0,.12); margin-top: 8px; }
.m60-rm-result__label { opacity: .8; font-size: 13px; }
.m60-rm-result__value { font-size: 30px; font-weight: 800; margin-top: 6px; }
.m60-rm-result__meta { margin-top: 6px; opacity: .85; }
.m60-rm-disclaimer { margin-top: 10px; font-size: 12px; opacity: .8; }
.m60-rm-check { display:flex; gap: 10px; align-items: flex-start; margin-top: 12px; }
.m60-rm-check input { margin-top: 3px; }
CSS;
  }

  private static function js(): string {
    return <<<JS
(function () {
  function parseConfig(el) {
    try { return JSON.parse(el.getAttribute('data-config') || '{}'); }
    catch { return {}; }
  }

  function formatMoneyInput(v) {
    const digits = (v || '').toString().replace(/[^\\d]/g, '');
    if (!digits) return '';
    const n = parseInt(digits, 10);
    if (!Number.isFinite(n)) return '';
    return n.toLocaleString('en-AU');
  }

  function collect(el) {
    const get = (name) => (el.querySelector('[name="' + name + '"]') || {}).value || '';
    return {
      postcode: get('postcode').trim(),
      property_value: get('property_value').trim(),
      age: get('age').trim(),
      full_name: get('full_name').trim(),
      email: get('email').trim(),
      phone: get('phone').trim(),
      consent: !!(el.querySelector('[name="consent"]') || {}).checked
    };
  }

  function setStep(el, step) {
    el.querySelectorAll('.m60-rm-step').forEach(s => s.classList.remove('is-active'));
    const next = el.querySelector('.m60-rm-step[data-step="' + step + '"]');
    if (next) next.classList.add('is-active');
    el.querySelectorAll('.m60-rm-error').forEach(e => e.textContent = '');
    const ok = el.querySelector('.m60-rm-success');
    if (ok) ok.textContent = '';
  }

  function showError(stepEl, msgs) {
    const box = stepEl.querySelector('.m60-rm-error');
    if (!box) return;
    box.textContent = Array.isArray(msgs) ? msgs.join(' ') : String(msgs || '');
  }

  function postForm(url, data) {
    const body = new URLSearchParams();
    Object.keys(data).forEach(k => body.append(k, data[k]));
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body
    }).then(r => r.json().then(j => ({ ok: r.ok, json: j })));
  }

  function initOne(root) {
    const cfg = parseConfig(root);
    const step1 = root.querySelector('.m60-rm-step[data-step="1"]');
    const step2 = root.querySelector('.m60-rm-step[data-step="2"]');
    const step3 = root.querySelector('.m60-rm-step[data-step="3"]');

    const pv = root.querySelector('[name="property_value"]');
    if (pv) {
      pv.addEventListener('input', function () {
        const caretEnd = pv.selectionEnd;
        pv.value = formatMoneyInput(pv.value);
        try { pv.setSelectionRange(caretEnd, caretEnd); } catch {}
      });
    }

    root.addEventListener('click', function (e) {
      const btn = e.target.closest('[data-action]');
      if (!btn) return;
      e.preventDefault();

      const action = btn.getAttribute('data-action');
      const data = collect(root);

      if (action === 'next') {
        const errs = [];
        if (!/^\\d{4}$/.test(data.postcode)) errs.push('Please enter a valid 4-digit postcode.');
        const pvDigits = data.property_value.replace(/[^\\d]/g, '');
        if (!pvDigits) errs.push('Please enter a valid property value.');
        if (errs.length) return showError(step1, errs);
        return setStep(root, 2);
      }

      if (action === 'back') return setStep(root, 1);

      if (action === 'calculate') {
        const errs = [];
        const ageN = parseInt(data.age.replace(/[^\\d]/g, ''), 10);
        if (!Number.isFinite(ageN)) errs.push('Please enter a valid age.');
        if (Number.isFinite(ageN) && ageN < cfg.minAge) errs.push('Minimum age is ' + cfg.minAge + '.');
        if (errs.length) return showError(step2, errs);

        return postForm(cfg.ajaxUrl, {
          action: 'm60_rm_calculate',
          nonce: cfg.nonce,
          postcode: data.postcode,
          property_value: data.property_value,
          age: String(ageN),
          minAge: String(cfg.minAge),
          minPropertyValue: String(cfg.minPropertyValue),
          minLoan: String(cfg.minLoan),
          maxLoan: String(cfg.maxLoan),
          maxLvr: String(cfg.maxLvr)
        }).then(({ ok, json }) => {
          if (!ok || !json.success) {
            return showError(step2, (json && json.data && json.data.errors) || ['Calculation failed.']);
          }

          root.querySelector('[data-bind="amount"]').textContent = json.data.amountFormatted;
          root.querySelector('[data-bind="meta"]').textContent = json.data.meta || '';
          root.querySelector('[data-bind="disclaimer"]').textContent = json.data.disclaimer || '';

          const leadSection = root.querySelector('[data-section="lead"]');
          if (leadSection) leadSection.style.display = cfg.enableLeads ? '' : 'none';

          root.dataset.lastAmount = String(json.data.amount || 0);
          return setStep(root, 3);
        }).catch(() => showError(step2, ['Network error. Please try again.']));
      }

      if (action === 'backToInputs') return setStep(root, 1);

      if (action === 'restart') {
        root.querySelectorAll('input').forEach(i => {
          if (i.type === 'checkbox') i.checked = false;
          else i.value = '';
        });
        root.dataset.lastAmount = '';
        return setStep(root, 1);
      }

      if (action === 'submitLead') {
        const errs = [];
        if (!data.full_name) errs.push('Please enter your name.');
        if (!data.email || !/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/.test(data.email)) errs.push('Please enter a valid email.');
        if (!data.phone) errs.push('Please enter a phone number.');
        if (!data.consent) errs.push('Please confirm you agree to be contacted.');
        if (errs.length) return showError(step3, errs);

        const ageN = parseInt(data.age.replace(/[^\\d]/g, ''), 10);
        return postForm(cfg.ajaxUrl, {
          action: 'm60_rm_submit_lead',
          nonce: cfg.nonce,
          enableLeads: cfg.enableLeads ? '1' : '0',
          notifyEmail: cfg.notifyEmail || '',
          postcode: data.postcode,
          property_value: data.property_value,
          age: String(ageN || ''),
          amount: root.dataset.lastAmount || '0',
          full_name: data.full_name,
          email: data.email,
          phone: data.phone,
          consent: data.consent ? '1' : '0',
          pageUrl: window.location.href
        }).then(({ ok, json }) => {
          if (!ok || !json.success) {
            return showError(step3, (json && json.data && json.data.errors) || ['Could not submit.']);
          }
          const okBox = root.querySelector('.m60-rm-success');
          if (okBox) okBox.textContent = json.data.message || 'Submitted.';
          const errBox = root.querySelector('.m60-rm-error');
          if (errBox) errBox.textContent = '';
        }).catch(() => showError(step3, ['Network error. Please try again.']));
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.m60-rm-calculator').forEach(initOne);
  });
})();
JS;
  }
}

M60_RM_Calculator_Plugin::boot();
