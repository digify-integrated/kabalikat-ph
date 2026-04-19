import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/unit-conversion/generate-table',
            selector: '#unit-conversion-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'FROM' },
                { data: 'TO' },
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
                export: 'unit_conversion',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/unit-conversion/delete-multiple',
            swalTitle : 'Confirm Multiple Unit Deletion',
            swalText : 'Are you sure you want to delete these unit conversions?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the unit conversions you want to delete',
            table : '#unit-conversion-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});