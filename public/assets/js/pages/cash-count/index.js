import { initializeDatatable } from '../../util/datatable.js';
import { generateDropdownOptions, initializeDateRangePicker } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/shop-order-report/generate-cash-count-table',
            selector: '#cash-count-report-table',
            serverSide: false,            
            order: [[0, 'asc']],
            ajaxData: () => ({
                filter_register: $('#filter_register').val(),
                filter_cashier: $('#filter_cashier').val(),
                filter_variance_status: $('#filter_variance_status').val(),
                filter_session_date: $('#filter_session_date').val(),
            }),
            columns: [
                { data: 'REGISTER' },
                { data: 'CASHIER' },
                { data: 'SESSION' },
                { data: 'OPENING_CASH' },
                { data: 'CASH_SALES' },
                { data: 'EXPECTED_CASH' },
                { data: 'ACTUAL_CASH' },
                { data: 'VARIANCE' },
                { data: 'STATUS' },
                { data: 'OPENED' },
                { data: 'CLOSED' },
            ],
            columnDefs: [
                { width: 'auto', targets: 0, responsivePriority: 2 },
                { width: 'auto', targets: 1, responsivePriority: 3 },
                { width: 'auto', targets: 2, responsivePriority: 4 },
                { width: 'auto', targets: 3, responsivePriority: 5 },
                { width: 'auto', targets: 4, responsivePriority: 6 },
                { width: 'auto', targets: 5, responsivePriority: 7 },
                { width: 'auto', targets: 6, responsivePriority: 8 },
                { width: 'auto', targets: 7, responsivePriority: 9 },
                { width: 'auto', targets: 8, responsivePriority: 10 },
                { width: 'auto', targets: 9, responsivePriority: 11 },
                { width: 'auto', targets: 10, responsivePriority: 12 },
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
            { selector: '#filter_session_date' }
        ]
    };

    initializeDatatable(config.table);

    config.dropdown.map((cfg) => generateDropdownOptions(cfg));
    config.datepickers.map(({ selector }) => initializeDateRangePicker(selector));

    document.addEventListener('click', (event) => {
        if (event.target.closest('#apply-filter')) {
            initializeDatatable(config.table);
        }

        if (event.target.closest('#reset-filter')) {
            $('#filter_register').val(null).trigger('change');
            $('#filter_cashier').val(null).trigger('change');
            $('#filter_variance_status').val(null).trigger('change');
            $('#filter_session_date').val(null);

            initializeDatatable(config.table);
        }
    });
});