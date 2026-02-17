import { showNotification, setNotification } from '../util/notifications.js';
import { handleSystemError } from '../util/system-errors.js';

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

  // Do not clear _token and import_table_name
  const hiddens = form.querySelectorAll(
    'input[type="hidden"]:not([name="_token"]):not([name="import_table_name"])'
  );
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
    databaseTable: el?.dataset?.table ?? null,
    navigationMenuId: el?.dataset?.navigationMenuId ?? null,
    detailId: el?.dataset?.detailId ?? null,
  };
};

export const displayDetails = async ({
  url,
  otherData = {},

  // Busy handling
  form,                 
  formSelector,         
  disableWhileFetching = true,

  // ✅ NEW: hide these while fetching (selectors or elements)
  busyHideTargets = [],

  // detailId config
  detailIdKey = 'detailId',
  detailIdValue,

  // Callbacks
  onSuccess = () => {},
  onNotExist,
  onFailureMessage,
} = {}) => {
  const resolveForm = () => form ?? (formSelector ? document.querySelector(formSelector) : null);

  const resolveNodes = (targets) => {
    const nodes = [];
    const list = Array.isArray(targets) ? targets : [targets];

    for (const t of list) {
      if (!t) continue;
      if (t.nodeType === 1) nodes.push(t);
      else if (typeof t === 'string') document.querySelectorAll(t).forEach((el) => nodes.push(el));
    }
    return nodes;
  };

  const setFormBusy = (targetForm, isBusy) => {
    if (!targetForm) return;

    const controls = targetForm.querySelectorAll('input, select, textarea, button');
    controls.forEach((el) => {
      if (isBusy) {
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

  const setHiddenBusy = (nodes, isBusy) => {
    nodes.forEach((el) => {
      if (isBusy) {
        // store original "hidden" state so we restore correctly
        el.dataset.prevHidden = String(el.classList.contains('d-none'));
        el.classList.add('d-none');
      } else {
        const wasHidden = el.dataset.prevHidden === 'true';
        if (!wasHidden) el.classList.remove('d-none');
        delete el.dataset.prevHidden;
      }
    });
  };

  const appendObject = (params, obj) => {
    if (!obj || typeof obj !== 'object') return;
    Object.entries(obj).forEach(([key, value]) => {
      if (value === undefined || value === null || value === '') return;
      params.append(key, typeof value === 'string' ? value : String(value));
    });
  };

  const targetForm = resolveForm();
  const hideNodes = resolveNodes(busyHideTargets);

  try {
    if (disableWhileFetching) setFormBusy(targetForm, true);
    setHiddenBusy(hideNodes, true);

    const csrf = getCsrfToken();
    const ctx = getPageContext();

    const params = new URLSearchParams();
    const resolvedDetailId = detailIdValue ?? (ctx?.detailId ?? '');
    params.append(detailIdKey, resolvedDetailId);
    params.append('appId', ctx.appId ?? '');
    params.append('navigationMenuId', ctx.navigationMenuId ?? '');
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

    if (!response.ok) throw new Error(`Request failed with status: ${response.status}`);

    const data = await response.json();

    if (data?.success) {
      await onSuccess(data);
      return data;
    }

    if (data?.notExist) {
      if (typeof onNotExist === 'function') onNotExist(data);
      else {
        setNotification(data.message);
        window.location.replace(data.redirect_link);
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
    setHiddenBusy(hideNodes, false);
    if (disableWhileFetching) setFormBusy(targetForm, false);
  }
};