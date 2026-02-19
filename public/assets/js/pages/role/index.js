import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/role/generate-table',
            selector: '#role-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'ROLE' }
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
                export: 'role',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/role/delete-multiple',
            swalTitle : 'Confirm Multiple Role Deletion',
            swalText : 'Are you sure you want to delete these roles?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the roles you want to delete',
            table : '#role-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});