import { initializeDatatable } from '../../util/datatable.js';
import { generateDropdownOptions, initializeDateRangePicker } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/shop-order-report/generate-transaction-summary-table',
            selector: '#transaction-summary-table',
            serverSide: false,
            order: [[0, 'asc']],
            ajaxData: () => ({
                filter_order_type: $('#filter_order_type').val(),
                filter_order_status: $('#filter_order_status').val(),
                filter_payment_status: $('#filter_payment_status').val(),
                filter_register: $('#filter_register').val(),
                filter_cashier: $('#filter_cashier').val(),
                filter_transaction_date: $('#filter_transaction_date').val(),
            }),
            columns: [
                { data: 'ORDER_NO' },
                { data: 'REGISTER' },
                { data: 'CASHIER' },
                { data: 'ORDER_TYPE' },
                { data: 'ORDER_STATUS' },
                { data: 'PAYMENT_STATUS' },
                { data: 'ITEMS' },
                { data: 'GROSS' },
                { data: 'DISCOUNT' },
                { data: 'CHARGES' },
                { data: 'NET' },
                { data: 'VAT' },
                { data: 'VATABLE' },
                { data: 'VAT_EXEMPT' },
                { data: 'ZERO_RATED' },
                { data: 'PAID' },
                { data: 'CHANGE' },
                { data: 'BALANCE' },
                { data: 'DATE' },
            ],
            columnDefs: [
                { width: 'auto', targets: 0, responsivePriority: 1 },
                { width: 'auto', targets: 1, responsivePriority: 2 },
                { width: 'auto', targets: 2, responsivePriority: 3 },
                { width: 'auto', targets: 3, responsivePriority: 4 },
                { width: 'auto', targets: 4, responsivePriority: 5 },
                { width: 'auto', targets: 5, responsivePriority: 6 },
                { width: 'auto', targets: 6, responsivePriority: 7 },
                { width: 'auto', targets: 7, responsivePriority: 8 },
                { width: 'auto', targets: 8, responsivePriority: 9 },
                { width: 'auto', targets: 9, responsivePriority: 10 },
                { width: 'auto', targets: 10, responsivePriority: 11 },
                { width: 'auto', targets: 11, responsivePriority: 12 },
                { width: 'auto', targets: 12, responsivePriority: 13 },
                { width: 'auto', targets: 13, responsivePriority: 14 },
                { width: 'auto', targets: 14, responsivePriority: 15 },
                { width: 'auto', targets: 15, responsivePriority: 16 },
                { width: 'auto', targets: 16, responsivePriority: 17 },
                { width: 'auto', targets: 17, responsivePriority: 18 },
                { width: 'auto', targets: 18, responsivePriority: 19 },
            ]
        },

        dropdown: [
            {
                url: '/shop-register/generate-options',
                dropdownSelector: '#filter_register',
                data: { multiple: true }
            },
            {
                url: '/user/generate-cashier-options',
                dropdownSelector: '#filter_cashier',
                data: { multiple: true }
            },
        ],

        datepickers: [
            {
                selector: '#filter_transaction_date'
            }
        ]
    };

    initializeDatatable(config.table);

    config.dropdown.forEach(cfg => generateDropdownOptions(cfg));

    config.datepickers.forEach(({ selector }) => initializeDateRangePicker(selector));

    document.addEventListener('click', (event) => {
        if (event.target.closest('#apply-filter')) {
            initializeDatatable(config.table);
        }

        if (event.target.closest('#reset-filter')) {
            $('#filter_order_type').val(null).trigger('change');
            $('#filter_order_status').val(null).trigger('change');
            $('#filter_payment_status').val(null).trigger('change');
            $('#filter_register').val(null).trigger('change');
            $('#filter_cashier').val(null).trigger('change');
            $('#filter_transaction_date').val(null);

            initializeDatatable(config.table);
        }
    });
});