import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/warehouse/generate-table',
            selector: '#warehouse-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'WAREHOUSE' },
                { data: 'CONTACT_PERSON' },
                { data: 'PHONE' },
                { data: 'TELEPHONE' },
                { data: 'EMAIL' },
            ],
            columnDefs: [
                { width: '5%', bSortable: false, targets: 0, responsivePriority: 1 },
                { width: 'auto', targets: 1, responsivePriority: 2 },
                { width: 'auto', targets: 2, responsivePriority: 3 },
                { width: 'auto', targets: 3, responsivePriority: 4 },
                { width: 'auto', targets: 4, responsivePriority: 5 },
                { width: 'auto', targets: 5, responsivePriority: 6 },
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
            url : '/warehouse/delete-multiple',
            swalTitle : 'Confirm Multiple Warehouses Deletion',
            swalText : 'Are you sure you want to delete these warehouses?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the warehouses you want to delete',
            table : '#warehouse-table'
        },
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});