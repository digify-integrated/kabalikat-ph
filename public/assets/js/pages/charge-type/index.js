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
                filter_by_file_type: $('#filter_by_file_type').val(),
            }),
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'FILE_EXTENSION' },
                { data: 'FILE_TYPE' },
            ],
            columnDefs: [
                { width: '5%', bSortable: false, targets: 0, responsivePriority: 1 },
                { width: 'auto', targets: 1, responsivePriority: 2 },
                { width: 'auto', targets: 2, responsivePriority: 3 },
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
        dropdown: [
            { url: '/file-type/generate-options', dropdownSelector: '#filter_by_file_type' },
        ]
    }
    
    checkNotification();

    initializeDatatable(config.table);

    config.dropdown.map((cfg) => generateDropdownOptions(cfg));

    multipleActionButton(config.delete);

    document.addEventListener('click', async (event) => {
        if (event.target.closest('#apply-filter')) {
            initializeDatatable(config.table);
        }

        if (event.target.closest('#reset-filter')) {
            $('#filter_by_file_type').val(null).trigger('change');

            initializeDatatable(config.table);
        }
    });
});