import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';
import { generateDropdownOptions, initializeDateRangePicker } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/stock-level/generate-table',
            selector: '#stock-level-table',
            serverSide: false,
            ajaxData: () => ({
                filter_by_product: $('#filter_by_product').val(),
                filter_by_warehouse: $('#filter_by_warehouse').val(),
                filter_by_expiration_date: $('#filter_by_expiration_date').val(),
                filter_by_received_date: $('#filter_by_received_date').val(),
                filter_by_status: $('#filter_by_status').val(),
            }),
            columns: [
                { data: 'PRODUCT' },
                { data: 'WAREHOUSE' },
                { data: 'QUANTITY' },
                { data: 'COST_PER_UNIT' },
                { data: 'TOTAL_VALUE' },
                { data: 'BATCH_NUMBER' },
                { data: 'RECEIVED_DATE' },
                { data: 'EXPIRATION_DATE' },
                { data: 'STATUS' },
            ],
            columnDefs: [
                { width: 'auto', targets: 0, responsivePriority: 1 },
                { width: 'auto', targets: 1, responsivePriority: 2 },
                { width: 'auto', targets: 2, responsivePriority: 3 },
                { width: 'auto', targets: 3, responsivePriority: 4 },
                { width: 'auto', targets: 4, responsivePriority: 5 },
                { width: 'auto', targets: 5, responsivePriority: 6 },
                { width: 'auto', targets: 6, type: 'date', responsivePriority: 7 },
                { width: 'auto', targets: 7, responsivePriority: 8 },
                { width: 'auto', targets: 8, responsivePriority: 9 },
            ],
            onRowClick: (rowData) => {
                if (rowData?.LINK) window.open(rowData.LINK, '_blank');
            },
            addons: {
                controls: true,
                export: 'stock_level',
            }
        },
        dropdown: [
            { url: '/products/generate-options', dropdownSelector: '#filter_by_product', data: { multiple : true } },
            { url: '/warehouse/generate-options', dropdownSelector: '#filter_by_warehouse', data: { multiple : true } },
        ],
        datepickers: [
            { selector: '#filter_by_received_date' },
            { selector: '#filter_by_expiration_date' },
        ]
    }
    
    checkNotification();

    initializeDatatable(config.table);

    config.dropdown.map((cfg) => generateDropdownOptions(cfg));
    config.datepickers.map(({ selector }) => initializeDateRangePicker(selector));

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