import { showNotification, setNotification  } from '../util/notifications.js';
import { reloadDatatable } from '../util/datatable.js';
import { handleSystemError } from '../util/system-errors.js';
import { getCsrfToken, getPageContext } from '../form/form.js';

const DEFAULT_SPINNER_HTML =
  '<span class="spinner-border spinner-border-sm align-middle ms-0" aria-hidden="true"></span>' +
  '<span class="visually-hidden">Loading...</span>';

const DATA_ORIGINAL = "originalContent";
const DATA_LOADING = "loading";

/** Normalize input into a de-duplicated array of HTMLButtonElements */
function resolveButtons(targets, root = document) {
  if (!targets) return [];

  // Fast paths
  if (targets instanceof HTMLButtonElement) return [targets];

  let list;

  // string: treat as id first; if not found, treat as selector
  if (typeof targets === "string") {
    const byId = root.getElementById?.(targets) || document.getElementById(targets);
    if (byId) list = [byId];
    else list = Array.from(root.querySelectorAll(targets));
  } else if (targets instanceof Element) {
    list = [targets];
  } else {
    // NodeList, HTMLCollection, Array, iterable
    list = Array.from(targets);
  }

  const out = [];
  const seen = new Set();

  for (const t of list) {
    const el = typeof t === "string"
      ? (root.getElementById?.(t) || document.getElementById(t) || root.querySelector(t))
      : t;

    if (!el) {
      console.warn("buttonState: target not found", t);
      continue;
    }
    if (!(el instanceof HTMLButtonElement)) {
      console.warn("buttonState: target is not a <button>", el);
      continue;
    }
    if (seen.has(el)) continue;
    seen.add(el);
    out.push(el);
  }

  return out;
}

/**
 * Apply a loading/disabled state to buttons.
 */
export function disableButton(targets, options = {}) {
  const {
    spinnerHtml = DEFAULT_SPINNER_HTML,
    keepWidth = true,
    setAriaBusy = true,
  } = options;

  const buttons = resolveButtons(targets);

  for (const btn of buttons) {
    // If already in our loading state, avoid DOM work.
    if (btn.dataset[DATA_LOADING] === "true") {
      // Still ensure disabled/aria are correct (cheap writes; no layout reads).
      if (!btn.disabled) btn.disabled = true;
      if (setAriaBusy && btn.getAttribute("aria-busy") !== "true") {
        btn.setAttribute("aria-busy", "true");
      }
      continue;
    }

    // Store original markup once for exact restoration.
    if (btn.dataset[DATA_ORIGINAL] == null) {
      btn.dataset[DATA_ORIGINAL] = btn.innerHTML;

      if (keepWidth && !btn.style.minWidth) {
        // Single layout read; avoid getBoundingClientRect unless you need subpixel precision.
        const w = btn.offsetWidth;
        if (w) btn.style.minWidth = `${w}px`;
      }
    }

    btn.dataset[DATA_LOADING] = "true";

    if (!btn.disabled) btn.disabled = true;
    if (setAriaBusy) btn.setAttribute("aria-busy", "true");

    // Only swap content if it differs from spinner
    if (btn.innerHTML !== spinnerHtml) btn.innerHTML = spinnerHtml;
  }
}

/**
 * Restore buttons to enabled state and original content.
 */
export function enableButton(targets, options = {}) {
  const { clearMinWidth = true, setAriaBusy = true } = options;

  const buttons = resolveButtons(targets);

  for (const btn of buttons) {
    if (btn.disabled) btn.disabled = false;
    if (setAriaBusy) btn.removeAttribute("aria-busy");

    const original = btn.dataset[DATA_ORIGINAL];
    if (original != null) {
      if (btn.innerHTML !== original) btn.innerHTML = original;
      delete btn.dataset[DATA_ORIGINAL];
    }

    delete btn.dataset[DATA_LOADING];

    if (clearMinWidth && btn.style.minWidth) btn.style.minWidth = "";
  }
}

/**
 * Password visibility add-on (delegated, idempotent)
 */
const PASSWORD_ADDON_BOUND = Symbol("password-addon-bound");

