import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/navigation-menu/generate-table',
            selector: '#navigation-menu-table',
            serverSide: false,
            ajaxData: () => ({
                filter_by_app: $('#filter_by_app').val(),
                filter_by_parent_menu: $('#filter_by_parent_menu').val()
            }),
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'NAVIGATION_MENU' },
                { data: 'APP_NAME' },
                { data: 'PARENT_NAME' },
                { data: 'ORDER_SEQUENCE' }
            ],
            columnDefs: [
                { width: '5%', bSortable: false, targets: 0, responsivePriority: 1 },
                { width: 'auto', targets: 1, responsivePriority: 2 },
                { width: 'auto', targets: 2, responsivePriority: 3 },
                { width: 'auto', targets: 3, responsivePriority: 4 },
                { width: 'auto', targets: 4, responsivePriority: 5 }
            ],
            onRowClick: (rowData) => {
                if (rowData?.LINK) window.open(rowData.LINK, '_blank');
            },
            addons: {
                controls: true,
                export: 'navigation_menu',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/navigation-menu/delete-multiple',
            swalTitle : 'Confirm Multiple Navigation Menu Deletion',
            swalText : 'Are you sure you want to delete these navigation menus?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the navigation menus you want to delete',
            table : '#navigation-menu-table'
        },
        dropdown: [
            { url: '/app/generate-options', dropdownSelector: '#filter_by_app' },
            { url: '/navigation-menu/generate-options', dropdownSelector: '#filter_by_parent_menu' },
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
            $('#filter_by_parent_menu').val(null).trigger('change');
            $('#filter_by_app').val(null).trigger('change');

            initializeDatatable(config.table);
        }
    });
});