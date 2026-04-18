import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/stock-adjustment-reason/generate-table',
            selector: '#stock-adjustment-reason-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'STOCK_ADJUSTMENT_REASON' }
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
                export: 'stock_adjustment_reason',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/stock-adjustment-reason/delete-multiple',
            swalTitle : 'Confirm Multiple Stock Adjustment Reason Deletion',
            swalText : 'Are you sure you want to delete these stock adjustment reasons?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the stock adjustment reasons you want to delete',
            table : '#stock-adjustment-reason-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});