import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/country/generate-table',
            selector: '#country-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'COUNTRY' }
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
                export: 'country',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/country/delete-multiple',
            swalTitle : 'Confirm Multiple Country Deletion',
            swalText : 'Are you sure you want to delete these countries?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the countries you want to delete',
            table : '#country-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});