import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/kitchen-route/generate-table',
            selector: '#kitchen-route-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'KITCHEN_ROUTE' }
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
                export: 'kitchen_route',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/kitchen-route/delete-multiple',
            swalTitle : 'Confirm Multiple Kitchen Route Deletion',
            swalText : 'Are you sure you want to delete these kitchen routes?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the kitchen routes you want to delete',
            table : '#kitchen-route-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});