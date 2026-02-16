import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const TABLE_URL = '/generate-app-table';
    const TABLE = '#app-table';
    const EXPORT = 'app';
    const DELETE_TRIGGER = '#delete-app';
    const DELETE_URL = '/delete-multiple-app';
    
    checkNotification();

    initializeDatatable({
        url: TABLE_URL,
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

    multipleActionButton({
        'trigger' : DELETE_TRIGGER,
        'url' : DELETE_URL,
        'swalTitle' : 'Confirm Multiple Apps Deletion',
        'swalText' : 'Are you sure you want to delete these apps?',
        'confirmButtonText' : 'Delete',
        'validationMessage' : 'Please select the apps you want to delete',
        'table' : TABLE
    });
});