'use strict';

import { showNotification } from './notifications.js';

/**
 * validate-lite.js (Pure JS ES6+) conveniences (mirrors common jQuery Validate behavior)
 *
 * What’s new vs your current version:
 * 1) Auto-rules inferred from HTML attributes / input types:
 *    - required attribute (and requiredIf rule)
 *    - type="email|url|tel|number"
 *    - minlength / maxlength
 *    - min / max / step (for number/date/time when applicable)
 *    - pattern
 * 2) You can still override/extend with `rules` and `messages`.
 * 3) Conditional rules like requiredIf / minIf / maxIf (similar to depends).
 * 4) One toast PER error message (staggered).
 * 5) Still Bootstrap-friendly + Select2 focus/open.
 *
 * Usage:
 * initValidation('.needs-validation', {
 *   rules: {
 *     relation_name: { required: true },
 *     middle_name: { requiredIf: { field: 'has_middle_name', value: '1' } },
 *     age: { min: 18 }, // overrides/extends HTML attributes
 *   },
 *   messages: {
 *     relation_name: { required: 'Please enter the relation name' },
 *     middle_name: { requiredIf: 'Middle name is required when enabled.' },
 *     age: { min: 'You must be at least 18.' },
 *   },
 *   submitHandler: (form) => { ... },
 * });
 */
export function initValidation(selector = '.needs-validation', options = {}) {
  const defaults = {
    // Toast options
    toastType: 'error',
    toastDuration: 3500,
    toastPosition: 'right',
    toastGravity: 'top',

    // Behavior
    notifyOnFieldInvalid: false,
    focusFirstInvalid: true,
    scrollToFirstInvalid: true,

    // Toast-per-message behavior
    toastEachError: true,
    toastDelayStepMs: 140,
    maxToastsPerSubmit: 10,

    // Validation config (validate-like)
    rules: {},
    messages: {},
    submitHandler: null,

    // Optional override:
    // getMessage: ({ field, fieldKey, ruleName, ruleValue, defaultMessage }) => string
    getMessage: null,

    // If true: run BOTH inferred rules and custom rules (custom can override).
    // If false: if a field has custom rules, inferred rules are skipped.
    runInferredRulesEvenWhenCustomProvided: true,
  };

  const config = { ...defaults, ...options };
  const forms = document.querySelectorAll(selector);
  if (!forms.length) return;

  for (const form of forms) {
    if (form.dataset.validationBound === 'true') continue;
    form.dataset.validationBound = 'true';

    if (config.notifyOnFieldInvalid) {
      form.addEventListener(
        'invalid',
        (e) => {
          const field = e.target;
          if (!isValidatableField(field)) return;

          const errors = validateField(field, form, config);
          if (!errors.length) return;

          toastErrors(errors, config);
        },
        true
      );
    }

    form.addEventListener('input', (e) => {
      const field = e.target;
      if (isValidatableField(field)) field.classList.remove('is-invalid');
    });
    form.addEventListener('change', (e) => {
      const field = e.target;
      if (isValidatableField(field)) field.classList.remove('is-invalid');
    });

    form.addEventListener('submit', (event) => {
      const { valid, errors } = validateForm(form, config);

      if (!valid) {
        event.preventDefault();
        event.stopPropagation();

        for (const err of errors) err.field.classList.add('is-invalid');

        if (config.toastEachError) toastErrors(errors, config);
        else if (errors[0]) toastErrors([errors[0]], config);

        const firstInvalid = errors[0]?.field;
        if (firstInvalid && (config.focusFirstInvalid || config.scrollToFirstInvalid)) {
          focusFieldSmart(firstInvalid, {
            scroll: config.scrollToFirstInvalid,
            focus: config.focusFirstInvalid,
          });
        }

        form.classList.add('was-validated');
        return;
      }

      form.classList.add('was-validated');

      if (typeof config.submitHandler === 'function') {
        event.preventDefault();
        config.submitHandler(form);
      }
    });
  }
}

/* ----------------------------- toasts ----------------------------- */

