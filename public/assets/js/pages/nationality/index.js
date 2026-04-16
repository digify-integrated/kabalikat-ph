import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/nationality/generate-table',
            selector: '#nationality-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'NATIONALITY' }
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
                export: 'nationality',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/nationality/delete-multiple',
            swalTitle : 'Confirm Multiple Nationality Deletion',
            swalText : 'Are you sure you want to delete these nationalities?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the nationalities you want to delete',
            table : '#nationality-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});