import { handleSystemError } from './system-errors.js';
import { getCsrfToken, getPageContext } from '../form/form.js';
import { initializeExportFeature } from '../util/export.js';

/**
 * ===== Performance-oriented DataTables helper =====
 * - Server-side capable (recommended for thousands of rows)
 * - Caches DT instances by DOM node (WeakMap)
 * - Avoids repeated DOM scans for checkbox selection UI
 * - Avoids re-binding handlers on every draw
 */

const dtByNode = new WeakMap();

const getTableNode = (selectorOrNode) => {
  if (!selectorOrNode) return null;
  if (selectorOrNode.nodeType === 1) return selectorOrNode;
  return document.querySelector(selectorOrNode);
};

const isDT = (node) => !!node && !!$.fn.DataTable && $.fn.DataTable.isDataTable(node);

const getDT = (selectorOrNode) => {
  const node = getTableNode(selectorOrNode);
  if (!node) return null;

  const cached = dtByNode.get(node);
  if (cached && isDT(node)) return cached;

  if (!isDT(node)) return null;

  const dt = $(node).DataTable();
  dtByNode.set(node, dt);
  return dt;
};

const safeInt = (value, fallback = 10) => {
  const n = Number.parseInt(value, 10);
  return Number.isFinite(n) ? n : fallback;
};

const debounce = (fn, delay = 250) => {
  let t;
  return function (...args) {
    clearTimeout(t);
    t = window.setTimeout(() => fn.apply(this, args), delay);
  };
};

/** Cache UI nodes */
const actionDropdownEl = document.querySelector('.action-dropdown');
const masterCheckboxEl = document.getElementById('datatable-checkbox');

/** Track selection count without DOM scanning */
let checkedCountCache = 0;

export const manageActionDropdown = ({ hideOnly = false } = {}) => {
  if (!actionDropdownEl) return;

  if (hideOnly) {
    checkedCountCache = 0;
    actionDropdownEl.classList.add('d-none');
    if (masterCheckboxEl) masterCheckboxEl.checked = false;

    const children = document.querySelectorAll('.datatable-checkbox-children');
    for (let i = 0; i < children.length; i++) children[i].checked = false;
    return;
  }

  actionDropdownEl.classList.toggle('d-none', checkedCountCache === 0);
};

export const toggleHideActionDropdown = () => {
  checkedCountCache = 0;
  if (actionDropdownEl) actionDropdownEl.classList.add('d-none');
  if (masterCheckboxEl) masterCheckboxEl.checked = false;
};

export const reloadDatatable = (tableSelectorOrNode) => {
  toggleHideActionDropdown();
  const node = getTableNode(tableSelectorOrNode);
  if (!node) return;

  if (isDT(node)) {
    $(node).DataTable().ajax.reload(null, false);
  }
};

export const destroyDatatable = (tableSelectorOrNode) => {
  const node = getTableNode(tableSelectorOrNode);
  if (!node) return;

  const dt = getDT(node);
  if (!dt) return;

  // Remove row-click handler we attach
  const tbody = node.tBodies?.[0];
  if (tbody) {
    const ns = `.dtRowClick_${node.id || 'table'}`;
    $(tbody).off(ns);
  }

  dt.clear();
  dt.destroy(true);
  dtByNode.delete(node);
};

export const clearDatatable = (tableSelectorOrNode) => {
  const dt = getDT(tableSelectorOrNode);
  if (dt) dt.clear().draw(false);
};

/**
 * Initialize DataTable (FAST defaults)
 *
 * IMPORTANT:
 * - For thousands of records, use serverSide: true and return DT format:
 *   { draw, recordsTotal, recordsFiltered, data: [...] }
 */