function toastErrors(errors, config) {
  // Unique messages preserve order
  const unique = [];
  const seen = new Set();

  for (const e of errors) {
    const msg = String(e.message || '').trim();
    if (!msg || seen.has(msg)) continue;
    seen.add(msg);
    unique.push(msg);
    if (unique.length >= config.maxToastsPerSubmit) break;
  }

  unique.forEach((msg, i) => {
    const delay = i * config.toastDelayStepMs;

    if (delay === 0) {
      // If your showNotification is title+description, you can split here:
      // showNotification(e.title, e.description, ...)
      showNotification(msg, config.toastType, config.toastDuration, config.toastPosition, config.toastGravity);
      return;
    }

    window.setTimeout(() => {
      showNotification(msg, config.toastType, config.toastDuration, config.toastPosition, config.toastGravity);
    }, delay);
  });
}

/* ----------------------------- validation ----------------------------- */

function validateForm(form, config) {
  const fields = getFormFields(form);
  const errors = [];

  for (const field of fields) {
    const fieldErrors = validateField(field, form, config);
    if (fieldErrors.length) errors.push(...fieldErrors);
  }

  return { valid: errors.length === 0, errors };
}

function validateField(field, form, config) {
  const fieldKey = getFieldKey(field);
  const errors = [];

  const inferredRules = inferRulesFromField(field);
  const customRules = fieldKey ? (config.rules?.[fieldKey] || null) : null;

  // Rule merge strategy:
  // - inferred rules apply by default
  // - custom rules override inferred rules of the same name
  // - if runInferredRulesEvenWhenCustomProvided=false and customRules exist => inferred rules ignored
  const effectiveRules = buildEffectiveRules(inferredRules, customRules, config);

  // Evaluate rules in a stable order (so messages feel consistent)
  const orderedRuleEntries = orderRules(effectiveRules);

  for (const [ruleName, ruleValue] of orderedRuleEntries) {
    if (!ruleValue) continue;

    const passed = runRule(ruleName, ruleValue, field, form);
    if (!passed) {
      const msg = resolveMessage({ field, fieldKey, ruleName, ruleValue, form, config });
      errors.push({ field, rule: ruleName, message: msg });
      break; // 1 error per field
    }
  }

  // Last resort fallback (native) if no rules were inferred nor provided
  if (errors.length === 0 && orderedRuleEntries.length === 0) {
    if (!field.checkValidity()) {
      const native = (field.validationMessage || 'Invalid value').trim();
      errors.push({ field, rule: 'native', message: prefixWithLabel(field, form, native) });
    }
  }

  return errors;
}

function buildEffectiveRules(inferredRules, customRules, config) {
  const inferred = inferredRules && typeof inferredRules === 'object' ? inferredRules : {};
  const custom = customRules && typeof customRules === 'object' ? customRules : {};

  if (!config.runInferredRulesEvenWhenCustomProvided && Object.keys(custom).length) {
    return { ...custom };
  }

  // Custom overrides inferred if same rule exists
  return { ...inferred, ...custom };
}

