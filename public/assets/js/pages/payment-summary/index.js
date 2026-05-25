import { initializeDatatable } from '../../util/datatable.js';
import { generateDropdownOptions, initializeDateRangePicker } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/shop-order-report/generate-payment-summary-table',
            selector: '#payment-summary-table',
            serverSide: false,       
            order: [[0, 'asc']],
            ajaxData: () => ({
                filter_payment_method: $('#filter_payment_method').val(),
                filter_payment_status: $('#filter_payment_status').val(),
                filter_cashier: $('#filter_cashier').val(),
                filter_payment_date: $('#filter_payment_date').val(),
            }),
            columns: [
                { data: 'PAYMENT_METHOD' },
                { data: 'ORDER_NO' },
                { data: 'AMOUNT' },
                { data: 'STATUS' },
                { data: 'CASHIER' },
                { data: 'REFERENCE' },
                { data: 'DATE' },
            ]
        },
        dropdown: [
            {
                url: '/payment-method/generate-options',
                dropdownSelector: '#filter_payment_method',
                data: { multiple: true }
            },
            {
                url: '/user/generate-cashier-options',
                dropdownSelector: '#filter_cashier',
                data: { multiple: true }
            }
        ],
        datepickers: [
            { selector: '#filter_payment_date' }
        ]
    };

    initializeDatatable(config.table);

    config.dropdown.forEach(generateDropdownOptions);
    config.datepickers.forEach(({ selector }) => initializeDateRangePicker(selector));

    document.addEventListener('click', (event) => {
        if (event.target.closest('#apply-filter')) {
            initializeDatatable(config.table);
        }

        if (event.target.closest('#reset-filter')) {
            $('#filter_payment_method').val(null).trigger('change');
            $('#filter_payment_status').val(null).trigger('change');
            $('#filter_cashier').val(null).trigger('change');
            $('#filter_payment_date').val(null);

            initializeDatatable(config.table);
        }
    });
});