import { initializeDatatable } from '../../util/datatable.js';
import { multipleDeleteActionButton } from '../../form/button.js';

document.addEventListener('DOMContentLoaded', () => {
    const TABLE = '#app-table';
    const EXPORT = 'app';

    initializeDatatable({
        url: '/generate-app-table',
        selector: TABLE,
        serverSide: false,
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
        },
        addons: {
            controls: true,
            export: EXPORT,
        }
    });

    multipleDeleteActionButton(
        '#delete-app',
        '/delete-multiple-app',
        'Confirm Multiple Apps Deletion?',
        'Are you sure you want to delete these apps?',
        TABLE
    );
});