export function passwordAddOn(selector = ".password-addon", options = {}) {
  const {
    eyeClass = "ki-eye",
    eyeOffClass = "ki-eye-slash",
    label = "Toggle password visibility",
    root = document,
  } = options;

  const scope = root instanceof Document ? root : root.ownerDocument || document;

  // Exit quickly if no matches
  if (!root.querySelector(selector)) return;

  const getToggle = (eventTarget) => eventTarget?.closest?.(selector) || null;

  const resolveInput = (toggleEl) => {
    const targetSel = toggleEl.getAttribute("data-target");
    const candidate =
      (targetSel && root.querySelector(targetSel)) || toggleEl.previousElementSibling;

    return candidate instanceof HTMLInputElement ? candidate : null;
  };

  const resolveIcon = (toggleEl) => toggleEl.querySelector("i,svg") || toggleEl;

  const setA11y = (toggleEl, inputEl, showing) => {
    if (!toggleEl.hasAttribute("role")) toggleEl.setAttribute("role", "button");
    if (!toggleEl.hasAttribute("tabindex")) toggleEl.setAttribute("tabindex", "0");
    if (!toggleEl.hasAttribute("aria-label")) toggleEl.setAttribute("aria-label", label);

    toggleEl.setAttribute("aria-pressed", String(showing));
    if (inputEl.id) toggleEl.setAttribute("aria-controls", inputEl.id);
  };

  const updateUI = (toggleEl, inputEl) => {
    const showing = inputEl.type !== "password";
    setA11y(toggleEl, inputEl, showing);

    const icon = resolveIcon(toggleEl);
    if (icon?.classList) {
      icon.classList.toggle(eyeClass, !showing);
      icon.classList.toggle(eyeOffClass, showing);
    }
  };

  const toggleVisibility = (toggleEl, inputEl) => {
    try {
      inputEl.type = inputEl.type === "password" ? "text" : "password";
    } catch {
      return;
    }
    updateUI(toggleEl, inputEl);
    inputEl.focus?.({ preventScroll: true });
  };

  const init = (toggleEl) => {
    if (!toggleEl || toggleEl[PASSWORD_ADDON_BOUND]) return;

    const inputEl = resolveInput(toggleEl);
    if (!inputEl) return;

    toggleEl[PASSWORD_ADDON_BOUND] = true;
    updateUI(toggleEl, inputEl);
  };

  // Initialize existing toggles
  root.querySelectorAll(selector).forEach(init);

  // Bind delegation once per root (no attributes needed)
  const delegatedKey = Symbol.for("password-addon-delegated");
  if (root[delegatedKey]) return;
  root[delegatedKey] = true;

  const onActivate = (e) => {
    const toggleEl = getToggle(e.target);
    if (!toggleEl) return;

    init(toggleEl);

    const inputEl = resolveInput(toggleEl);
    if (!inputEl) return;

    if (e.type === "click") {
      e.preventDefault();
      toggleVisibility(toggleEl, inputEl);
      return;
    }

    if (e.type === "keydown") {
      const k = e.key;
      if (k === "Enter" || k === " ") {
        e.preventDefault();
        toggleVisibility(toggleEl, inputEl);
      }
    }
  };

  root.addEventListener("click", onActivate);
  root.addEventListener("keydown", onActivate);
}

/**
 * Copy to clipboard
 * Expects:
 * - showNotification({ message, type })
 * - getTextFromTarget(target)
 * - legacyCopy(text)
 */

const DEFAULT_MESSAGES = Object.freeze({
  empty: "No text to copy",
  success: "Copied to clipboard",
  failure: "Copy failed",
});

function normalizeValue(text, target) {
  if (typeof text === "function") {
    const v = text();
    return typeof v === "string" ? v.trim() : "";
  }
  if (typeof text === "string") return text.trim();

  const v = getTextFromTarget?.(target);
  return typeof v === "string" ? v.trim() : "";
}

function canUseAsyncClipboard() {
  return window.isSecureContext && !!navigator.clipboard?.writeText;
}

/**
 * @returns {Promise<{ ok: boolean, value: string, method: "clipboard"|"legacy"|"none", error?: Error }>}
 */
export async function copyToClipboard({
  text,
  target,
  notify = true,
  messages = {},
  onSuccess,
  onError,
} = {}) {
  const msg = { ...DEFAULT_MESSAGES, ...messages };
  const value = normalizeValue(text, target);

  const emit = (payload) => {
    if (!notify) return;
    if (typeof notify === "function") return notify(payload);

    if (typeof showNotification === "function") {
        const { message, type, timeOut } = payload || {};
        return showNotification(message, type, timeOut);
    }
    };

  if (!value) {
    const error = new Error("No text to copy");
    emit({ message: msg.empty, type: "error" });
    onError?.(error, { ok: false, value: "", method: "none" });
    return { ok: false, value: "", method: "none", error };
  }

  const asyncOk = canUseAsyncClipboard();
  const method = asyncOk ? "clipboard" : "legacy";

  try {
    if (asyncOk) {
      await navigator.clipboard.writeText(value);
    } else {
      const ok = !!legacyCopy?.(value);
      if (!ok) throw new Error("Copy failed");
    }

    emit({ message: msg.success, type: "success" });
    onSuccess?.({ ok: true, value, method });
    return { ok: true, value, method };
  } catch (error) {
    const err = error instanceof Error ? error : new Error(String(error));
    emit({ message: msg.failure, type: "error" });
    onError?.(err, { ok: false, value, method });
    return { ok: false, value, method, error: err };
  }
}

