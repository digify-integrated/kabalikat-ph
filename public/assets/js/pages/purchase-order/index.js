import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';
import { generateDropdownOptions, initializeDateRangePicker } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/purchase-order/generate-table',
            selector: '#purchase-order-table',
            serverSide: false,
            ajaxData: () => ({
                filter_by_status: $('#filter_by_status').val(),
            }),
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'REFERENCE_NUMBER' },
                { data: 'WAREHOUSE' },
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
                export: 'purchase_order',
            }
        },
        action: [
            {
                trigger : '#delete-data',
                url : '/purchase-order/delete-multiple',
                swalTitle : 'Confirm Multiple Purchase Order Deletion',
                swalText : 'Are you sure you want to delete these purchase order?',
                confirmButtonText : 'Delete',
                validationMessage : 'Please select the purchase order you want to delete',
                table : '#purchase-order-table'
            },
            {
                trigger : '#approve-data',
                url : '/purchase-order/approve-multiple',
                swalTitle : 'Confirm Multiple Purchase Order Approval',
                swalText : 'Are you sure you want to approve these purchase order?',
                confirmButtonText : 'Approve',
                validationMessage : 'Please select the purchase order you want to approve',
                table : '#purchase-order-table'
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