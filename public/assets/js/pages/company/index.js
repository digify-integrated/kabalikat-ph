import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/company/generate-table',
            selector: '#company-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'COMPANY' }
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
                export: 'app',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/company/delete-multiple',
            swalTitle : 'Confirm Multiple Companies Deletion',
            swalText : 'Are you sure you want to delete these companies?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the companies you want to delete',
            table : '#company-table'
        },
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});