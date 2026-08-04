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
                { data: 'PAYMENT_METHOD' }, // 0
                { data: 'ORDER_NO' },       // 1
                { data: 'STATUS' },         // 2
                { data: 'CASHIER' },        // 3
                { data: 'REFERENCE' },      // 4
                { data: 'DATE' },           // 5
                { data: 'AMOUNT' },         // 6
            ],
            footerCallback: function (row, data, start, end, display) {
                const api = this.api();

                // Helper to clean formatting (commas/currency symbols) and parse as float
                const parseVal = function (i) {
                    if (typeof i === 'string') {
                        return parseFloat(i.replace(/[\$,]/g, '')) || 0;
                    }
                    return typeof i === 'number' ? i : 0;
                };

                // Calculate total for AMOUNT (column index 6) across all filtered pages
                const totalAmount = api
                    .column(6, { page: 'all' }) // Use { page: 'current' } for page-only total
                    .data()
                    .reduce((a, b) => parseVal(a) + parseVal(b), 0);

                // Format total with commas and 2 decimal places
                const formattedTotal = totalAmount.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });

                // Insert into the footer cell for column 6
                $(api.column(6).footer()).html(formattedTotal);
            }
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