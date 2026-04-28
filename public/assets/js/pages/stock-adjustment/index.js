import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/stock-adjustment/generate-table',
            selector: '#stock-adjustment-table',
            serverSide: false,
            ajaxData: () => ({
                filter_by_stock_level: $('#filter_by_stock_level').val(),
                filter_by_adjustment_type: $('#filter_by_adjustment_type').val(),
                filter_by_status: $('#filter_by_status').val(),
            }),
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'STOCK_LEVEL' },
                { data: 'ADJUSTMENT_TYPE' },
                { data: 'CURRENT_QUANTITY' },
                { data: 'QUANTITY' },
                { data: 'STATUS' },
                { data: 'ADJUSTMENT_REASON' },
                { data: 'REMARKS' },
            ],
            columnDefs: [
                { width: '5%', bSortable: false, targets: 0, responsivePriority: 1 },
                { width: 'auto', targets: 1, responsivePriority: 2 },
                { width: 'auto', targets: 2, responsivePriority: 3 },
                { width: 'auto', targets: 3, responsivePriority: 4 },
                { width: 'auto', targets: 4, responsivePriority: 5 },
                { width: 'auto', targets: 5, responsivePriority: 6 },
                { width: 'auto', targets: 6, responsivePriority: 7 },
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
        ],
        dropdown: [
            { url: '/stock-level/generate-options', dropdownSelector: '#filter_by_stock_level', data: { multiple : true } },
        ],
    }
    
    checkNotification();

    initializeDatatable(config.table);

    config.dropdown.map((cfg) => generateDropdownOptions(cfg));
    config.action.forEach((cfg) => multipleActionButton(cfg));

    document.addEventListener('click', async (event) => {
        if (event.target.closest('#apply-filter')) {
            initializeDatatable(config.table);
        }

        if (event.target.closest('#reset-filter')) {
            $('#filter_by_product').val(null).trigger('change');
            $('#filter_by_warehouse').val(null).trigger('change');
            $('#filter_by_expiration_date').val(null);
            $('#filter_by_received_date').val(null);
            $('#filter_by_status').val(['Draft', 'For Approval']).trigger('change');

            initializeDatatable(config.table);
        }
    });
});