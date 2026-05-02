import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';
import { generateDropdownOptions, initializeDateRangePicker } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/stock-adjustment/generate-table',
            selector: '#stock-adjustment-table',
            serverSide: false,
            ajaxData: () => ({
                filter_by_status: $('#filter_by_status').val(),
            }),
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'REFERENCE_NUMBER' },
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
                export: 'stock_adjustment',
            }
        },
        action: [
            {
                trigger : '#delete-data',
                url : '/stock-adjustment/delete-multiple',
                swalTitle : 'Confirm Multiple Stock Adjustment Deletion',
                swalText : 'Are you sure you want to delete these stock adjustment?',
                confirmButtonText : 'Delete',
                validationMessage : 'Please select the stock adjustment you want to delete',
                table : '#stock-adjustment-table'
            },
            {
                trigger : '#approve-data',
                url : '/stock-adjustment/approve-multiple',
                swalTitle : 'Confirm Multiple Stock Adjustment Approval',
                swalText : 'Are you sure you want to approve these stock adjustment?',
                confirmButtonText : 'Approve',
                validationMessage : 'Please select the stock adjustment you want to approve',
                table : '#stock-adjustment-table'
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