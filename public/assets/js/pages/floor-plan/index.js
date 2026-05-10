import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/floor-plan/generate-table',
            selector: '#floor-plan-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'FLOOR_PLAN' },
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
                export: 'floor_plan',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/floor-plan/delete-multiple',
            swalTitle : 'Confirm Multiple Attribute Deletion',
            swalText : 'Are you sure you want to delete these floor plans?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the floor plans you want to delete',
            table : '#floor-plan-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});