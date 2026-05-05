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
                filter_byfilter_by_supplier_product: $('#filter_by_supplier').val(),
                filter_by_warehouse: $('#filter_by_warehouse').val(),
                filter_by_order_date: $('#filter_by_order_date').val(),
                filter_by_expected_delivery_date: $('#filter_by_expected_delivery_date').val(),
                filter_by_status: $('#filter_by_status').val(),
            }),
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'REFERENCE_NUMBER' },
                { data: 'SUPPLIER' },
                { data: 'WAREHOUSE' },
                { data: 'STATUS' },
                { data: 'ORDER_DATE' },
                { data: 'EXPECTED_DELIVERY_DATE' },
            ],
            columnDefs: [
                { width: '5%', bSortable: false, targets: 0, responsivePriority: 1 },
                { width: 'auto', targets: 1, responsivePriority: 2 },
                { width: 'auto', targets: 2, responsivePriority: 3 },
                { width: 'auto', targets: 3, responsivePriority: 4 },
                { width: 'auto', targets: 4, responsivePriority: 5 },
                { width: 'auto', targets: 5, type: 'date', responsivePriority: 6 },
                { width: 'auto', targets: 6, type: 'date', responsivePriority: 7 },
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
        ],
        dropdown: [
            { url: '/supplier/generate-options', dropdownSelector: '#filter_by_supplier', data: { multiple : true } },
            { url: '/warehouse/generate-options', dropdownSelector: '#filter_by_warehouse', data: { multiple : true } },
        ],
        datepickers: [
            { selector: '#filter_by_order_date' },
            { selector: '#filter_by_expected_delivery_date' },
        ]
    }
    
    checkNotification();

    initializeDatatable(config.table);

    config.dropdown.map((cfg) => generateDropdownOptions(cfg));
    config.datepickers.map(({ selector }) => initializeDateRangePicker(selector));

    config.action.forEach((cfg) => multipleActionButton(cfg));

    document.addEventListener('click', async (event) => {
        if (event.target.closest('#apply-filter')) {
            initializeDatatable(config.table);
        }
        
        if (event.target.closest('#reset-filter')) {
            $('#filter_by_supplier').val(null).trigger('change');
            $('#filter_by_warehouse').val(null).trigger('change');
            $('#filter_by_order_date').val(null);
            $('#filter_by_expected_delivery_date').val(null);
            $('#filter_by_status').val(['Draft', 'For Approval', 'Approved', 'On-Process']).trigger('change');

            initializeDatatable(config.table);
        }
    });
});