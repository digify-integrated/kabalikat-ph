import { initializeDatatable, initializeDatatableControls, reloadDatatable } from '../../util/datatable.js';
import { initializeExportFeature } from '../../util/export.js';

document.addEventListener('DOMContentLoaded', () => {
    initializeDatatable({
        selector: '#app-table',
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

    initializeDatatableControls('#app-table');
    initializeExportFeature('app');
});