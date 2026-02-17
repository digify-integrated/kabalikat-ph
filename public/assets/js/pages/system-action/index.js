import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const TABLE_URL = '/generate-system-action-table';
    const TABLE = '#system-action-table';
    const EXPORT = 'system_action';
    const DELETE_TRIGGER = '#delete-data';
    const DELETE_URL = '/delete-multiple-system-action';
    
    checkNotification();

    initializeDatatable({
        url: TABLE_URL,
        selector: TABLE,
        serverSide: false,
        columns: [
            { data: 'CHECK_BOX' },
            { data: 'SYSTEM_ACTION' }
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
        'swalTitle' : 'Confirm Multiple System Actions Deletion',
        'swalText' : 'Are you sure you want to delete these system actions?',
        'confirmButtonText' : 'Delete',
        'validationMessage' : 'Please select the system actions you want to delete',
        'table' : TABLE
    });
});