import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/city/generate-table',
            selector: '#city-table',
            serverSide: false,
            ajaxData: () => ({
                filter_by_state: $('#filter_by_state').val(),
                filter_by_country: $('#filter_by_country').val(),
            }),
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'CITY' },
                { data: 'STATE' },
                { data: 'COUNTRY' },
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
                export: 'city',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/city/delete-multiple',
            swalTitle : 'Confirm Multiple City Deletion',
            swalText : 'Are you sure you want to delete these city?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the city you want to delete',
            table : '#city-table'
        },
        dropdown: [
            { url: '/state/generate-options', dropdownSelector: '#filter_by_state' },
            { url: '/country/generate-options', dropdownSelector: '#filter_by_country' },
        ]
    }
    
    checkNotification();

    initializeDatatable(config.table);

    config.dropdown.forEach(cfg => {
        generateDropdownOptions({
            url: cfg.url,
            dropdownSelector: cfg.dropdownSelector,
            data: { multiple : true }
        });
    });

    multipleActionButton(config.delete);

    document.addEventListener('click', async (event) => {
        if (event.target.closest('#apply-filter')) {
            initializeDatatable(config.table);
        }

        if (event.target.closest('#reset-filter')) {
            $('#filter_by_state').val(null).trigger('change');
            $('#filter_by_country').val(null).trigger('change');

            initializeDatatable(config.table);
        }
    });
});