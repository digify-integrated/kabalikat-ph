import { showNotification, setNotification } from '../util/notifications.js';
import { handleSystemError } from '../util/system-errors.js';
import { redirectToCleanPath } from '../form/button.js';

export const resetForm = (formId) => {
  const form = document.getElementById(formId);
  if (!form) return;

  form.reset();

  const selects = form.querySelectorAll('.form-select');
  for (const s of selects) {
    s.value = '';
    s.dispatchEvent(new Event('change', { bubbles: true }));
  }

  const invalids = form.querySelectorAll('.is-invalid');
  for (const el of invalids) el.classList.remove('is-invalid');

  const hiddens = form.querySelectorAll('input[type="hidden"]:not([name="_token"])');
  for (const h of hiddens) h.value = '';
};

export const getCsrfToken = (
  metaName = 'csrf-token',
  doc = document
) => doc.querySelector(`meta[name="${metaName}"]`)?.content ?? '';

export const getPageContext = () => {
  const el = document.getElementById('kt_app_body');
  return {
    appId: el?.dataset?.appId ?? null,
    navigationMenuId: el?.dataset?.navigationMenuId ?? null,
    detailId: el?.dataset?.detailId ?? null,
  };
};

export const displayDetails = async ({
  url,
  otherData = {},

  // Busy handling
  form,                 // HTMLElement
  formSelector,         // string selector (optional)
  disableWhileFetching = true,

  // detailId config
  detailIdKey = 'detailId',
  detailIdValue,        // optional override; otherwise uses page context

  // Callbacks
  onSuccess = () => {},
  onNotExist,           // optional override
  onFailureMessage,     // optional override
} = {}) => {
  // --- local helpers kept INSIDE for a single cohesive function ---
  const resolveForm = () => form ?? (formSelector ? document.querySelector(formSelector) : null);

  const setFormBusy = (targetForm, isBusy) => {
    if (!targetForm) return;

    const controls = targetForm.querySelectorAll('input, select, textarea, button');

    controls.forEach((el) => {
      if (isBusy) {
        // store original disabled state so we restore correctly
        el.dataset.prevDisabled = String(el.disabled);
        el.disabled = true;
      } else {
        const wasDisabled = el.dataset.prevDisabled === 'true';
        el.disabled = wasDisabled;
        delete el.dataset.prevDisabled;
      }
    });

    if (isBusy) targetForm.setAttribute('aria-busy', 'true');
    else targetForm.removeAttribute('aria-busy');
  };

  const appendObject = (params, obj) => {
    if (!obj || typeof obj !== 'object') return;
    Object.entries(obj).forEach(([key, value]) => {
      if (value === undefined || value === null || value === '') return;
      params.append(key, typeof value === 'string' ? value : String(value));
    });
  };

  const targetForm = resolveForm();

  try {
    if (disableWhileFetching) setFormBusy(targetForm, true);

    const csrf = getCsrfToken();
    const ctx = getPageContext();

    const params = new URLSearchParams();

    const resolvedDetailId = detailIdValue ?? (ctx?.detailId ?? '');
    params.append(detailIdKey, resolvedDetailId);

    // correctly append extra payload keys (fixes your previous "detailId" overwrite)
    appendObject(params, otherData);

    const response = await fetch(url, {
      method: 'POST',
      body: params,
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        Accept: 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
    });

    if (!response.ok) {
      throw new Error(`Request failed with status: ${response.status}`);
    }

    const data = await response.json();

    if (data?.success) {
      await onSuccess(data);
      return data;
    }

    if (data?.notExist) {
      if (typeof onNotExist === 'function') {
        onNotExist(data);
      } else {
        setNotification(data.message);
        redirectToCleanPath();
      }
      return data;
    }

    if (typeof onFailureMessage === 'function') onFailureMessage(data);
    else showNotification(data?.message ?? 'Request failed.');

    return data;
  } catch (error) {
    handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
    throw error;
  } finally {
    if (disableWhileFetching) setFormBusy(targetForm, false);
  }
};