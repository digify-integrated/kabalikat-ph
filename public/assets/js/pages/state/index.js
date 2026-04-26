import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/state/generate-table',
            selector: '#state-table',
            serverSide: false,
            ajaxData: () => ({
                filter_by_country: $('#filter_by_country').val(),
            }),
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'STATE' },
                { data: 'COUNTRY' },
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
                export: 'state',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/state/delete-multiple',
            swalTitle : 'Confirm Multiple State Deletion',
            swalText : 'Are you sure you want to delete these state?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the state you want to delete',
            table : '#state-table'
        },
        dropdown: [
            { url: '/country/generate-options', dropdownSelector: '#filter_by_country', data: { multiple : true } },
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
            $('#filter_by_country').val(null).trigger('change');

            initializeDatatable(config.table);
        }
    });
});