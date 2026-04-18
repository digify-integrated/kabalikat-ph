import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/unit/generate-table',
            selector: '#unit-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'UNIT_TYPE' }
            ],
            columnDefs: [
                { width: '5%', bSortable: false, targets: 0, responsivePriority: 1 },
                { width: 'auto', targets: 1, responsivePriority: 2 },
            ],
            onRowClick: (rowData) => {
                if (rowData?.LINK) window.open(rowData.LINK, '_blank');
            },
            addons: {
                controls: true,
                export: 'unit',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/unit/delete-multiple',
            swalTitle : 'Confirm Multiple Unit Deletion',
            swalText : 'Are you sure you want to delete these units?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the units you want to delete',
            table : '#unit-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});