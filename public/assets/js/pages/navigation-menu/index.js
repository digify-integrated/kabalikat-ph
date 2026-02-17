import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const TABLE_URL = '/generate-navigation-menu-table';
    const TABLE = '#navigation-menu-table';
    const EXPORT = 'navigation_menu';
    const DELETE_TRIGGER = '#delete-navigation-menu';
    const DELETE_URL = '/delete-multiple-navigation-menu';

    const datatableConfig = () => ({
        url: TABLE_URL,
        selector: TABLE,
        serverSide: false,
        ajaxData: {
            filter_by_app: $('#filter_by_app').val(),
            filter_by_parent_menu: $('#filter_by_parent_menu').val()
        },
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
            export: EXPORT,
        }
    });
    
    const dropdownConfigs = [
        { url: '/generate-app-options', dropdownSelector: '#filter_by_app' },
        { url: '/generate-navigation-menu-options', dropdownSelector: '#filter_by_parent_menu' },
    ];
    
    checkNotification();

    initializeDatatable(datatableConfig());

    dropdownConfigs.forEach(cfg => {
        generateDropdownOptions({
            url: cfg.url,
            dropdownSelector: cfg.dropdownSelector,
            data: { multiple : true }
        });
    });

    multipleActionButton({
        'trigger' : DELETE_TRIGGER,
        'url' : DELETE_URL,
        'swalTitle' : 'Confirm Multiple Navigation Menu Deletion',
        'swalText' : 'Are you sure you want to delete these navigation menus?',
        'confirmButtonText' : 'Delete',
        'validationMessage' : 'Please select the navigation menus you want to delete',
        'table' : TABLE
    });

     document.addEventListener('click', async (event) => {
        if (event.target.closest('#apply-filter')) {
            initializeDatatable(datatableConfig());
        }

        if (event.target.closest('#reset-filter')) {
            $('#filter_by_parent_menu').val(null).trigger('change');
            $('#filter_by_app').val(null).trigger('change');

            initializeDatatable(datatableConfig());
        }
    });
});