import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/charge-type/generate-table',
            selector: '#charge-type-table',
            serverSide: false,
            ajaxData: () => ({
                filter_by_value_type: $('#filter_by_value_type').val(),
                filter_by_is_variable: $('#filter_by_is_variable').val(),
                filter_by_application_order: $('#filter_by_application_order').val(),
                filter_by_tax_type: $('#filter_by_tax_type').val(),
            }),
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'CHARGE_TYPE' },
                { data: 'CHARGE_VALUE' },
                { data: 'APPLICATION_ORDER' },
                { data: 'TAX_TYPE' },
            ],
            columnDefs: [
                { width: '5%', bSortable: false, targets: 0, responsivePriority: 1 },
                { width: 'auto', targets: 1, responsivePriority: 2 },
                { width: 'auto', targets: 2, responsivePriority: 3 },
                { width: 'auto', targets: 3, responsivePriority: 4 },
                { width: 'auto', targets: 4, responsivePriority: 5 },
            ],
            onRowClick: (rowData) => {
                if (rowData?.LINK) window.open(rowData.LINK, '_blank');
            },
            addons: {
                controls: true,
                export: 'charge_type',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/charge-type/delete-multiple',
            swalTitle : 'Confirm Multiple Charge Type Deletion',
            swalText : 'Are you sure you want to delete these charge type?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the charge type you want to delete',
            table : '#charge-type-table'
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