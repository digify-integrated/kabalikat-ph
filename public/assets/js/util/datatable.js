import { handleSystemError } from './system-errors.js';
import { getCsrfToken, getPageContext } from '../form/form.js';

/**
 * Cache DT instances by table DOM node (robust, avoids selector-staleness).
 * WeakMap allows GC when table is removed.
 */
const dtByNode = new WeakMap();

const getTableNode = (selectorOrNode) => {
  if (!selectorOrNode) return null;
  if (selectorOrNode.nodeType === 1) return selectorOrNode; // element
  return document.querySelector(selectorOrNode);
};

const getDT = (selectorOrNode) => {
  const node = getTableNode(selectorOrNode);
  if (!node) return null;

  const cached = dtByNode.get(node);
  if (cached) {
    // Ensure it’s still a DT table
    if ($.fn.dataTable.isDataTable(node)) return cached;
    dtByNode.delete(node);
  }

  if (!$.fn.dataTable.isDataTable(node)) return null;

  const dt = $(node).DataTable();
  dtByNode.set(node, dt);
  return dt;
};

const safeInt = (value, fallback = 10) => {
  const n = Number.parseInt(value, 10);
  return Number.isFinite(n) ? n : fallback;
};

const debounce = (fn, delay = 150) => {
  let t;
  return function debounced(...args) {
    clearTimeout(t);
    t = window.setTimeout(() => fn.apply(this, args), delay);
  };
};

/** Cache global-ish UI nodes once */
const actionDropdownEl = document.querySelector('.action-dropdown');
const masterCheckboxEl = document.getElementById('datatable-checkbox');

/**
 * Avoid querying all children twice; use :checked count when only counting.
 * Use a loop when you must write to all checkboxes.
 */
export const manageActionDropdown = ({ hideOnly = false } = {}) => {
  if (!actionDropdownEl) return;

  const children = document.querySelectorAll('.datatable-checkbox-children');

  if (hideOnly) {
    actionDropdownEl.classList.add('d-none');
    if (masterCheckboxEl) masterCheckboxEl.checked = false;
    for (let i = 0; i < children.length; i++) children[i].checked = false;
    return;
  }

  // Count checked cheaply
  const checkedCount = document.querySelectorAll('.datatable-checkbox-children:checked').length;
  actionDropdownEl.classList.toggle('d-none', checkedCount === 0);
};

export const toggleHideActionDropdown = () => {
  if (actionDropdownEl) actionDropdownEl.classList.add('d-none');
  if (masterCheckboxEl) masterCheckboxEl.checked = false;
};

export const reloadDatatable = (tableSelector) => {
  toggleHideActionDropdown();
  const dt = getDT(tableSelector);
  if (dt) dt.ajax.reload(null, false);
};

export const destroyDatatable = (tableSelector) => {
  const node = getTableNode(tableSelector);
  const dt = getDT(node);
  if (!dt) return;

  // Remove any delegated handlers we attached for this table (row click)
  const tbody = node.tBodies?.[0];
  if (tbody) {
    const ns = `.dtRowClick_${node.id || 'table'}`;
    $(tbody).off(ns);
  }

  dt.clear();
  dt.destroy(true);
  dtByNode.delete(node);
};

export const clearDatatable = (tableSelector) => {
  const dt = getDT(tableSelector);
  if (dt) dt.clear().draw(false);
};

export const initializeDatatable = ({
  url,
  selector,
  ajaxData = {},
  columns = [],
  columnDefs = [],
  lengthMenu = [
    [10, 25, 50, 100, -1],
    [10, 25, 50, 100, 'All'],
  ],
  order = [[1, 'asc']],
  onRowClick = null,
}) => {
  const table = getTableNode(selector);
  if (!table) return;

  destroyDatatable(table);
  manageActionDropdown({ hideOnly: true });

  if (!url) {
    showNotification(`Missing data-url on: ${selector}`);
    return;
  }

  const csrf = getCsrfToken();

  const dt = $(table).DataTable({
    processing: true,
    deferRender: true,
    ajax: {
      url,
      type: 'POST',
      dataType: 'json',
      headers: csrf ? { 'X-CSRF-Token': csrf } : {},
      data: (d) => {
        const ctx = getPageContext();

        // If ajaxData is an object, merge it. If it's a function, call it.
        const extra =
          typeof ajaxData === 'function'
            ? ajaxData(d)
            : (ajaxData || {});

        return {
          ...d,           // DataTables paging/search/order payload
          ...extra,       // any custom caller-provided fields
          ...ctx,         // appId + navigationMenuId
        };
      },
      dataSrc: '',
      error: (xhr, status, err) => handleSystemError(xhr, status, err),
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
  });

  dtByNode.set(table, dt);

  // One delegated row-click handler per table (major win on redraw)
  if (typeof onRowClick === 'function') {
    const tbody = table.tBodies?.[0];
    if (tbody) {
      const ns = `.dtRowClick_${table.id || selector.replace(/[^a-z0-9]/gi, '')}`;
      $(tbody)
        .off(ns)
        .on(`click${ns}`, 'td:nth-child(n+2)', function () {
          const rowData = dt.row(this.closest('tr')).data();
          if (rowData) onRowClick(rowData);
        });
    }
  }
};

/** Bind checkbox handlers once globally */
let checkboxHandlersBound = false;

export const initializeDatatableControls = (tableSelector) => {
  const dt = getDT(tableSelector);
  if (!dt) {
    showNotification(`DataTable not initialized for selector: ${tableSelector}`);
    return;
  }

  const lengthEl = document.getElementById('datatable-length');
  const searchEl = document.getElementById('datatable-search');

  if (lengthEl) {
    dt.page.len(safeInt(lengthEl.value, 10)).draw(false);
    // Bind directly (faster than delegating through jQuery for fixed controls)
    lengthEl.onchange = () => dt.page.len(safeInt(lengthEl.value, 10)).draw(false);
  }

  if (searchEl) {
    const handleSearch = debounce((value) => dt.search(value).draw(false), 150);
    searchEl.oninput = () => handleSearch(searchEl.value);
  }

  if (!checkboxHandlersBound) {
    checkboxHandlersBound = true;

    // Use delegation because checkboxes are often re-rendered by DT
    $(document)
      .on('click.dtCheckbox', '.datatable-checkbox-children', () => manageActionDropdown())
      .on('click.dtCheckbox', '#datatable-checkbox', function () {
        const checked = this.checked === true;
        $('.datatable-checkbox-children').prop('checked', checked);
        manageActionDropdown();
      });
  } else {
    manageActionDropdown();
  }
};

export const initializeSubDatatableControls = (searchSelector, lengthSelector, tableSelector) => {
  const dt = getDT(tableSelector);
  if (!dt) {    
    showNotification(`DataTable not initialized for selector: ${tableSelector}`);
    return;
  }

  const handleSearch = debounce((value) => dt.search(value).draw(false), 150);

  // Keep delegation; namespace once and replace existing handlers.
  $(document)
    .off('change.dtSubControls', lengthSelector)
    .on('change.dtSubControls', lengthSelector, function () {
      dt.page.len(safeInt(this.value, 10)).draw(false);
    })
    .off('input.dtSubControls', searchSelector)
    .on('input.dtSubControls', searchSelector, function () {
      handleSearch(this.value);
    });
};