export const multipleActionButton = ({
  trigger,
  url,
  swalTitle,
  swalText,
  swalIcon = 'warning',
  confirmButtonText,
  confirmButtonClass = 'danger',
  validationMessage,
  table,
  opts = {}
}) => {
  const { checkboxSelector = '.datatable-checkbox-children:checked' } = opts;

  const el = typeof trigger === 'string' ? document.querySelector(trigger) : trigger;
  if (!el) {
    console.warn('[multipleDeleteActionButton] Trigger element not found:', trigger);
    return () => {};
  }

  let busy = false;

  const onClick = async (e) => {
    e?.preventDefault?.();
    if (busy) return;

    const selectedIds = Array.from(document.querySelectorAll(checkboxSelector)).map(
      ({ value }) => value
    );

    if (selectedIds.length === 0) {
      showNotification(validationMessage);
      return;
    }

    const { isConfirmed } = await Swal.fire({
      title: swalTitle,
      text: swalText,
      icon: swalIcon,
      showCancelButton: true,
      confirmButtonText: confirmButtonText,
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: `btn btn-${confirmButtonClass}`,
        cancelButton: 'btn btn-secondary',
      },
      buttonsStyling: false,
    });

    if (!isConfirmed) return;

    busy = true;
    const csrf = getCsrfToken();

    if ('disabled' in el) el.disabled = true;
    el.setAttribute('aria-busy', 'true');

    try {
      const body = new URLSearchParams();
      selectedIds.forEach((id) => body.append('selected_id[]', id));

      const response = await fetch(url, {
        method: 'POST',
        body,
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          Accept: 'application/json',
          ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
      });

      let payload;
      try {
        payload = await response.json();
      } catch {
        const text = await response.text().catch(() => '');
        payload = { success: false, message: text };
      }

      if (!response.ok) {
        throw new Error(payload?.message || `Deletion failed (${response.status}).`);
      }

      if (payload?.success) {
        showNotification(payload?.message, 'success');
        reloadDatatable(table)
        return;
      }

      showNotification(payload?.message);
    } catch (error) {
      handleSystemError(
        error,
        'fetch_failed',
        `Fetch request failed: ${error?.message ?? error}`
      );
    } finally {
      busy = false;
      if ('disabled' in el) el.disabled = false;
      el.removeAttribute('aria-busy');
    }
  };

  el.addEventListener('click', onClick);

  return () => el.removeEventListener('click', onClick);
};

export const redirectToCleanPath = ({
  patterns = [/\/new\/?$/i, /\/details\/[^/]+\/?$/i],
  preserveQuery = true,
  preserveHash = true,
  replace = true,
  url = window.location.href,
} = {}) => {
  const u = new URL(url);

  let path = u.pathname;
  for (const rx of patterns) path = path.replace(rx, '');

  const target =
    `${u.origin}${path}` +
    (preserveQuery ? u.search : '') +
    (preserveHash ? u.hash : '');

  if (target !== u.href) {
    replace ? window.location.replace(target) : window.location.assign(target);
  }

  return target;
};

export const detailsDeleteButton = ({
  trigger,
  url,
  swalTitle,
  swalText,
  swalIcon = 'warning',
  confirmButtonText,
  confirmButtonClass = 'danger',
}) => {
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest(trigger);
    if (!btn) return;

    e.preventDefault();

    const result = await Swal.fire({
      title: swalTitle,
      text: swalText,
      icon: swalIcon,
      showCancelButton: true,
      confirmButtonText: confirmButtonText,
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: `btn btn-${confirmButtonClass}`,
        cancelButton: 'btn btn-secondary',
      },
      buttonsStyling: false,
    });

    if (result.isConfirmed) {
      try {
        const csrf = getCsrfToken();
        const ctx = getPageContext();

        const formData = new URLSearchParams();
        formData.append('detailId', ctx.detailId ?? '');
        formData.append('appId', ctx.appId ?? '');
        formData.append('navigationMenuId', ctx.navigationMenuId ?? '')

        const response = await fetch(url, {
          method: 'POST',
          body: formData,
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            Accept: 'application/json',
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
          },
        });

        if (!response.ok) throw new Error(`Request failed with status: ${response.status}`);

        const data = await response.json();

        if (data.success) {
          setNotification(data.message, 'success');
          window.location.replace(data.redirect_link);
        }
        else {
          showNotification(data.message);
        }
      } catch (error) {
        handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
      }
    }
  });
};

