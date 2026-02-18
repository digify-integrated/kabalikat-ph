import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/system-action/generate-table',
            selector: '#system-action-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'SYSTEM_ACTION' }
            ],
            columnDefs: [
                { width: '5%', bSortable: false, targets: 0, responsivePriority: 1 },
                { width: 'auto', targets: 1, responsivePriority: 2 },
            ],
            onRowClick: (rowData) => {
                if (rowData?.LINK) window.open(rowData.LINK, '_blank');
            },
            addons: {
                controls: true,
                export: 'system_action',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/system-action/delete-multiple',
            swalTitle : 'Confirm Multiple System Action Deletion',
            swalText : 'Are you sure you want to delete these system actions?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the system actions you want to delete',
            table : '#system-action-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});