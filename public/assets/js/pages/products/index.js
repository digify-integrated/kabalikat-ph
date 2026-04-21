import { initializeDatatable } from '../../util/datatable.js';
import { multipleActionButton } from '../../form/button.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: {
            url: '/products/generate-table',
            selector: '#product-table',
            serverSide: false,
            ajaxData: () => ({
                filter_by_product_status: $('#filter_by_product_status').val()
            }),
            columns: [
                { data: 'CHECK_BOX' },
                { data: 'PRODUCT' },
                { data: 'SKU' },
                { data: 'PRODUCT_TYPE' },
                { data: 'BASE_PRICE' },
                { data: 'STATUS' },
            ],
            columnDefs: [
                { width: '5%', bSortable: false, targets: 0, responsivePriority: 1 },
                { width: 'auto', targets: 1, responsivePriority: 2 },
                { width: 'auto', targets: 2, responsivePriority: 3 },
                { width: 'auto', targets: 3, responsivePriority: 4 },
                { width: 'auto', targets: 4, responsivePriority: 5 },
                { width: 'auto', targets: 5, responsivePriority: 6 },
            ],
            onRowClick: (rowData) => {
                if (rowData?.LINK) window.open(rowData.LINK, '_blank');
            },
            addons: {
                controls: true,
                export: 'product',
            }
        },
        action: [
            {
                trigger : '#delete-data',
                url : '/product/delete-multiple',
                swalTitle : 'Confirm Multiple Product Deletion',
                swalText : 'Are you sure you want to delete these product?',
                confirmButtonText : 'Delete',
                validationMessage : 'Please select the products you want to delete',
                table : '#product-table'
            },
        ],
    }
    
    checkNotification();

    initializeDatatable(config.table);

    config.action.forEach((cfg) => multipleActionButton(cfg));

    document.addEventListener('click', async (event) => {
        /*if (event.target.closest('#apply-filter')) {
            initializeDatatable(config.table);
        }

        if (event.target.closest('#reset-filter')) {
            $('#filter_by_product_status').val(null).trigger('change');

            initializeDatatable(config.table);
        }*/
    });
});