export const detailsTableActionButton = ({
  trigger,
  url,
  table,
  swalTitle,
  swalText,
  swalIcon = 'warning',
  confirmButtonText,
  confirmButtonClass = 'danger',
}) => {
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest(trigger);
    if (!btn) return;
    const referenceId = btn.dataset.referenceId;

    e.preventDefault();

    const result = await Swal.fire({
      title: swalTitle,
      text: swalText,
      icon: swalIcon,
      showCancelButton: true,
      confirmButtonText: confirmButtonText,
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: `btn btn-${confirmButtonClass}`,
        cancelButton: 'btn btn-secondary',
      },
      buttonsStyling: false,
    });

    if (result.isConfirmed) {
      try {
        const csrf = getCsrfToken();
        const ctx = getPageContext();

        const formData = new URLSearchParams();
        formData.append('referenceId', referenceId);
        formData.append('detailId', ctx.detailId ?? '');
        formData.append('appId', ctx.appId ?? '');
        formData.append('navigationMenuId', ctx.navigationMenuId ?? '')

        const response = await fetch(url, {
          method: 'POST',
          body: formData,
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            Accept: 'application/json',
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
          },
        });

        if (!response.ok) throw new Error(`Request failed with status: ${response.status}`);

        const data = await response.json();

        if (data.success) {
          reloadDatatable(table);
          showNotification(data.message, 'success');
        }
        else {
          showNotification(data.message);
        }
      } catch (error) {
        handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
      }
    }
  });
};

export const permissionToggle = ({
  trigger,
  url,
}) => {
  document.addEventListener('change', async (e) => {
    const btn = e.target.closest(trigger);
    if (!btn) return;
    const referenceId = btn.dataset.referenceId;
    const accessType = btn.dataset.accessType;
    const access = btn.checked ? 1 : 0;

    e.preventDefault();

    try {
      const csrf = getCsrfToken();
      const ctx = getPageContext();

      const formData = new URLSearchParams();
      formData.append('referenceId', referenceId);
      formData.append('accessType', accessType);
      formData.append('access', access);
      formData.append('detailId', ctx.detailId ?? '');
      formData.append('appId', ctx.appId ?? '');
      formData.append('navigationMenuId', ctx.navigationMenuId ?? '')

      const response = await fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          Accept: 'application/json',
          ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
      });

      if (!response.ok) throw new Error(`Request failed with status: ${response.status}`);

      const data = await response.json();

      if (!data.success) {
        showNotification(data.message);
      }
    } catch (error) {
        handleSystemError(error, 'fetch_failed', `Failed to update role permission: ${error.message}`);
    }
  });
};

export const imageRealtimeUploadButton = ({ trigger, url }) => {
  document.addEventListener('change', async (e) => {
    const input = e.target;

    // Ensure the change came from a file input we care about
    if (!(input instanceof HTMLInputElement)) return;
    if (input.type !== 'file') return;

    // Match selector (either the input itself or an ancestor wrapper)
    const matched = input.matches(trigger) || input.closest(trigger);
    if (!matched) return;

    if (!input.files || input.files.length === 0) return;

    const csrf = getCsrfToken();
    const ctx = getPageContext();

    const formData = new FormData();
    formData.append('detailId', ctx.detailId ?? '');
    formData.append('appId', ctx.appId ?? '');
    formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
    formData.append('image', input.files[0]);

    try {
      const response = await fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
          Accept: 'application/json',
          ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
          // IMPORTANT: do NOT set Content-Type here
        },
        credentials: 'same-origin', // good practice for Laravel sessions/CSRF
      });

      const data = await response.json().catch(() => null);

      if (!response.ok) {
        const msg = data?.message || `Request failed with status: ${response.status}`;
        throw new Error(msg);
      }

      if (data?.success) {
        showNotification(data.message, 'success');
      } else {
        showNotification(data?.message || 'Upload failed.');
      }
    } catch (error) {
      handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
    } finally {
      // Optional: allow re-uploading the same file (change event won't fire otherwise)
      input.value = '';
    }
  });
};

export const discardCreate = () => {
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('#discard-create');
    if (!btn) return;

    e.preventDefault();

    Swal.fire({
      title: 'Are you sure?',
      text: 'You will lose all unsaved changes!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, discard it!',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-danger mt-2',
        cancelButton: 'btn btn-secondary ms-2 mt-2'
      },
      buttonsStyling: false,
    }).then(({ isConfirmed }) => {
      if (!isConfirmed) return;

      redirectToCleanPath();
    });
  });
};
