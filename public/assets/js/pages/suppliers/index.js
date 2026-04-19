import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/supplier/generate-table',
            selector: '#supplier-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'SUPPLIER' }
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
            url : '/supplier/delete-multiple',
            swalTitle : 'Confirm Multiple Suppliers Deletion',
            swalText : 'Are you sure you want to delete these suppliers?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the suppliers you want to delete',
            table : '#supplier-table'
        },
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});