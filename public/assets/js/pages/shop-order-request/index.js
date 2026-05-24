import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';
import { initializeDateRangePicker } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/shop-order-request/generate-table',
            selector: '#shop-order-request-table',
            serverSide: false,
            ajaxData: () => ({
                filter_by_request_date: $('#filter_by_request_date').val(),
                filter_by_request_type: $('#filter_by_request_type').val(),
                filter_by_status: $('#filter_by_status').val(),
            }),
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'ORDER_NUMBER' },
                { data: 'REQUEST_TYPE' },
                { data: 'REQUEST_REASON' },
                { data: 'STATUS' },
                { data: 'REQUESTED_BY' },
                { data: 'REQUESTED_AT' },
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
                export: 'shop_order_request',
            }
        },
        action: [
            {
                trigger : '#delete-data',
                url : '/shop-order-request/delete-multiple',
                swalTitle : 'Confirm Multiple Shop Order Request Deletion',
                swalText : 'Are you sure you want to delete these shop order request?',
                confirmButtonText : 'Delete',
                validationMessage : 'Please select the shop order request you want to delete',
                table : '#shop-order-request-table'
            },
        ],
        datepickers: [
            { selector: '#filter_by_request_date' },
        ]
    }
    
    checkNotification();

    initializeDatatable(config.table);

    config.datepickers.map(({ selector }) => initializeDateRangePicker(selector));

    config.action.forEach((cfg) => multipleActionButton(cfg));

    document.addEventListener('click', async (event) => {
        if (event.target.closest('#apply-filter')) {
            initializeDatatable(config.table);
        }
        
        if (event.target.closest('#reset-filter')) {
            $('#filter_by_request_date').val(null);
            $('#filter_by_request_type').val(null).trigger('change');
            $('#filter_by_status').val(['Pending']).trigger('change');

            initializeDatatable(config.table);
        }
    });
});