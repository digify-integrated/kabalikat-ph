import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/stock-transfer-reason/generate-table',
            selector: '#stock-transfer-reason-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'STOCK_TRANSFER_REASON' }
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
                export: 'stock_transfer_reason',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/stock-transfer-reason/delete-multiple',
            swalTitle : 'Confirm Multiple Stock Transfer Reason Deletion',
            swalText : 'Are you sure you want to delete these stock transfer reasons?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the stock transfer reasons you want to delete',
            table : '#stock-transfer-reason-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});