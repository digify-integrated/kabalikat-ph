import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/file-type/generate-table',
            selector: '#file-type-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'FILE_TYPE' }
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
                export: 'file_type',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/file-type/delete-multiple',
            swalTitle : 'Confirm Multiple File Type Deletion',
            swalText : 'Are you sure you want to delete these file types?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the file types you want to delete',
            table : '#file-type-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});