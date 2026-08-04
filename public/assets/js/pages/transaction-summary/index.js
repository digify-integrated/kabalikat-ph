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
            { data: 'ORDER_NO' },      // 0
            { data: 'REGISTER' },      // 1
            { data: 'CASHIER' },       // 2
            { data: 'ORDER_TYPE' },    // 3
            { data: 'ORDER_STATUS' },  // 4
            { data: 'PAYMENT_STATUS' },// 5
            { data: 'ITEMS' },         // 6
            { data: 'GROSS' },         // 7
            { data: 'DISCOUNT' },      // 8
            { data: 'CHARGES' },       // 9
            { data: 'NET' },           // 10
            { data: 'VAT' },           // 11
            { data: 'VATABLE' },       // 12
            { data: 'VAT_EXEMPT' },    // 13
            { data: 'ZERO_RATED' },    // 14
            { data: 'PAID' },          // 15
            { data: 'CHANGE' },        // 16
            { data: 'BALANCE' },       // 17
            { data: 'DATE' },          // 18
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
        ],
        footerCallback: function (row, data, start, end, display) {
            const api = this.api();

            // Helper function to strip currency/commas and convert to float
            const parseVal = function (i) {
                if (typeof i === 'string') {
                    return parseFloat(i.replace(/[\$,]/g, '')) || 0;
                }
                return typeof i === 'number' ? i : 0;
            };

            // Helper function to calculate and display total for a column index
            const sumColumn = function (colIndex, isCurrency = true) {
                const total = api
                    .column(colIndex, { page: 'all' }) // Use { page: 'current' } for current page only
                    .data()
                    .reduce((a, b) => parseVal(a) + parseVal(b), 0);

                // Format as currency/2 decimal places or plain integer
                const formattedTotal = isCurrency 
                    ? total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : Math.round(total);

                $(api.column(colIndex).footer()).html(formattedTotal);
            };

            // Calculate totals for columns (6 through 17)
            sumColumn(6, false); // ITEMS (Integer count)
            sumColumn(7);  // GROSS
            sumColumn(8);  // DISCOUNT
            sumColumn(9);  // CHARGES
            sumColumn(10); // NET
            sumColumn(11); // VAT
            sumColumn(12); // VATABLE
            sumColumn(13); // VAT_EXEMPT
            sumColumn(14); // ZERO_RATED
            sumColumn(15); // PAID
            sumColumn(16); // CHANGE
            sumColumn(17); // BALANCE
        }
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