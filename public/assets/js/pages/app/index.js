import { initializeDatatable, initializeDatatableControls, reloadDatatable } from '../../util/datatable.js';

document.addEventListener('DOMContentLoaded', () => {
    initializeDatatable({
        selector: '#app-module-table',
        transaction: 'generate app module table',
        columns: [
            { data: 'CHECK_BOX' },
            { data: 'APP_NAME' }
        ],
        columnDefs: [
            { width: '5%', bSortable: false, targets: 0, responsivePriority: 1 },
            { width: 'auto', targets: 1, responsivePriority: 2 }
        ],
        onRowClick: (rowData) => {
            if (rowData?.LINK) window.open(rowData.LINK, '_blank');
        }
    });
});