function orderRules(rulesObj) {
  const order = [
    'required',
    'requiredIf',
    'typeEmail',
    'typeUrl',
    'typeTel',
    'typeNumber',
    'minlength',
    'maxlength',
    'min',
    'max',
    'step',
    'pattern',
    'equalTo',
  ];

  const entries = Object.entries(rulesObj || {});
  entries.sort((a, b) => {
    const ai = order.indexOf(a[0]);
    const bi = order.indexOf(b[0]);
    return (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
  });
  return entries;
}

/* ----------------------------- inferred rules ----------------------------- */

function inferRulesFromField(field) {
  const rules = {};

  // required attribute
  if (field.required) rules.required = true;

  const tag = field.tagName.toLowerCase();
  const type = (field.getAttribute('type') || '').toLowerCase();

  // type-based
  if (type === 'email') rules.typeEmail = true;
  if (type === 'url') rules.typeUrl = true;
  if (type === 'tel') rules.typeTel = true;
  if (type === 'number') rules.typeNumber = true;

  // minlength/maxlength attributes (work for text-like inputs)
  const minLenAttr = field.getAttribute('minlength');
  if (minLenAttr != null && String(minLenAttr).trim() !== '') rules.minlength = Number(minLenAttr);

  const maxLenAttr = field.getAttribute('maxlength');
  if (maxLenAttr != null && String(maxLenAttr).trim() !== '') rules.maxlength = Number(maxLenAttr);

  // pattern attribute
  const pattern = field.getAttribute('pattern');
  if (pattern) rules.pattern = pattern;

  // min/max/step attributes
  // Applicable: number, range, date, datetime-local, month, time, week
  const supportsMinMax =
    type === 'number' ||
    type === 'range' ||
    type === 'date' ||
    type === 'datetime-local' ||
    type === 'month' ||
    type === 'time' ||
    type === 'week';

  if (supportsMinMax) {
    const minAttr = field.getAttribute('min');
    if (minAttr != null && String(minAttr).trim() !== '') rules.min = minAttr;

    const maxAttr = field.getAttribute('max');
    if (maxAttr != null && String(maxAttr).trim() !== '') rules.max = maxAttr;

    const stepAttr = field.getAttribute('step');
    // Ignore "any" — means no step constraint
    if (stepAttr && stepAttr !== 'any') rules.step = stepAttr;
  }

  // select required convenience:
  // If <select required> is set, required already covers it.

  // textarea rules already handled via minlength/maxlength if present.

  // If developer uses data-rule-* attributes (optional convenience)
  // e.g. data-rule-equal-to="#password"
  const eq = field.getAttribute('data-rule-equal-to');
  if (eq) rules.equalTo = eq;

  return rules;
}

/* ----------------------------- rule engine ----------------------------- */

function runRule(ruleName, ruleValue, field, form) {
  const value = getFieldValue(field);
  const type = (field.getAttribute('type') || '').toLowerCase();

  switch (ruleName) {
    case 'required':
      return requiredCheck(field, form);

    // Conditional required: requiredIf: { field: 'other', value: '1' } OR function(form, field) => boolean
    case 'requiredIf': {
      const must = evaluateCondition(ruleValue, form, field);
      if (!must) return true;
      return !isEmpty(value);
    }

    case 'typeEmail':
      if (isEmpty(value)) return true;
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value));

    case 'typeUrl':
      if (isEmpty(value)) return true;
      return isValidUrl(String(value));

    case 'typeTel':
      if (isEmpty(value)) return true;
      // Lightweight phone check (lets many international formats through)
      return /^[+()\d\s\-\.]{6,}$/.test(String(value));

    case 'typeNumber':
      if (isEmpty(value)) return true;
      return isFiniteNumber(value);

    case 'minlength':
      if (isEmpty(value)) return true;
      return String(value).length >= Number(ruleValue);

    case 'maxlength':
      if (isEmpty(value)) return true;
      return String(value).length <= Number(ruleValue);

    case 'pattern': {
      if (isEmpty(value)) return true;
      const re = ruleValue instanceof RegExp ? ruleValue : new RegExp(`^(?:${String(ruleValue)})$`);
      return re.test(String(value));
    }

    case 'min': {
      if (isEmpty(value)) return true;
      return compareMinMax({ field, type, value, bound: ruleValue, isMin: true });
    }

    case 'max': {
      if (isEmpty(value)) return true;
      return compareMinMax({ field, type, value, bound: ruleValue, isMin: false });
    }

    case 'step': {
      if (isEmpty(value)) return true;
      return validateStep({ field, type, value, step: ruleValue });
    }

    case 'equalTo': {
      const other = resolveOtherField(ruleValue, form);
      if (!other) return true;
      return String(value) === String(getFieldValue(other));
    }

    default:
      return true;
  }
}

function requiredCheck(field, form) {
  const type = (field.getAttribute('type') || '').toLowerCase();

  if (type === 'checkbox') return field.checked;
  if (type === 'radio') {
    if (!field.name) return field.checked;
    return !!form.querySelector(`input[type="radio"][name="${cssEscape(field.name)}"]:checked`);
  }

  const value = getFieldValue(field);
  return !isEmpty(value);
}

