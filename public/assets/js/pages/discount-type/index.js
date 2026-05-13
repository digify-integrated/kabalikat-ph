import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/discount-type/generate-table',
            selector: '#discount-type-table',
            serverSide: false,
            ajaxData: () => ({
                filter_by_value_type: $('#filter_by_value_type').val(),
                filter_by_is_variable: $('#filter_by_is_variable').val(),
                filter_by_application_order: $('#filter_by_application_order').val(),
                filter_by_tax_type: $('#filter_by_tax_type').val(),
            }),
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'DISCOUNT_TYPE' },
                { data: 'DISCOUNT_VALUE' },
                { data: 'IS_VARIABLE' },
                { data: 'APPLICATION_ORDER' },
                { data: 'IS_VAT_EXEMPT' },
            ],
            columnDefs: [
                { width: '5%', bSortable: false, targets: 0, responsivePriority: 1 },
                { width: 'auto', targets: 1, responsivePriority: 2 },
                { width: 'auto', targets: 2, responsivePriority: 3 },
                { width: 'auto', targets: 3, responsivePriority: 4 },
                { width: 'auto', targets: 4, responsivePriority: 5 },
                { width: 'auto', targets: 5, responsivePriority: 6 },
            ],
            onRowClick: (rowData) => {
                if (rowData?.LINK) window.open(rowData.LINK, '_blank');
            },
            addons: {
                controls: true,
                export: 'discount_type',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/discount-type/delete-multiple',
            swalTitle : 'Confirm Multiple Discount Type Deletion',
            swalText : 'Are you sure you want to delete these discount type?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the discount type you want to delete',
            table : '#discount-type-table'
        },
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);

    document.addEventListener('click', async (event) => {
        if (event.target.closest('#apply-filter')) {
            initializeDatatable(config.table);
        }

        if (event.target.closest('#reset-filter')) {
            $('#filter_by_value_type').val(null).trigger('change');
            $('#filter_by_is_variable').val(null).trigger('change');
            $('#filter_by_application_order').val(null).trigger('change');
            $('#filter_by_tax_type').val(null).trigger('change');

            initializeDatatable(config.table);
        }
    });
});