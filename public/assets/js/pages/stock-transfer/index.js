import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';
import { generateDropdownOptions, initializeDateRangePicker } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/stock-transfer/generate-table',
            selector: '#stock-transfer-table',
            serverSide: false,
            ajaxData: () => ({
                filter_by_status: $('#filter_by_status').val(),
            }),
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'REFERENCE_NUMBER' },
                { data: 'TANSFER' },
                { data: 'STOCK_ADJUSTMENT_REASON' },
                { data: 'STATUS' },
            ],
            columnDefs: [
                { width: '5%', bSortable: false, targets: 0, responsivePriority: 1 },
                { width: 'auto', targets: 1, responsivePriority: 2 },
                { width: 'auto', targets: 2, responsivePriority: 3 },
                { width: 'auto', targets: 3, responsivePriority: 4 },
            ],
            onRowClick: (rowData) => {
                if (rowData?.LINK) window.open(rowData.LINK, '_blank');
            },
            addons: {
                controls: true,
                export: 'stock_transfer',
            }
        },
        action: [
            {
                trigger : '#delete-data',
                url : '/stock-transfer/delete-multiple',
                swalTitle : 'Confirm Multiple Stock Transfer Deletion',
                swalText : 'Are you sure you want to delete these stock transfer?',
                confirmButtonText : 'Delete',
                validationMessage : 'Please select the stock transfer you want to delete',
                table : '#stock-transfer-table'
            },
            {
                trigger : '#approve-data',
                url : '/stock-transfer/approve-multiple',
                swalTitle : 'Confirm Multiple Stock Transfer Approval',
                swalText : 'Are you sure you want to approve these stock transfer?',
                confirmButtonText : 'Approve',
                validationMessage : 'Please select the stock transfer you want to approve',
                table : '#stock-transfer-table'
            },
        ]
    }
    
    checkNotification();

    initializeDatatable(config.table);

    config.action.forEach((cfg) => multipleActionButton(cfg));

    document.addEventListener('click', async (event) => {
        if (event.target.closest('#apply-filter')) {
            initializeDatatable(config.table);
        }

        if (event.target.closest('#reset-filter')) {
            $('#filter_by_status').val(['Draft', 'For Approval']).trigger('change');

            initializeDatatable(config.table);
        }
    });
});