export const initializeDatatable = ({
  selector,
  url,
  ajaxData = {},
  columns = [],
  columnDefs = [],
  order = [[1, 'asc']],
  lengthMenu = [
    [10, 25, 50, 100, -1],
    [10, 25, 50, 100, 'All'],
  ],
  onRowClick = null,
  addons = {
    controls: false,            // true | { table?: selectorOrNode }
    export: false,              // selectorOrNode (e.g. EXPORT_TABLE) | { table: ... }
    subControls: false,         // { searchSelector, lengthSelector, table?: selectorOrNode }
  },
  serverSide = true,
  pageLength = 25,
  searchDelay = 400,
  scrollX = true,
  responsive = false,
  processing = false,
}) => {
  const table = getTableNode(selector);
  if (!table) return;

  destroyDatatable(table);
  manageActionDropdown({ hideOnly: true });

  if (!url) {
    showNotification(`Missing url for DataTable: ${selector}`);
    return;
  }

  const csrf = getCsrfToken();

  const dt = $(table).DataTable({
    serverSide,
    processing: processing,
    deferRender: true,
    autoWidth: false,
    orderClasses: false,
    searchDelay,
    responsive,
    scrollX,
    scrollCollapse: true,

    paging: true,
    pageLength,
    lengthChange: false,
    order,
    columns,
    columnDefs,
    lengthMenu,

    ajax: {
      url,
      type: 'POST',
      dataType: 'json',
      headers: csrf ? { 'X-CSRF-Token': csrf } : {},
      data: (d) => {
        const ctx = getPageContext();
        const extra = typeof ajaxData === 'function' ? ajaxData(d) : (ajaxData || {});
        return { ...d, ...extra, ...ctx };
      },
      dataSrc: serverSide ? 'data' : '',
      error: (xhr, status, err) => handleSystemError(xhr, status, err),
    },

    language: {
      emptyTable: 'No data found',
      info: '_START_ - _END_ of _TOTAL_ items',
      loadingRecords: 'Loading Data...',
      processing: 'Loading...',
      zeroRecords: 'No matching records found',
    },

    drawCallback: () => {
      toggleHideActionDropdown();
    },

    // ✅ Run optional initializers ONCE after DT is ready
    initComplete: () => {
      // 1) Standard controls
      if (addons?.controls) {
        const tableRef =
          typeof addons.controls === 'object' && addons.controls?.table
            ? addons.controls.table
            : selector;
        initializeDatatableControls(tableRef);
      }

      // 2) Export feature
      if (addons?.export) {
        const exportRef =
          typeof addons.export === 'object' && addons.export?.table
            ? addons.export.table
            : addons.export; // allow passing EXPORT_TABLE directly
        // Assumes initializeExportFeature exists/imported in this module
        initializeExportFeature(exportRef);
      }

      // 3) Sub-datatable controls
      if (addons?.subControls && typeof addons.subControls === 'object') {
        const {
          searchSelector,
          lengthSelector,
          table: subTableRef = selector,
        } = addons.subControls;

        if (searchSelector && lengthSelector) {
          initializeSubDatatableControls(searchSelector, lengthSelector, subTableRef);
        }
      }
    },
  });

  dtByNode.set(table, dt);

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

  return dt;
};


/** Bind checkbox handlers once globally (fast, delegated) */
let checkboxHandlersBound = false;

export const initializeDatatableControls = (tableSelectorOrNode) => {
  const dt = getDT(tableSelectorOrNode);
  if (!dt) {
    showNotification(`DataTable not initialized for: ${tableSelectorOrNode}`);
    return;
  }

  const lengthEl = document.getElementById('datatable-length');
  const searchEl = document.getElementById('datatable-search');

  if (lengthEl) {
    dt.page.len(safeInt(lengthEl.value, 10)).draw(false);
    lengthEl.onchange = () => dt.page.len(safeInt(lengthEl.value, 10)).draw(false);
  }

  if (searchEl) {
    const handleSearch = debounce((value) => dt.search(value).draw(false), 400);
    searchEl.oninput = () => handleSearch(searchEl.value);
  }

  if (!checkboxHandlersBound) {
    checkboxHandlersBound = true;

    $(document)
      .on('click.dtCheckbox', '.datatable-checkbox-children', function () {
        checkedCountCache += this.checked ? 1 : -1;
        if (checkedCountCache < 0) checkedCountCache = 0;
        manageActionDropdown();
      })
      .on('click.dtCheckbox', '#datatable-checkbox', function () {
        const checked = this.checked === true;
        const children = document.querySelectorAll('.datatable-checkbox-children');

        checkedCountCache = checked ? children.length : 0;
        for (let i = 0; i < children.length; i++) children[i].checked = checked;

        manageActionDropdown();
      });
  } else {
    manageActionDropdown();
  }
};

const initializeSubDatatableControls = (searchSelector, lengthSelector, tableSelectorOrNode) => {
  const dt = getDT(tableSelectorOrNode);
  if (!dt) {
    showNotification(`DataTable not initialized for: ${tableSelectorOrNode}`);
    return;
  }

  const handleSearch = debounce((value) => dt.search(value).draw(false), 400);

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
