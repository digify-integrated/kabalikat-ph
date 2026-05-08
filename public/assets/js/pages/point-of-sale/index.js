import { initializeDatatable } from '../../util/datatable.js';
import { displayDetails } from '../../form/form.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: [
            {
                url: '/inventory-dashboard/generate-out-of-stock-table',
                selector: '#out-of-stock-table',
                serverSide: false,
                order: [[0, 'asc']],
                columns: [
                    { data: 'PRODUCT' }
                ],
            },
            {
                url: '/inventory-dashboard/generate-expired-stock-table',
                selector: '#expired-stock-table',
                serverSide: false,
                order: [[0, 'asc']],
                columns: [
                    { data: 'PRODUCT' },
                    { data: 'BATCH_NUMBER' },
                    { data: 'QUANTITY' },
                    { data: 'EXPIRATION_DATE' },
                ],
            },
            {
                url: '/inventory-dashboard/generate-low-stock-table',
                selector: '#low-stock-table',
                serverSide: false,
                order: [[0, 'asc']],
                columns: [
                    { data: 'PRODUCT' },
                    { data: 'QUANTITY' },
                    { data: 'REORDER_LEVEL' },
                ],
            },
            {
                url: '/inventory-dashboard/generate-near-expiry-table',
                selector: '#near-expiry-table',
                serverSide: false,
                order: [[0, 'asc']],
                columns: [
                    { data: 'PRODUCT' },
                    { data: 'BATCH_NUMBER' },
                    { data: 'QUANTITY' },
                    { data: 'EXPIRATION_DATE' },
                ],
            },
        ],
        details: [
            {
                url: '/inventory-dashboard/fetch-details',
                onSuccess: async (data) => {
                    document.getElementById('out-of-stock-count').textContent = data.outOfStockCount || '0';
                    document.getElementById('expired-items-count').textContent = data.expiredItemsCount || '0';
                    document.getElementById('low-stock-count').textContent = data.lowStockCount || '0';
                    document.getElementById('expiring-soon-count').textContent = data.expiringSoonCount || '0';
                },
            }
        ]
    }
    
    checkNotification()
    
    config.details.map((cfg) => displayDetails(cfg))
    config.table.map((cfg) => initializeDatatable(cfg))
});