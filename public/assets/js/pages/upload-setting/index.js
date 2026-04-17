import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/upload-setting/generate-table',
            selector: '#upload-setting-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'UPLOAD_SETTING' },
                { data: 'MAX_FILE_SIZE' },
                { data: 'ALLOWED_FILE_EXTENSION' },
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
                export: 'upload_setting',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/upload-setting/delete-multiple',
            swalTitle : 'Confirm Multiple Upload Setting Deletion',
            swalText : 'Are you sure you want to delete these upload setting?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the upload setting you want to delete',
            table : '#upload-setting-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});