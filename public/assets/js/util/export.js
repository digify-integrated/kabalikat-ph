import { disableButton, enableButton } from '../modules/form-utilities.js';
import { handleSystemError } from '../modules/system-errors.js';
import { showNotification } from './notifications.js';

const initializeDualListBoxIcon = () => {
  $('.moveall i').removeClass().addClass('ki-duotone ki-right');
  $('.removeall i').removeClass().addClass('ki-duotone ki-left');
  $('.move i').removeClass().addClass('ki-duotone ki-right');
  $('.remove i').removeClass().addClass('ki-duotone ki-left');

  $('.moveall, .removeall, .move, .remove')
    .removeClass('btn-default')
    .addClass('btn-primary');
};

async function fetchOrThrow(url, options = {}) {
  const res = await fetch(url, {
    headers: { 'X-Requested-With': 'XMLHttpRequest', ...(options.headers || {}) },
    ...options
  });
  if (!res.ok) throw res;
  return res;
}

const  getFilenameFromDisposition = (disposition) => {
  if (!disposition) return '';

  // RFC 5987: filename*=UTF-8''encoded-name
  const star = disposition.match(/filename\*\s*=\s*UTF-8''([^;]+)/i);
  if (star?.[1]) {
    return decodeURIComponent(star[1].replace(/["']/g, ''));
  }

  // filename="name.ext" OR filename=name.ext
  const plain = disposition.match(/filename\s*=\s*("?)([^";]+)\1/i);
  return plain?.[2] || '';
}

export const initializeExportFeature = (tableName) => {
    let selectedColumnsOrder = [];

    $(document).off('click', '#export-data').on('click', '#export-data', async () => {
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetchOrThrow('/export-list', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ table_name: tableName })
            });

            const response = await res.json();
            const select = document.getElementById('table_column');
            if (!select) return;

            select.options.length = 0;
            response.forEach(opt => select.appendChild(new Option(opt.text, opt.id)));

        } catch (err) {
            await handleSystemError(err, 'error', 'Failed to load export columns');
            return;
        }

        if (!$('#table_column').length) return;

        $('#table_column').bootstrapDualListbox({
            nonSelectedListLabel: 'Non-selected',
            selectedListLabel: 'Selected',
            preserveSelectionOnMove: 'moved',
            moveOnSelect: false,
            helperSelectNamePostfix: false,
            sortByInputOrder: true
        });

        $('#table_column').off('change').on('change', function () {
            selectedColumnsOrder = $('#table_column option:selected')
                .map((_, opt) => $(opt).val())
                .get();
        });

        $('#table_column').bootstrapDualListbox('refresh', true);
        initializeDualListBoxIcon();
    });

    $(document).off('click', '#submit-export').on('click', '#submit-export', async () => {
        const exportTo = $('input[name="export_to"]:checked').val();
        const tableColumn = selectedColumnsOrder;
        const exportId = [];

        $('.datatable-checkbox-children:checked').each((_, el) => exportId.push(el.value));

        if (exportId.length === 0) {
            showNotification({
                message: 'Choose the data you want to export',
                type: 'error'
            });
            return;
        }

        if (tableColumn.length === 0) {
            showNotification({
                message: 'Choose the columns you want to export',
                type: 'error'
            });
            return;
        }

        disableButton('submit-export');

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const res = await fetchOrThrow('/export', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    export_id: exportId,
                    export_to: exportTo,
                    table_column: tableColumn,
                    table_name: tableName
                })
            });

            const disposition = res.headers.get('Content-Disposition') || '';
            const filename = getFilenameFromDisposition(disposition);
            const match = /filename="(.+?)"/.exec(disposition);
            if (match?.[1]) filename = match[1];

            const blob = await res.blob();
            const url = URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = filename || `export.${exportTo}`;
            document.body.appendChild(a);
            a.click();
            a.remove();

            URL.revokeObjectURL(url);

        } catch (err) {
            await handleSystemError(err, 'error', 'Export failed');
        } finally {
            enableButton('submit-export');
        }
    });
};
