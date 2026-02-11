import { handleSystemError } from '../modules/system-errors.js';
import { showNotification } from './notifications.js';

const getDT = (selector) => {
  if (!selector) return null;
  return $.fn.DataTable.isDataTable(selector) ? $(selector).DataTable() : null;
};

const safeInt = (value, fallback = 10) => {
  const n = Number.parseInt(value, 10);
  return Number.isFinite(n) ? n : fallback;
};

const debounce = (fn, delay = 150) => {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), delay);
  };
};

export const manageActionDropdown = (options = { hideOnly: false }) => {
  const actionDropdown = document.querySelector('.action-dropdown');
  if (!actionDropdown) return;

  const masterCheckbox = document.getElementById('datatable-checkbox');
  const childCheckboxes = document.querySelectorAll('.datatable-checkbox-children');

  if (options.hideOnly) {
    actionDropdown.classList.add('d-none');
    if (masterCheckbox) masterCheckbox.checked = false;
    childCheckboxes.forEach((chk) => (chk.checked = false));
    return;
  }

  let checkedCount = 0;
  childCheckboxes.forEach((chk) => {
    if (chk.checked) checkedCount++;
  });

  actionDropdown.classList.toggle('d-none', checkedCount === 0);
};

export const toggleHideActionDropdown = () => {
  const actionDropdown = document.querySelector('.action-dropdown');
  const masterCheckbox = document.getElementById('datatable-checkbox');
  if (actionDropdown) actionDropdown.classList.add('d-none');
  if (masterCheckbox) masterCheckbox.checked = false;
};

export const reloadDatatable = (datatableSelector) => {
  toggleHideActionDropdown();

  const dt = getDT(datatableSelector);
  if (!dt) return;

  dt.ajax.reload(null, false);
};

export const destroyDatatable = (datatableSelector) => {
  const dt = getDT(datatableSelector);
  if (!dt) return;

  dt.clear();
  dt.destroy(true);
};

export const clearDatatable = (datatableSelector) => {
  const dt = getDT(datatableSelector);
  if (!dt) return;

  dt.clear().draw(false);
};

export const initializeDatatable = ({
  selector,
  ajaxData = {},
  columns = [],
  columnDefs = [],
  lengthMenu = [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
  order = [[1, 'asc']],
  onRowClick = null,
}) => {
  const tableElement = document.querySelector(selector);
  if (!tableElement) return;

  const el = document.querySelector(selector);

  destroyDatatable(selector);

  manageActionDropdown({ hideOnly: true });

  const settings = {
    processing: true,
    deferRender: true,
    ajax: {
      url: el.dataset.url,
      type: 'GET',
      dataType: 'json',
      data: {
        ...ajaxData,
      },
      dataSrc: '',
      error: (xhr, status, error) => {
        handleSystemError(xhr, status, error);
      },
    },

    lengthChange: false,
    searchDelay: 200,
    order,
    columns,
    columnDefs,
    lengthMenu,
    autoWidth: false,

    language: {
      emptyTable: 'No data found',
      info: '_START_ - _END_ of _TOTAL_ items',
      loadingRecords: 'Just a moment while we fetch your data...',
      zeroRecords: 'No matching records found',
    },

    createdRow: (row, rowData) => {
      if (typeof onRowClick !== 'function') return;

      $(row)
        .off('click.dtRowClick')
        .on('click.dtRowClick', 'td:nth-child(n+2)', () => {
          onRowClick(rowData);
        });
    },
  };

  $(selector).DataTable(settings);
};

export const initializeDatatableControls = (selector) => {
  const dt = getDT(selector);
  if (!dt) {
    showNotification({
      message: `DataTable not initialized for selector: ${selector}`,
      type: 'error'
    });
    return;
  }

  const $lengthDropdown = $('#datatable-length');
  const $searchInput = $('#datatable-search');

  if ($lengthDropdown.length) {
    dt.page.len(safeInt($lengthDropdown.val(), 10)).draw(false);

    $lengthDropdown.off('change.dtControls').on('change.dtControls', function () {
      dt.page.len(safeInt(this.value, 10)).draw(false);
    });
  }

  if ($searchInput.length) {
    const handleSearch = debounce((value) => {
      dt.search(value).draw(false);
    }, 150);

    $searchInput.off('input.dtControls').on('input.dtControls', function () {
      handleSearch(this.value);
    });
  }

  $(document)
    .off('click.dtCheckbox')
    .on('click.dtCheckbox', '.datatable-checkbox-children', () => manageActionDropdown())
    .on('click.dtCheckbox', '#datatable-checkbox', function () {
      const checked = this.checked === true;
      $('.datatable-checkbox-children').prop('checked', checked);
      manageActionDropdown();
    });
};

export const initializeSubDatatableControls = (searchSelector, lengthSelector, tableSelector) => {
  const dt = getDT(tableSelector);
  if (!dt) {
    showNotification({
      message: `DataTable not initialized for selector: ${tableSelector}`,
      type: 'error'
    });
    return;
  }

  $(document)
    .off('change.dtSubControls', lengthSelector)
    .on('change.dtSubControls', lengthSelector, function () {
      dt.page.len(safeInt(this.value, 10)).draw(false);
    });

  const handleSearch = debounce((value) => {
    dt.search(value).draw(false);
  }, 150);

  $(document)
    .off('input.dtSubControls', searchSelector)
    .on('input.dtSubControls', searchSelector, function () {
      handleSearch(this.value);
    });
};
