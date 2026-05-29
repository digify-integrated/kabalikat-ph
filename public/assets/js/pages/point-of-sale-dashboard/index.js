import { initializeDatatable } from '../../util/datatable.js';
import { displayDetails } from '../../form/form.js';
import { checkNotification } from '../../util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        table: [
            {
                url: '/point-of-sale-dashboard/generate-recent-orders-table',
                selector: '#recent-orders-table',
                serverSide: false,
                order: [[0, 'desc']],
                columns: [
                    { data: 'ORDER_INFO' },
                    { data: 'LOCATION_CONTEXT' },
                    { data: 'STATUS' },
                    { data: 'NET_TOTAL', className: 'text-end' }
                ],
            },
            {
                url: '/point-of-sale-dashboard/generate-payments-ledger-table',
                selector: '#payments-ledger-table',
                serverSide: false,
                order: [[0, 'desc']],
                columns: [
                    { data: 'ORDER_REF' },
                    { data: 'METHOD' },
                    { data: 'TRACE_REF' },
                    { data: 'STATUS' },
                    { data: 'AMOUNT', className: 'text-end' }
                ],
            }
        ],
        details: [
            {
                url: '/point-of-sale-dashboard/fetch-details',
                onSuccess: async (data) => {
                    document.getElementById('gross-sales-count').textContent = data.grossSales || '0.00';
                    document.getElementById('net-sales-count').textContent = data.netSales || '0.00';
                    document.getElementById('total-orders-count').textContent = data.totalOrders || '0';
                    document.getElementById('avg-order-value').textContent = data.avgOrderValue || '0.00';
                },
            }
        ]
    };
    
    checkNotification();
    
    config.details.map((cfg) => displayDetails(cfg));
    config.table.map((cfg) => initializeDatatable(cfg));
});