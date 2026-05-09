import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/payment-method/generate-table',
            selector: '#payment-method-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'PAYMENT_METHOD' }
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
                export: 'payment_method',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/payment-method/delete-multiple',
            swalTitle : 'Confirm Multiple Payment Method Deletion',
            swalText : 'Are you sure you want to delete these payment methods?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the payment methods you want to delete',
            table : '#payment-method-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});