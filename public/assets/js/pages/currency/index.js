import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/currency/generate-table',
            selector: '#currency-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'CURRENCY' },
                { data: 'SYMBOL' },
                { data: 'SHORTHAND' },
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
                export: 'currency',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/currency/delete-multiple',
            swalTitle : 'Confirm Multiple Currency Deletion',
            swalText : 'Are you sure you want to delete these nationalities?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the nationalities you want to delete',
            table : '#currency-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});