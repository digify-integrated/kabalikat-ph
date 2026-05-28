import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton } from '../../form/button.js';
import { getPageContext, getCsrfToken, resetForm } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    let searchTimeout;
    let pollInterval; 
    
    // ✅ Track current active ticket IDs on screen to detect new arrivals
    let currentTicketIds = []; 
    
    // ✅ Initialize the kitchen notification audio object
    const alertAudio = new Audio(window.kitchenAlertAudioUrl);

    const appendObject = (params, object = {}) => {
        Object.entries(object).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                params.append(key, value);
            }
        });
    };

    async function generateKitchenTickets(
        url,
        otherData = {},
        isSilent = false
    ) {
        try {
            const csrf = getCsrfToken();
            const ctx = getPageContext();
            const params = new URLSearchParams();

            params.append('detailId', ctx.detailId ?? '');
            appendObject(params, otherData);

            if (!isSilent) {
                renderKitchenLoading();
            }

            const response = await fetch(url, {
                method: 'POST',
                body: params,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    Accept: 'application/json',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                },
            });

            if (!response.ok) {
                throw new Error(`Request failed with status: ${response.status}`);
            }

            const data = await response.json();
            const container = document.getElementById('kitchen-board');

            if (!container) return;

            const route = data.data?.[0] ?? null;

            if (!route || !route.tickets?.length) {
                container.innerHTML = renderNoKitchenTickets();
                // ✅ Reset our tracker if the kitchen board is completely cleared
                currentTicketIds = []; 
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | ITEM-LEVEL LIVE AUDIO NOTIFICATION ENGINE (CHAOS PROOF)
            |--------------------------------------------------------------------------
            */
            // 1. Extract every individual item across ALL active tickets on the board
            const incomingItemsSignatures = [];
            let hasNewKitchenDemands = false;

            route.tickets.forEach(ticket => {
                if (ticket.items && Array.isArray(ticket.items)) {
                    ticket.items.forEach(item => {
                        // 1. Build a strict unique string for every active row
                        const itemSignature = `${item.ticket_item_id}-${item.status}`;
                        incomingItemsSignatures.push(itemSignature);

                        // 2. CRITICAL CONDITION: Only trigger sound if the item is explicitly 'Queued'
                        // and it did NOT exist in our previous screen state snapshot.
                        if (item.status === 'Queued' && currentTicketIds.length > 0) {
                            if (!currentTicketIds.includes(itemSignature)) {
                                hasNewKitchenDemands = true;
                            }
                        }
                    });
                }
            });

            // 3. Play sound only if an actual new demand hit the queue
            if (hasNewKitchenDemands) {
                alertAudio.play().catch(err => {
                    console.warn('Audio playback restricted:', err.message);
                });
            }

            // 4. Always save the snapshot signatures for the next comparison loop
            currentTicketIds = incomingItemsSignatures;

            // 2. If we already have a baseline snapshot on screen, check for changes
            if (currentTicketIds.length > 0) {
                // Check if there's any incoming item signature that didn't exist on the screen 5 seconds ago
                const hasNewKitchenDemands = incomingItemsSignatures.some(
                    signature => !currentTicketIds.includes(signature)
                );
                
                if (hasNewKitchenDemands) {
                    alertAudio.play().catch(err => {
                        console.warn('Audio playback restricted by browser context:', err.message);
                    });
                }
            }

            // 3. Save the new item signatures as our state baseline for the next poll cycle
            currentTicketIds = incomingItemsSignatures;

            container.innerHTML = renderKitchenDisplay(route);

        } catch (error) {
            if (!isSilent) {
                handleSystemError(
                    error,
                    'fetch_failed',
                    `Fetch request failed: ${error.message}`
                );
            } else {
                console.warn('Background auto-refresh synchronized error sync:', error.message);
            }
        }
    }

    function startKitchenAutoRefresh() {
        clearInterval(pollInterval);

        pollInterval = setInterval(() => {
            const searchField = document.getElementById('kitchen_ticket_search');
            const searchVal = searchField ? searchField.value.trim() : '';

            if (searchVal !== '') {
                return;
            }

            generateKitchenTickets(
                '/kitchen-ticket/generate-kitchen-tickets',
                {},
                true 
            );
        }, 5000); 
    }

    function getTicketStatusBadge(status) {
        switch (status) {
            case 'Queued': return 'bg-warning text-dark';
            case 'Preparing': return 'bg-primary';
            case 'Ready': return 'bg-success';
            case 'Cancelled': return 'bg-danger';
            default: return 'bg-secondary';
        }
    }

    function renderKitchenDisplay(route) {
        const grouped = { Queued: [], Preparing: [], Ready: [] };

        route.tickets.forEach(t => {
            if (!grouped[t.ticket_status]) grouped[t.ticket_status] = [];
            grouped[t.ticket_status].push(t);
        });

        return `
        <div class="row g-3 flex-wrap flex-md-nowrap overflow-md-auto pb-2">
            ${Object.entries(grouped).map(([status, tickets]) => `
                <div class="col-12 col-md-4" style="min-width: 280px;">
                    <div class="mb-3 d-flex justify-content-between align-items-center px-2 py-2">
                        <div class="fw-bold text-uppercase small text-muted">${status}</div>
                        <span class="badge bg-secondary">${tickets.length}</span>
                    </div>
                    <div class="d-grid gap-2">
                        ${tickets.map(renderKitchenTicket).join('')}
                    </div>
                </div>
            `).join('')}
        </div>
        `;
    }

    function renderKitchenTicket(ticket) {
        const isDelayed = ticket.minutes_waiting >= 10;
        const borderClass = isDelayed ? 'border-start border-danger border-3' : 'border-0';
        const textTimerClass = isDelayed ? 'text-danger fw-bold blink-text' : 'text-muted';

        return `
        <div class="card shadow-sm ${borderClass}">
            <div class="card-header py-2">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <div class="fw-bold lh-1"># ${ticket.ticket_number}</div>
                        <div class="text-muted small mt-1">
                            ${ticket.floor_plan_name ?? 'Walk-in'} • Table ${ticket.table_number ?? '-'}
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge ${getTicketStatusBadge(ticket.ticket_status)}">${ticket.ticket_status}</span>
                        <div class="${textTimerClass} small mt-1">
                            ${isDelayed ? '⚠️ ' : ''}${Math.floor(ticket.minutes_waiting)} min
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-2">
                <div class="d-grid gap-2">
                   ${ticket.items.map((item, index) => renderKitchenItem(item, index, ticket.items.length, ticket.ticket_status)).join('')}
                </div>
            </div>
        </div>
        `;
    }

    function renderKitchenItem(item, index, totalItems, ticketStatus) {
        const remaining = Number(item.remaining_quantity ?? 0);
        const isCancelled = item.status === 'Cancelled' || Number(item.cancelled_quantity ?? 0) > 0;
        
        let isDone = false;
        if (ticketStatus === 'Queued' && ['Preparing', 'Ready', 'Served'].includes(item.status)) {
            isDone = true;
        } else if (ticketStatus === 'Preparing' && ['Ready', 'Served'].includes(item.status)) {
            isDone = true;
        } else if (ticketStatus === 'Ready' && ['Served'].includes(item.status)) {
            isDone = true;
        }

        const hasBorder = index !== totalItems - 1;
        const rowOpacity = (isDone || isCancelled) ? 'opacity-50' : '';
        const hoverClass = 'kitchen-item-row px-2 transition-all';

        let textDecorationClass = 'text-dark';
        if (isDone) {
            textDecorationClass = 'text-decoration-line-through text-muted';
        } else if (isCancelled) {
            textDecorationClass = 'text-decoration-line-through text-danger';
        }

        return `
        <div
            class="d-flex justify-content-between align-items-center py-2 ${hoverClass} ${hasBorder ? 'border-bottom' : ''} ${rowOpacity}"
            style="cursor: pointer; min-height: 52px;"
            data-action="toggle-kitchen-item"
            data-id="${item.shop_order_item_id}"
            data-ticket-item-id="${item.ticket_item_id}" 
        >
            <div class="d-flex align-items-center gap-3">
                <div class="text-center" style="min-width: 45px;">
                    <span class="fs-5 fw-extrabold ${textDecorationClass}">${remaining}x</span>
                </div>
                <div>
                    <div class="fw-bold fs-6 ${textDecorationClass}">${item.product_name}</div>
                    ${item.note ? `
                        <div class="text-warning-emphasis bg-warning bg-opacity-10 border border-warning-subtle rounded px-2 py-0.5 small fw-medium mt-1">
                            ⚠️ Note: ${item.note}
                        </div>
                    ` : ''}
                </div>
            </div>
            <div class="text-end ps-2">
                ${isDone ? `
                    <span class="badge bg-success text-white px-2 py-1.5 d-flex align-items-center gap-1 shadow-sm">✅ Done</span>
                ` : isCancelled ? `
                    <span class="badge bg-danger text-white px-2 py-1.5 small">VOID</span>
                ` : `
                    <span class="badge bg-warning text-dark border px-2 py-1.5 text-uppercase tracking-wider small fw-bold">TO DO</span>
                `}
            </div>
        </div>
        `;
    }

    function renderKitchenLoading() {
        const container = document.getElementById('kitchen-board');
        if (!container) return;

        container.innerHTML = `
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-15">
                    <div class="spinner-border text-primary mb-3"></div>
                    <div class="fw-bold text-muted">Synchronizing with live inventory tickets...</div>
                </div>
            </div>
        </div>
        `;
    }

    function renderNoKitchenTickets() {
        return `
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-15">
                    <i class="ki-outline ki-chef fs-5tx text-muted mb-5"></i>
                    <div class="fw-bold fs-2 mb-3">No Kitchen Tickets</div>
                    <div class="text-muted">Standing by. Direct food entries from cashier terminals print instantly.</div>
                </div>
            </div>
        </div>
        `;
    }

    generateKitchenTickets('/kitchen-ticket/generate-kitchen-tickets').then(() => {
        startKitchenAutoRefresh();
    });

    document.addEventListener('input', (event) => {
        if (event.target.id !== 'kitchen_ticket_search') return;

        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            const query = event.target.value;

            generateKitchenTickets(
                '/kitchen-ticket/generate-kitchen-tickets',
                { search: query },
                query !== '' 
            );
        }, 250);
    });

    document.addEventListener('click', async (event) => {
        const target = event.target.closest('[data-action="toggle-kitchen-item"]');
        if (!target) return;

        const ticketItemId = target.dataset.ticketItemId;

        try {
            target.classList.add('opacity-50');

            await updateKitchenItemStatus(ticketItemId);
            await generateKitchenTickets('/kitchen-ticket/generate-kitchen-tickets', {}, true);

        } catch (error) {
            console.error(error);
            handleSystemError(error, 'update_failed', 'Failed to update kitchen item');
        } finally {
            target.classList.remove('opacity-50');
        }
    });

    async function updateKitchenItemStatus(ticketItemId) {
        const csrf = getCsrfToken();
        const ctx = getPageContext();
        const params = new URLSearchParams();

        params.append('ticket_item_id', ticketItemId);
        params.append('detailId', ctx.detailId ?? '');

        const response = await fetch('/kitchen-ticket/toggle-item-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
            body: params
        });

        const data = await response.json();

        if (!data.success) {
            showNotification(data.message);

            return;
        }

        return data;
    }

    // Add this inside your DOMContentLoaded listener block
    const unlockBtn = document.getElementById('btn-unlock-kds');
    if (unlockBtn) {
        unlockBtn.addEventListener('click', () => {
            // 1. Play a quick, microscopic silent blip to formally authorize the audio context
            alertAudio.play()
                .then(() => {
                    // 2. Remove the blocker overlay instantly
                    document.getElementById('kds-audio-lock').remove();
                    
                    // 3. Trigger initial ticket fetch now that audio is safe
                    generateKitchenTickets('/kitchen-ticket/generate-kitchen-tickets').then(() => {
                        startKitchenAutoRefresh();
                    });
                })
                .catch(err => {
                    console.error("Audio initialization failed:", err);
                    // Fallback: clear blocker anyway so they can see the board
                    document.getElementById('kds-audio-lock').remove();
                });
        });
    }
});