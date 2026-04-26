import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/file-extension/generate-table',
            selector: '#file-extension-table',
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
                export: 'file_extension',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/file-extension/delete-multiple',
            swalTitle : 'Confirm Multiple File Extension Deletion',
            swalText : 'Are you sure you want to delete these file extension?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the file extension you want to delete',
            table : '#file-extension-table'
        },
        dropdown: [
            { url: '/file-type/generate-options', dropdownSelector: '#filter_by_file_type' },
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
            $('#filter_by_file_type').val(null).trigger('change');

            initializeDatatable(config.table);
        }
    });
});