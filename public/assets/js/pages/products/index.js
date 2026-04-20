import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/user/generate-table',
            selector: '#user-table',
            serverSide: false,
            ajaxData: () => ({
                filter_by_user_status: $('#filter_by_user_status').val()
            }),
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'USER' },
                { data: 'STATUS' },
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
                export: 'users',
            }
        },
        action: [
            {
                trigger : '#delete-data',
                url : '/user/delete-multiple',
                swalTitle : 'Confirm Multiple User Deletion',
                swalText : 'Are you sure you want to delete these user?',
                confirmButtonText : 'Delete',
                validationMessage : 'Please select the users you want to delete',
                table : '#user-table'
            },
            {
                trigger : '#activate-data',
                url : '/user/activate-multiple',
                swalTitle : 'Confirm Multiple User Activation',
                swalText : 'Are you sure you want to activate these user?',
                confirmButtonText : 'Activate',
                validationMessage : 'Please select the users you want to activate',
                confirmButtonClass : 'success',
                table : '#user-table'
            },
            {
                trigger : '#deactivate-data',
                url : '/user/deactivate-multiple',
                swalTitle : 'Confirm Multiple User Deactivation',
                swalText : 'Are you sure you want to deactivate these user?',
                confirmButtonText : 'Activate',
                validationMessage : 'Please select the users you want to deactivate',
                table : '#user-table'
            }
        ],
    }
    
    checkNotification();

    initializeDatatable(config.table);

    config.action.forEach((cfg) => multipleActionButton(cfg));

    document.addEventListener('click', async (event) => {
        if (event.target.closest('#apply-filter')) {
            initializeDatatable(config.table);
        }

        if (event.target.closest('#reset-filter')) {
            $('#filter_by_user_status').val(null).trigger('change');

            initializeDatatable(config.table);
        }
    });
});