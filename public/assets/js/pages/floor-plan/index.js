import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/attribute/generate-table',
            selector: '#attribute-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'ATTRIBUTE' },
                { data: 'SELECTION_TYPE' }
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
                export: 'attribute',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/attribute/delete-multiple',
            swalTitle : 'Confirm Multiple Attribute Deletion',
            swalText : 'Are you sure you want to delete these attributes?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the attributes you want to delete',
            table : '#attribute-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});