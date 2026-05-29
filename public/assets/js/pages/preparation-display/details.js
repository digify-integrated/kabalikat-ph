import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton } from '../../form/button.js';
import { getPageContext, getCsrfToken, resetForm } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';
import { generateDropdownOptions, appendObject } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    let searchTimeout;
    let pollInterval; 
    
    // Track current active ticket IDs on screen to detect new arrivals
    let currentTicketIds = []; 
    
    // Initialize the kitchen notification audio object
    const alertAudio = new Audio(window.kitchenAlertAudioUrl);

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
                currentTicketIds = []; 
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | ITEM-LEVEL LIVE AUDIO NOTIFICATION ENGINE
            |--------------------------------------------------------------------------
            */
            const incomingItemsSignatures = [];
            let hasNewKitchenDemands = false;

            route.tickets.forEach(ticket => {
                if (ticket.items && Array.isArray(ticket.items)) {
                    ticket.items.forEach(item => {
                        const itemSignature = `${item.ticket_item_id}-${item.status}`;
                        incomingItemsSignatures.push(itemSignature);

                        if (item.status === 'Queued' && currentTicketIds.length > 0) {
                            if (!currentTicketIds.includes(itemSignature)) {
                                hasNewKitchenDemands = true;
                            }
                        }
                    });
                }
            });

            if (hasNewKitchenDemands) {
                alertAudio.play().catch(err => {
                    console.warn('Audio playback restricted:', err.message);
                });
            }

            currentTicketIds = incomingItemsSignatures;

            // 🌟 STEP 1: Find out which tab is currently selected before rewriting the DOM
            let activeStatusTab = 'Queued'; // Fallback baseline
            const currentActiveTabEl = container.querySelector('#kitchenTabs .nav-link.active');
            if (currentActiveTabEl) {
                // If id is "tab-Preparing", extracting splits string to get "Preparing"
                activeStatusTab = currentActiveTabEl.id.replace('tab-', '');
            }

            // 🌟 STEP 2: Pass the active state indicator string into the display renderer
            container.innerHTML = renderKitchenDisplay(route, activeStatusTab);

        } catch (error) {
            if (!isSilent) {
                handleSystemError(
                    error,
                    fetch_failed,
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
    
    function renderKitchenDisplay(route, activeStatus = 'Queued') {
        const grouped = { Queued: [], Preparing: [], Ready: [] };

        route.tickets.forEach(t => {
            if (grouped[t.ticket_status] !== undefined) {
                grouped[t.ticket_status].push(t);
            }
        });

        // 🌟 OPTIMIZATION: Check if the tab frame navigation shell already exists on the screen
        const existingTabsContainer = document.querySelector('.kitchen-tabbed-display');

        if (existingTabsContainer) {
            // If the main menu shell already exists, update the specific ticket lists and counts directly
            Object.entries(grouped).forEach(([status, tickets]) => {
                
                // 1. Dynamic Badge Count Synchronization without changing focus state
                const countBadge = document.querySelector(`#tab-${status} .badge`);
                if (countBadge) {
                    countBadge.textContent = tickets.length;
                    
                    // Keep context indicator styling aligned with incoming load volumes
                    countBadge.className = 'badge ms-2 px-3 py-2 small shadow-sm';
                    if (tickets.length === 0) countBadge.classList.add('bg-secondary');
                    else if (status === 'Queued') countBadge.classList.add('bg-primary');
                    else if (status === 'Preparing') countBadge.classList.add('bg-warning', 'text-dark');
                    else if (status === 'Ready') countBadge.classList.add('bg-success');
                }

                // 2. Refresh target status card lists safely
                const panelContainer = document.getElementById(`panel-${status}`);
                if (panelContainer) {
                    if (tickets.length === 0) {
                        panelContainer.innerHTML = `
                            <div class="text-center py-5 my-3 text-muted border border-dashed rounded-3 bg-light">
                                <div class="fs-1 mb-2">🍽️</div>
                                <div class="fw-medium">No active tickets inside ${status} group.</div>
                            </div>
                        `;
                    } else {
                        // Update only the child grids within the panel container
                        panelContainer.innerHTML = `
                            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                                ${tickets.map(t => `
                                    <div class="col">
                                        ${renderKitchenTicket(t)}
                                    </div>
                                `).join('')}
                            </div>
                        `;
                    }
                }
            });

            // Return an empty string or null since we modified the targeted sub-nodes directly
            return existingTabsContainer.outerHTML;
        }

        // 🌟 FALLBACK: If this is the initial load, build the entire wrapper frame standard setup
        const tabHeaders = Object.entries(grouped).map(([status, tickets]) => {
            const isActive = status === activeStatus ? 'active' : '';
            let badgeColor = 'bg-secondary';
            
            if (status === 'Queued' && tickets.length > 0) badgeColor = 'bg-primary';
            if (status === 'Preparing' && tickets.length > 0) badgeColor = 'bg-warning text-dark';
            if (status === 'Ready' && tickets.length > 0) badgeColor = 'bg-success';

            return `
                <li class="nav-item flex-fill text-center" role="presentation">
                    <button 
                        class="nav-link w-100 py-3 fs-5 fw-bold text-uppercase position-relative ${isActive}" 
                        id="tab-${status}" 
                        data-bs-toggle="tab" 
                        data-bs-target="#panel-${status}" 
                        type="button" 
                        role="tab" 
                        aria-controls="panel-${status}" 
                        aria-selected="${status === activeStatus ? 'true' : 'false'}"
                    >
                        ${status}
                        <span class="badge ${badgeColor} ms-2 px-2.5 py-1.5 rounded-circle small shadow-sm">${tickets.length}</span>
                    </button>
                </li>
            `;
        }).join('');

        const tabContents = Object.entries(grouped).map(([status, tickets]) => {
            const isActive = status === activeStatus ? 'show active' : '';
            
            return `
                <div 
                    class="tab-pane fade ${isActive} pt-3" 
                    id="panel-${status}" 
                    role="tabpanel" 
                    aria-labelledby="tab-${status}"
                >
                    ${tickets.length === 0 ? `
                        <div class="text-center py-5 my-3 text-muted border border-dashed rounded-3 bg-light">
                            <div class="fs-1 mb-2">🍽️</div>
                            <div class="fw-medium">No active tickets inside ${status} status group.</div>
                        </div>
                    ` : `
                        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                            ${tickets.map(t => `
                                <div class="col">
                                    ${renderKitchenTicket(t)}
                                </div>
                            `).join('')}
                        </div>
                    `}
                </div>
            `;
        }).join('');

        return `
            <div class="kitchen-tabbed-display w-100">
                <ul class="nav nav-pills nav-justified border p-1 rounded shadow-sm gap-1" id="kitchenTabs" role="tablist">
                    ${tabHeaders}
                </ul>

                <div class="tab-content mt-2" id="kitchenTabsContent">
                    ${tabContents}
                </div>
            </div>
        `;
    }

    function renderKitchenTicket(ticket) {
        const minutes = ticket.minutes_waiting;
        const isDelayed = minutes >= 20;
        const isWarning = minutes >= 10 && minutes < 20;
        
        // Determine styling based on ticket urgency
        let cardBorder = 'border-neutral-200';
        let headerBg = '';
        let timerBadge = 'text-dark fw-bold';
        let icon = '⏱️ ';

        if (isDelayed) {
            cardBorder = 'border-danger border-2';
            headerBg = 'bg-danger text-white'; // Added text-white for danger contrast
            timerBadge = 'fw-bold';
            icon = '🚨 ';
        } else if (isWarning) {
            cardBorder = 'border-warning border-2';
            headerBg = 'bg-warning text-dark'; // Kept text-dark for yellow contrast
            timerBadge = 'fw-bold';
            icon = '⚠️ ';
        }

        return `
        <div class="card h-100 border overflow-hidden ${cardBorder}">
            <div class="card-header d-flex justify-content-between align-items-center px-5 py-2.5 ${headerBg}">
                <div>
                    <span class="fs-4 fw-bold tracking-tight"># ${ticket.ticket_number}</span>
                    <div class="small opacity-80 fw-semibold text-uppercase tracking-wider mt-0.5">
                        ${ticket.floor_plan_name ?? 'Walk-in'} • <span class="fw-bold">TBL ${ticket.table_number ?? '-'}</span>
                    </div>
                </div>
                <div>
                    <span class="fs-6 px-3 py-2 ${timerBadge}">
                        ${icon}${Math.floor(minutes)}m
                    </span>
                </div>
            </div>
            
            <div class="card-body p-2 bg-light">
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

        // Modern State Styling Configuration Matrix
        let containerClass = 'border-light text-dark';
        let qtyBadgeClass = 'bg-primary text-white';
        let textClass = 'fw-bold text-dark';
        let actionIndicator = `<span class="text-muted opacity-40 fw-bold fs-5">⚡</span>`;

        if (isDone) {
            containerClass = 'bg-secondary bg-opacity-10 border-transparent opacity-60';
            qtyBadgeClass = 'bg-secondary text-white-50';
            textClass = 'text-decoration-line-through text-muted fw-normal';
            actionIndicator = `<span class="text-success fw-bold fs-5">✓</span>`;
        } else if (isCancelled) {
            containerClass = 'bg-danger bg-opacity-10 border-danger border-opacity-20';
            qtyBadgeClass = 'bg-danger text-white';
            textClass = 'text-decoration-line-through text-danger';
            actionIndicator = `<span class="badge bg-danger text-uppercase tracking-wider fs-7">VOID</span>`;
        }

        return `
        <div
            class="d-flex justify-content-between align-items-center p-4 rounded border transition-all shadow-xs user-select-none ${containerClass}"
            style="cursor: pointer; min-height: 58px;"
            data-action="toggle-kitchen-item"
            data-id="${item.shop_order_item_id}"
            data-ticket-item-id="${item.ticket_item_id}" 
            onmousedown="this.style.transform='scale(0.98)'"
            onmouseup="this.style.transform='scale(1)'"
            onmouseleave="this.style.transform='scale(1)'"
        >
            <div class="d-flex align-items-center gap-3 flex-grow-1">
                <div class="rounded-2 ${qtyBadgeClass} d-flex align-items-center justify-content-center" style="width: 44px; height: 38px;">
                    <span class="fs-4 fw-bold">${remaining}</span>
                </div>
                
                <div class="flex-grow-1">
                    <div class="${textClass}" style="font-size: 1.05rem; line-height: 1.25; letter-spacing: -0.01em;">
                        ${item.product_name}
                    </div>
                    ${item.order_note ? `
                        <div class="bg-warning text-dark border border-warning border-opacity-20 rounded px-2 py-0.5 mt-1 fw-bold tracking-wide text-uppercase" style="font-size: 0.75rem;">
                            ⚠️ Note: ${item.order_note}
                        </div>
                    ` : ''}
                </div>
            </div>
            
            <div class="ps-3 text-end flex-shrink-0">
                ${actionIndicator}
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
            if (target) target.classList.remove('opacity-50');
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

    const unlockBtn = document.getElementById('btn-unlock-kds');
    if (unlockBtn) {
        unlockBtn.addEventListener('click', () => {
            alertAudio.play()
                .then(() => {
                    document.getElementById('kds-audio-lock').remove();
                    generateKitchenTickets('/kitchen-ticket/generate-kitchen-tickets').then(() => {
                        startKitchenAutoRefresh();
                    });
                })
                .catch(err => {
                    console.error("Audio initialization failed:", err);
                    document.getElementById('kds-audio-lock').remove();
                });
        });
    }
});