function evaluateCondition(condition, form, field) {
  // condition can be:
  // - function(form, field) => boolean
  // - { field: 'otherNameOrId', value: 'x' } (value can be array)
  // - { selector: '#id', value: 'x' }
  // - { field: 'x', notEmpty: true }
  if (typeof condition === 'function') {
    return !!condition(form, field);
  }

  if (!condition || typeof condition !== 'object') return false;

  const other =
    condition.selector
      ? form.querySelector(condition.selector)
      : resolveOtherField(condition.field, form);

  if (!other) return false;

  const otherVal = getFieldValue(other);

  if (condition.notEmpty) return !isEmpty(otherVal);

  if ('value' in condition) {
    const expected = condition.value;
    if (Array.isArray(expected)) return expected.map(String).includes(String(otherVal));
    return String(otherVal) === String(expected);
  }

  // If no explicit comparison, treat "truthy" as enabled
  return !isEmpty(otherVal);
}

function resolveOtherField(ruleValue, form) {
  if (!ruleValue) return null;

  if (typeof ruleValue === 'string' && /[#.\[]/.test(ruleValue)) {
    return form.querySelector(ruleValue);
  }

  const byName = form.querySelector(`[name="${cssEscape(String(ruleValue))}"]`);
  if (byName) return byName;

  const byId = form.querySelector(`#${cssEscape(String(ruleValue))}`);
  return byId || null;
}

/* ----------------------------- min/max/step helpers ----------------------------- */

function compareMinMax({ type, value, bound, isMin }) {
  // For number/range -> numeric compare
  if (type === 'number' || type === 'range') {
    const n = Number(value);
    const b = Number(bound);
    if (!Number.isFinite(n) || !Number.isFinite(b)) return true; // ignore if unparsable
    return isMin ? n >= b : n <= b;
  }

  // For date/time-like types -> lexicographic compare works with ISO-ish values
  // date: YYYY-MM-DD
  // datetime-local: YYYY-MM-DDTHH:mm
  // month: YYYY-MM
  // time: HH:mm (lex works)
  // week: YYYY-W##
  const v = String(value);
  const b = String(bound);
  return isMin ? v >= b : v <= b;
}

function validateStep({ type, value, step }) {
  // step applies cleanly to number/range; for date/time it’s more nuanced.
  // We’ll enforce step only for number/range in this lightweight implementation.
  if (!(type === 'number' || type === 'range')) return true;

  const n = Number(value);
  const s = Number(step);
  if (!Number.isFinite(n) || !Number.isFinite(s) || s <= 0) return true;

  // Consider decimal steps; use a tolerance.
  const ratio = n / s;
  const nearest = Math.round(ratio);
  return Math.abs(ratio - nearest) < 1e-10;
}

function isFiniteNumber(v) {
  const n = Number(v);
  return Number.isFinite(n);
}

function isValidUrl(str) {
  try {
    // Accepts http(s) URLs; adjust if you want to accept any scheme
    const u = new URL(str);
    return u.protocol === 'http:' || u.protocol === 'https:';
  } catch {
    return false;
  }
}

/* ----------------------------- messages ----------------------------- */

function resolveMessage({ field, fieldKey, ruleName, ruleValue, form, config }) {
  const custom = fieldKey ? config.messages?.[fieldKey]?.[ruleName] : null;

  const fallback = defaultRuleMessage(field, ruleName, ruleValue, form);
  const chosen = typeof custom === 'string' && custom.trim() ? custom : fallback;

  if (typeof config.getMessage === 'function') {
    const overridden = config.getMessage({
      field,
      fieldKey,
      ruleName,
      ruleValue,
      defaultMessage: chosen,
    });
    if (overridden != null && String(overridden).trim()) return String(overridden).trim();
  }

  return String(chosen).trim();
}

function defaultRuleMessage(field, ruleName, ruleValue, form) {
  const name = getFieldLabelText(field, form) || getBestFieldName(field);

  switch (ruleName) {
    case 'required':
      return `Please enter ${name}.`;
    case 'requiredIf':
      return `${name} is required.`;
    case 'typeEmail':
      return `Please enter a valid ${name}.`;
    case 'typeUrl':
      return `Please enter a valid URL for ${name}.`;
    case 'typeTel':
      return `Please enter a valid ${name}.`;
    case 'typeNumber':
      return `Please enter a valid number for ${name}.`;
    case 'minlength':
      return `${name} must be at least ${Number(ruleValue)} characters.`;
    case 'maxlength':
      return `${name} must be at most ${Number(ruleValue)} characters.`;
    case 'min':
      return `${name} must be at least ${String(ruleValue)}.`;
    case 'max':
      return `${name} must be at most ${String(ruleValue)}.`;
    case 'step':
      return `${name} must be a valid increment.`;
    case 'pattern':
      return `${name} format is invalid.`;
    case 'equalTo':
      return `${name} does not match.`;
    default:
      return `${name} is invalid.`;
  }
}

/* ----------------------------- field utils ----------------------------- */

function getFormFields(form) {
  return Array.from(form.querySelectorAll('input, select, textarea')).filter(isValidatableField);
}

function isValidatableField(el) {
  if (!el || el.disabled) return false;

  const tag = el.tagName;
  if (tag === 'BUTTON') return false;

  const type = (el.getAttribute('type') || '').toLowerCase();
  if (type === 'hidden' || type === 'submit' || type === 'reset' || type === 'button') return false;

  return typeof el.checkValidity === 'function';
}

function getFieldKey(field) {
  return field.getAttribute('name') || field.id || null;
}

function getFieldValue(field) {
  const type = (field.getAttribute('type') || '').toLowerCase();

  if (type === 'checkbox') return field.checked ? (field.value || 'on') : '';
  if (type === 'radio') {
    const form = field.form;
    if (!form || !field.name) return field.checked ? field.value : '';
    const checked = form.querySelector(`input[type="radio"][name="${cssEscape(field.name)}"]:checked`);
    return checked ? checked.value : '';
  }
  return field.value;
}

function isEmpty(v) {
  return v == null || String(v).trim() === '';
}

function getBestFieldName(field) {
  return field.getAttribute('aria-label') || field.name || field.id || 'this field';
}

function prefixWithLabel(field, form, msg) {
  const label = getFieldLabelText(field, form) || getBestFieldName(field);
  return `${label}: ${msg}`;
}

function getFieldLabelText(field, form) {
  if (field.id) {
    const label = form.querySelector(`label[for="${cssEscape(field.id)}"]`);
    const txt = label?.textContent?.trim();
    if (txt) return txt;
  }

  const wrapped = field.closest('label');
  const wrappedTxt = wrapped?.textContent?.trim();
  if (wrappedTxt) return wrappedTxt;

  const labelledBy = field.getAttribute('aria-labelledby');
  if (labelledBy) {
    const parts = labelledBy
      .split(/\s+/)
      .map((id) => document.getElementById(id)?.textContent?.trim())
      .filter(Boolean);
    if (parts.length) return parts.join(' ');
  }

  return '';
}

function cssEscape(value) {
  if (window.CSS?.escape) return window.CSS.escape(value);
  return String(value).replace(/["\\#.;?+*~':!^$[\]()=>|/@]/g, '\\$&');
}

/* ----------------------------- Select2 support (best-effort) ----------------------------- */

function isSelect2Field(field) {
  return field?.tagName === 'SELECT' && field.classList.contains('select2-hidden-accessible');
}

function openSelect2(field) {
  if (!window.jQuery) return false;

  const $el = window.jQuery(field);
  if (typeof $el.select2 !== 'function' || !$el.data('select2')) return false;

  try {
    $el.select2('open');
    return true;
  } catch {
    return false;
  }
}

function getSelect2Container(field) {
  const next = field.nextElementSibling;
  return next?.classList?.contains('select2') ? next : null;
}

function focusFieldSmart(field, { scroll = true, focus = true } = {}) {
  if (!field) return;

  if (isSelect2Field(field)) {
    const container = getSelect2Container(field);

    if (container && scroll) {
      container.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    if (openSelect2(field)) return;

    if (container && focus) {
      const selection = container.querySelector('.select2-selection');
      selection?.focus();
    }
    return;
  }

  if (scroll) field.scrollIntoView({ behavior: 'smooth', block: 'center' });
  if (focus) window.setTimeout(() => field.focus({ preventScroll: true }), 0);
}
