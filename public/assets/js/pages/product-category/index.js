import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/product-category/generate-table',
            selector: '#product-category-table',
            serverSide: false,
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'PRODUCT_CATEGORY' }
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
                export: 'product_category',
            }
        },
        delete: {
            trigger : '#delete-data',
            url : '/product-category/delete-multiple',
            swalTitle : 'Confirm Multiple Product Category Deletion',
            swalText : 'Are you sure you want to delete these product categories?',
            confirmButtonText : 'Delete',
            validationMessage : 'Please select the product categories you want to delete',
            table : '#product-category-table'
        }
    }
    
    checkNotification();

    initializeDatatable(config.table);

    multipleActionButton(config.delete);
});