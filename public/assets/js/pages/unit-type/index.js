import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/unit-type/generate-table',
            selector: '#unit-type-table',
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
                export: 'unit_type',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/unit-type/delete-multiple',
            swalTitle : 'Confirm Multiple Unit Type Deletion',
            swalText : 'Are you sure you want to delete these unit types?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the unit types you want to delete',
            table : '#unit-type-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});