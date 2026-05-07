import { initializeDatatable } from '../../util/datatable.js';
import { displayDetails } from '../../form/form.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: [
            {
                url: '/country/generate-table',
                selector: '#country-table',
                serverSide: false,
                columns: [
                    { data: 'CHECK_BOX' },
                    { data: 'COUNTRY' }
                ],
            }
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

    //initializeDatatable(config.table);
});