import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/shop-register/generate-table',
            selector: '#shop-register-table',
            serverSide: false,
            ajaxData: () => ({
                filter_by_company: $('#filter_by_company').val(),
                filter_by_is_restaurant: $('#filter_by_is_restaurant').val(),
                filter_by_status: $('#filter_by_status').val(),
            }),
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'SHOP_REGISTER' },
                { data: 'COMPANY' },
                { data: 'IS_RESTAURANT' },
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
                export: 'shop_register',
            }
        },
        action: [
            {
                trigger : '#delete-data',
                url : '/shop-register/delete-multiple',
                swalTitle : 'Confirm Multiple Shop Register Deletion',
                swalText : 'Are you sure you want to delete these shop register?',
                confirmButtonText : 'Delete',
                validationMessage : 'Please select the shop registers you want to delete',
                table : '#shop-register-table'
            },
        ],
        dropdown: [
            { url: '/company/generate-options', dropdownSelector: '#filter_by_company', data: { multiple : true } },
        ]
    }
    
    checkNotification();

    initializeDatatable(config.table);

    config.action.forEach((cfg) => multipleActionButton(cfg));
    config.dropdown.map((cfg) => generateDropdownOptions(cfg));

    document.addEventListener('click', async (event) => {
        if (event.target.closest('#apply-filter')) {
            initializeDatatable(config.table);
        }

        if (event.target.closest('#reset-filter')) {
            $('#filter_by_company').val(null).trigger('change');
            $('#filter_by_is_restaurant').val(null).trigger('change');
            $('#filter_by_status').val('Active').trigger('change');

            initializeDatatable(config.table);
        }
    });
});