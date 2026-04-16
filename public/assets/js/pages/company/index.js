import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/app/generate-table',
            selector: '#app-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'APP' }
            ],
            columnDefs: [
                { width: '5%', bSortable: false, targets: 0, responsivePriority: 1 },
                { width: 'auto', targets: 1, responsivePriority: 2 }
            ],
            onRowClick: (rowData) => {
                if (rowData?.LINK) window.open(rowData.LINK, '_blank');
            },
            addons: {
                controls: true,
                export: 'app',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/app/delete-multiple',
            swalTitle : 'Confirm Multiple Apps Deletion',
            swalText : 'Are you sure you want to delete these apps?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the apps you want to delete',
            table : '#app-table'
        },
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});