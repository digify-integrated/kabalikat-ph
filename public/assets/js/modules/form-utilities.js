import { showNotification } from '../util/notifications.js';

const DEFAULTS = Object.freeze({
  spinnerHtml:
    '<span class="spinner-border spinner-border-sm align-middle ms-0" aria-hidden="true"></span>' +
    '<span class="visually-hidden">Loading...</span>',
  labelSelector: '[data-btn-label]',
  mode: 'html',
  busyAttr: 'aria-busy',
});

const stateByButton = new WeakMap();
const resolvedIdCache = new Map();

const normalizeTargets = (targets) => {
  if (!targets) return [];
  const list = Array.isArray(targets) ? targets : [targets];
  const out = [];

  for (const t of list) {
    if (!t) continue;

    if (typeof t === 'string') {
      let el = resolvedIdCache.get(t);
      if (!el || !document.contains(el)) {
        el = document.getElementById(t);
        if (el) resolvedIdCache.set(t, el);
      }
      if (el) out.push(el);
      continue;
    }

    if (t instanceof HTMLElement) {
      out.push(t);
    }
  }

  return out;
};

const getOrInitState = (btn, labelSelector) => {
  let st = stateByButton.get(btn);
  if (!st) {
    st = { labelNode: null, originalText: null, originalHtml: null };
    stateByButton.set(btn, st);
  }

  if (st.labelNode === null) {
    st.labelNode = btn.querySelector?.(labelSelector) || undefined;
  }

  return st;
};

const readContent = (el, mode) => (mode === 'html' ? el.innerHTML : el.textContent);
const writeContent = (el, mode, value) => {
  if (mode === 'html') {
    if (el.innerHTML !== value) el.innerHTML = value;
  } else {
    if (el.textContent !== value) el.textContent = value;
  }
};

const setButtonLoading = (
  targets,
  isLoading,
  options = {}
) => {
  const {
    spinnerHtml,
    labelSelector,
    mode,
    busyAttr,
  } = { ...DEFAULTS, ...options };

  const key = mode === 'html' ? 'originalHtml' : 'originalText';
  const buttons = normalizeTargets(targets);

  for (const btn of buttons) {
    if (!('disabled' in btn)) {
      console.warn('setButtonLoading: target does not support disabled:', btn);
      continue;
    }

    const st = getOrInitState(btn, labelSelector);
    const labelNode = st.labelNode || null;

    const contentHost = labelNode || btn;

    if (isLoading) {
      if (st[key] == null) st[key] = readContent(contentHost, mode);

      if (!btn.disabled) btn.disabled = true;
      if (busyAttr && btn.getAttribute(busyAttr) !== 'true') btn.setAttribute(busyAttr, 'true');

      if (mode === 'html') {
        if (contentHost.innerHTML !== spinnerHtml) contentHost.innerHTML = spinnerHtml;
      } else {
        const loadingText = 'Loading…';
        if (contentHost.textContent !== loadingText) contentHost.textContent = loadingText;
      }
    } else {
      if (btn.disabled) btn.disabled = false;
      if (busyAttr) btn.removeAttribute(busyAttr);

      const original = st[key];
      if (original != null) {
        writeContent(contentHost, mode, original);
        st[key] = null;
      }
    }
  }

  return buttons.length;
};

export const disableButton = (targets, options) =>
  setButtonLoading(targets, true, options);

export const enableButton = (targets, options) =>
  setButtonLoading(targets, false, options);

export const passwordAddOn = (selector = '.password-addon') => {
  const addons = document.querySelectorAll(selector);
  if (!addons.length) return;

  addons.forEach((addon) => {
    const targetSel = addon.getAttribute('data-target');
    const input =
      (targetSel && document.querySelector(targetSel)) ||
      addon.previousElementSibling;

    if (!input || input.tagName !== 'INPUT') return;

    const icon =
      addon.querySelector('i') ||
      addon.querySelector('svg') ||
      addon;

    const CLASS_EYE = 'ki-eye';
    const CLASS_EYE_OFF = 'ki-eye-slash';

    const setA11y = () => {
      addon.setAttribute('role', 'button');
      addon.setAttribute('tabindex', '0');
      addon.setAttribute('aria-label', 'Toggle password visibility');
      addon.setAttribute('aria-controls', input.id || '');

      addon.setAttribute('aria-pressed', input.type !== 'password' ? 'true' : 'false');
    };

    const updateIcon = () => {
      if (!icon?.classList) return;

      const showing = input.type !== 'password';
      icon.classList.toggle(CLASS_EYE, !showing);
      icon.classList.toggle(CLASS_EYE_OFF, showing);
    };

    const toggle = () => {
      try {
        input.type = input.type === 'password' ? 'text' : 'password';
      } catch {
        return;
      }

      updateIcon();
      addon.setAttribute('aria-pressed', input.type !== 'password' ? 'true' : 'false');

      input.focus({ preventScroll: true });
    };

    setA11y();
    updateIcon();

    if (addon.dataset.passwordAddonBound === 'true') return;
    addon.dataset.passwordAddonBound = 'true';

    addon.addEventListener('click', (e) => {
      e.preventDefault();
      toggle();
    });

    addon.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggle();
      }
    });
  });
};

const getTextFromTarget = (target) => {
  if (!target) return '';
  if (typeof target === 'string') return document.getElementById(target)?.textContent?.trim() || '';
  if (target instanceof HTMLElement) return target.textContent?.trim() || '';
  return '';
};

const legacyCopy = (value) => {
  const ta = document.createElement('textarea');
  ta.value = value;
  ta.readOnly = true;
  ta.style.position = 'fixed';
  ta.style.left = '-9999px';
  ta.style.top = '0';
  document.body.appendChild(ta);
  ta.select();
  const ok = document.execCommand('copy');
  ta.remove();
  return ok;
};

export const copyToClipboard = async ({
  text,
  target,
  notify = true,
  onSuccess,
  onError,
} = {}) => {
  const value = (typeof text === 'string' ? text.trim() : '') || getTextFromTarget(target);

  if (!value) {
    notify && showNotification({
      message: 'No text to copy',
      type: 'error'
    });
    onError?.(new Error('No text to copy'));
    return false;
  }

  try {
    if (navigator.clipboard?.writeText && window.isSecureContext) {
      await navigator.clipboard.writeText(value);
      notify && showNotification({
        message: 'Copied to clipboard',
        type: 'success'
      });
      onSuccess?.();
      return true;
    }
  } catch {
    
  }

  try {
    const ok = legacyCopy(value);
    if (ok) {
      notify && showNotification({
        message: 'Copied to clipboard',
        type: 'success'
      });
      onSuccess?.();
      return true;
    }
    throw new Error('execCommand copy failed');
  } catch (e) {
    notify && showNotification({
      message: 'No text to copy',
      type: 'error'
    });
    onError?.(e);
    return false;
  }
};