import { disableButton, enableButton } from '../form/button.js';
import { initializeDualListBoxIcon } from '../form/field.js';
import { getCsrfToken } from '../form/form.js';
import { handleSystemError } from '../util/system-errors.js';
import { showNotification } from '../util/notifications.js';

const parseFilename = (contentDisposition) => {
  if (!contentDisposition) return '';

  // Prefer RFC 5987: filename*=UTF-8''...
  const starMatch = contentDisposition.match(/filename\*\s*=\s*([^;]+)/i);
  if (starMatch?.[1]) {
    const v = starMatch[1].trim();
    const parts = v.split("''"); // UTF-8''encodedName
    const encoded = parts.length === 2 ? parts[1] : v;
    const cleaned = encoded.replace(/^["']|["']$/g, '');
    try {
      return decodeURIComponent(cleaned);
    } catch {
      return cleaned;
    }
  }

  // Fallback: filename=... (quoted or unquoted)
  const nameMatch = contentDisposition.match(/filename\s*=\s*([^;]+)/i);
  if (nameMatch?.[1]) {
    return nameMatch[1].trim().replace(/^["']|["']$/g, '');
  }

  return '';
};

const downloadBlob = (blob, filename) => {
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
};

const jsonFetch = async (url, { signal, method = 'POST', bodyObj } = {}) => {
  const csrf = getCsrfToken();
  const res = await fetch(url, {
    method,
    signal,
    headers: {
      'Content-Type': 'application/json',
      ...(csrf && { 'X-CSRF-TOKEN': csrf }),
    },
    body: bodyObj ? JSON.stringify(bodyObj) : undefined,
    credentials: 'same-origin',
  });

  if (!res.ok) throw res; // handleSystemError supports Response
  return res.json();
};

const blobFetch = async (url, { method = 'POST', bodyObj } = {}) => {
  const csrf = getCsrfToken();
  const res = await fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json',
      ...(csrf && { 'X-CSRF-TOKEN': csrf }),
    },
    body: bodyObj ? JSON.stringify(bodyObj) : undefined,
    credentials: 'same-origin',
  });

  if (!res.ok) throw res;

  const cd = res.headers.get('Content-Disposition');
  const headerFilename = parseFilename(cd);

  const blob = await res.blob();
  return { blob, headerFilename };
};

export const initializeExportFeature = (tableName) => {
  let selectedColumnsOrder = [];
  let dualListInitialized = false;
  let listAbort = null;

  const getSelectEl = () => document.getElementById('table_column');

  const initDualListOnce = () => {
    if (dualListInitialized) return;
    const $el = $('#table_column');
    if (!$el.length) return;

    $el.bootstrapDualListbox({
      nonSelectedListLabel: 'Non-selected',
      selectedListLabel: 'Selected',
      preserveSelectionOnMove: 'moved',
      moveOnSelect: false,
      helperSelectNamePostfix: false,
      sortByInputOrder: true,
    });

    // Keep selected order updated
    $el.off('change.export').on('change.export', function () {
      selectedColumnsOrder = $('#table_column option:selected')
        .map((_, opt) => opt.value)
        .get();
    });

    initializeDualListBoxIcon();
    dualListInitialized = true;
  };

  const refreshDualList = () => {
    const $el = $('#table_column');
    if ($el.length) $el.bootstrapDualListbox('refresh', true);
    initializeDualListBoxIcon();
  };

  // Load export column list
  $(document)
    .off('click.exportFeature', '#export-data')
    .on('click.exportFeature', '#export-data', async () => {
      const select = getSelectEl();
      if (!select) return;

      // Cancel in-flight request on repeated clicks
      if (listAbort) listAbort.abort();
      listAbort = new AbortController();

      try {
        const response = await jsonFetch('/export/export-list', {
          signal: listAbort.signal,
          bodyObj: { table_name: tableName },
        });

        // Populate options efficiently
        select.options.length = 0;
        const frag = document.createDocumentFragment();
        for (let i = 0; i < response.length; i++) {
          const opt = response[i];
          frag.appendChild(new Option(opt.text, opt.id));
        }
        select.appendChild(frag);

        initDualListOnce();
        selectedColumnsOrder = []; // reset because options changed
        refreshDualList();
      } catch (err) {
        if (err?.name === 'AbortError') return;
        handleSystemError(err, 'error', 'Failed to load export columns');
      } finally {
        listAbort = null;
      }
    });

  // Submit export (download file)
  $(document)
    .off('click.exportFeature', '#submit-export')
    .on('click.exportFeature', '#submit-export', async () => {
      const exportTo = document.querySelector('input[name="export_to"]:checked')?.value;

      const exportId = Array.from(
        document.querySelectorAll('.datatable-checkbox-children:checked'),
        (el) => el.value
      );

      if (!exportId.length) {
        showNotification('Choose the data you want to export');
        return;
      }

      if (!selectedColumnsOrder.length) {
        showNotification('Choose the columns you want to export');
        return;
      }

      if (!exportTo) {
        showNotification('Choose an export format');   
        return;
      }

      disableButton('submit-export');

      try {
        const { blob, headerFilename } = await blobFetch('/export/export', {
          bodyObj: {
            export_id: exportId,
            export_to: exportTo,
            table_column: selectedColumnsOrder,
            table_name: tableName,
          },
        });

        // Use server filename if provided, otherwise fallback
        const fallback = `${tableName}-export.${exportTo}`;
        const finalName = headerFilename || fallback;

        downloadBlob(blob, finalName);
      } catch (err) {
        handleSystemError(err, 'error', 'Export failed');
      } finally {
        enableButton('submit-export');
      }
    });
};