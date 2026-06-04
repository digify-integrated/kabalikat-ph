import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton } from '../../form/button.js';
import { getPageContext, getCsrfToken, resetForm } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';
import { generateDropdownOptions, appendObject } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    let searchTimeout;
    let pollInterval;     
    let currentTicketIds = []; 
    
    // Stateful tracking map for escalation alarms thresholds
    let trackedOverdueTickets = {};
    
    // NEW STATE MAPS FOR HANDLING MULTIPLE OVERDUES
    let overdueTicketsQueue = [];
    let isBoardLaunched = false; // Rigid lock gate preventing pre-rendered modal popups
    let bootstrapModalInstance = null; // Uninstantiated globally on initial download load

    const audioPath = window.kitchenAlertAudioUrl || '/audio/kitchen-alarm.mp3'; 
    const alertAudio = new Audio(audioPath);
    alertAudio.preload = 'auto';

    // Elements
    const unlockBtn = document.getElementById('btn-unlock-kds');
    const alarmModalElement = document.getElementById('kdsAlarmModal');
    
    const acknowledgeBtn = document.getElementById('btn-kds-acknowledge');
    const snoozeBtn = document.getElementById('btn-kds-snooze');

    // Helper to render the top item inside our ticket warning queue stack cleanly
    function processedNextQueuedAlarm() {
        if (overdueTicketsQueue.length === 0) {
            alertAudio.pause();
            alertAudio.currentTime = 0;
            if (bootstrapModalInstance) {
                bootstrapModalInstance.hide();
            }
            return;
        }

        const currentTicket = overdueTicketsQueue[0];
        
        // 1. Dynamically update core modal text descriptors
        document.getElementById('kdsModalTicketNum').textContent = `TICKET #${currentTicket.ticket_number}`;
        document.getElementById('kdsModalTicketMeta').textContent = `${currentTicket.floor_plan_name ?? 'Walk-in'} • TBL ${currentTicket.table_number ?? '-'}`;
        document.getElementById('kdsModalTicketTime').textContent = Math.floor(currentTicket.minutes_waiting);

        // 2. NEW: Extract and inject ONLY active items (skip cooked/served/cancelled rows)
        const itemsListContainer = document.getElementById('kdsModalTicketItemsList');
        if (itemsListContainer && currentTicket.items && Array.isArray(currentTicket.items)) {
            
            // Filter to capture items that still need kitchen production attention
            const activeItems = currentTicket.items.filter(item => {
                const remaining = Number(item.remaining_quantity ?? 0);
                const isCancelled = item.status === 'Cancelled' || Number(item.cancelled_quantity ?? 0) > 0;
                
                let isDone = false;
                if (currentTicket.ticket_status === 'Queued' && ['Preparing', 'Ready', 'Served'].includes(item.status)) isDone = true;
                if (currentTicket.ticket_status === 'Preparing' && ['Ready', 'Served'].includes(item.status)) isDone = true;
                if (currentTicket.ticket_status === 'Ready' && ['Served'].includes(item.status)) isDone = true;

                return remaining > 0 && !isCancelled && !isDone;
            });

            // Loop and append neat UI line items to the container element
            itemsListContainer.innerHTML = activeItems.map(item => `
                <div class="d-flex align-items-center gap-3 p-2.5 rounded border border-gray-200 shadow-xs">
                    <div class="bg-danger text-white rounded-2 d-flex align-items-center justify-content-center fw-bold fs-5 shadow-sm" style="width: 36px; height: 32px; min-width: 36px;">
                        ${Number(item.remaining_quantity ?? 0)}
                    </div>
                    <div class="text-start flex-grow-1">
                        <div class="fw-extrabold text-dark fs-6" style="line-height: 1.2;">
                            ${item.product_name}
                        </div>
                        ${item.order_note ? `
                            <div class="text-danger border-start border-danger border-2 ps-2 mt-0.5 fw-semibold" style="font-size: 0.75rem;">
                                📝 Note: ${item.order_note}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');

            // Fallback backup display in case backend statuses state shifts unexpectedly
            if (activeItems.length === 0) {
                itemsListContainer.innerHTML = `<div class="text-muted text-center py-2 small">All item batches cleared down. Check status.</div>`;
            }
        }

        // 3. Update warning subtexts if multiple tickets are waiting queued back-to-back
        const queueNotice = document.getElementById('kdsModalQueueNotice');
        if (overdueTicketsQueue.length > 1) {
            queueNotice.innerHTML = `<span class="text-danger fw-bold">⚠️ [${overdueTicketsQueue.length} Overdue Tickets Pending]</span> Acknowledge this item to view the next card reminder.`;
        } else {
            queueNotice.textContent = "The kitchen staff must acknowledge this warning immediately or choose to snooze the siren alert.";
        }

        // 4. Fire continuous loops
        alertAudio.loop = true;
        alertAudio.play().catch(err => console.warn('Audio restriction handled:', err.message));

        if (bootstrapModalInstance) {
            bootstrapModalInstance.show();
        }
    }

    async function generateKitchenTickets(url, otherData = {}, isSilent = false) {
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
                trackedOverdueTickets = {}; 
                overdueTicketsQueue = [];
                return;
            }

            const incomingItemsSignatures = [];
            let hasNewKitchenDemands = false;
            const currentActiveTicketNumbers = [];
            
            // Temporary collection stack for this specific poll interval cycle iteration
            let newlyFoundOverdueTickets = [];

            route.tickets.forEach(ticket => {
                const ticketNum = ticket.ticket_number;
                
                if (['Queued', 'Preparing', 'Ready'].includes(ticket.ticket_status)) {
                    currentActiveTicketNumbers.push(ticketNum);
                    
                    const minutes = Math.floor(Number(ticket.minutes_waiting ?? 0));

                    // Escalation Alarm Logic
                    if (minutes >= 20) {
                        let isAnAlarmMilestone = false;
                        if (!(ticketNum in trackedOverdueTickets)) {
                            isAnAlarmMilestone = true;
                            trackedOverdueTickets[ticketNum] = 25; 
                        } else if (minutes >= trackedOverdueTickets[ticketNum]) {
                            isAnAlarmMilestone = true;
                            trackedOverdueTickets[ticketNum] = Math.floor(minutes / 5) * 5 + 5; 
                        }

                        if (isAnAlarmMilestone) {
                            // Verify it isn't already inside our processing collection array
                            if (!overdueTicketsQueue.some(t => t.ticket_number === ticketNum)) {
                                newlyFoundOverdueTickets.push(ticket);
                            }
                        }
                    }
                }

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

            // Clean up tracking memory for tickets that are completed, cancelled, or served
            Object.keys(trackedOverdueTickets).forEach(ticketNum => {
                if (!currentActiveTicketNumbers.includes(Number(ticketNum)) && !currentActiveTicketNumbers.includes(String(ticketNum))) {
                    delete trackedOverdueTickets[ticketNum];
                }
            });

            // Strip items from the current active display array if they were cleared off from cashier terminals out of band
            overdueTicketsQueue = overdueTicketsQueue.filter(t => currentActiveTicketNumbers.includes(t.ticket_number));

            // CRITICAL LOCK GATE: Append and display popups ONLY if board has been unlocked by user interaction
            if (isBoardLaunched) {
                if (newlyFoundOverdueTickets.length > 0) {
                    overdueTicketsQueue = [...overdueTicketsQueue, ...newlyFoundOverdueTickets];
                    
                    // If the modal isn't currently displayed open, trigger the newly prioritized alarm array head
                    const isModalOpen = alarmModalElement && alarmModalElement.classList.contains('show');
                    if (!isModalOpen) {
                        processedNextQueuedAlarm();
                    }
                } else if (hasNewKitchenDemands) {
                    alertAudio.loop = false;
                    alertAudio.play().catch(err => console.warn('Audio restriction handled:', err.message));
                    showNotification(`🔔 NEW ORDER: Fresh tickets added to the kitchen display board.`);
                }
            }

            currentTicketIds = incomingItemsSignatures;

            let activeStatusTab = 'Queued';
            const currentActiveTabEl = container.querySelector('#kitchenTabs .nav-link.active');

            if (currentActiveTabEl) {
                activeStatusTab = currentActiveTabEl.id.replace('tab-', '');
            }

            container.innerHTML = renderKitchenDisplay(route, activeStatusTab);
        }
        catch (error) {
            if (!isSilent) {
                handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
            }
            else {
                console.warn('Background auto-refresh synchronized error sync:', error.message);
            }
        }
    }

    // Modal Button Trigger - ACKNOWLEDGE
    if (acknowledgeBtn) {
        acknowledgeBtn.addEventListener('click', () => {
            // Remove the resolved item from the top of the array queue stack
            overdueTicketsQueue.shift(); 
            processedNextQueuedAlarm();
        });
    }

    // Modal Button Trigger - SNOOZE
    if (snoozeBtn) {
        snoozeBtn.addEventListener('click', () => {
            if (overdueTicketsQueue.length > 0) {
                const currentTicket = overdueTicketsQueue.shift();
                const ticketNum = currentTicket.ticket_number;
                const currentMinutes = Math.floor(Number(currentTicket.minutes_waiting ?? 0));
                
                // Adjust threshold tracker to suppress immediate alerts on this card for 3 minutes
                trackedOverdueTickets[ticketNum] = currentMinutes + 3;
            }
            processedNextQueuedAlarm();
        });
    }

    function startKitchenAutoRefresh() {
        clearInterval(pollInterval);
        pollInterval = setInterval(() => {
            const searchField = document.getElementById('kitchen_ticket_search');
            const searchVal = searchField ? searchField.value.trim() : '';

            const isModalOpen = alarmModalElement && alarmModalElement.classList.contains('show');
            if (searchVal !== '' || isModalOpen) return;

            generateKitchenTickets('/kitchen-ticket/generate-kitchen-tickets', {}, true);
        }, 5000); 
    }

    // Initial load running quietly in the background on initial page initialization
    generateKitchenTickets('/kitchen-ticket/generate-kitchen-tickets', {}, true);

    // CLICK HANDLER: This is the ONLY bridge that removes the overlay and enables alerts
    if (unlockBtn) {
        unlockBtn.addEventListener('click', () => {
            if (alarmModalElement) {
                // Clear out pre-rendered "!important" styling restrictions entirely 
                alarmModalElement.style.setProperty('display', 'none', 'important'); 
                alarmModalElement.style.display = ''; 
                
                // Dynamically build the Modal element inside tracking state context here
                bootstrapModalInstance = new bootstrap.Modal(alarmModalElement);
            }

            alertAudio.play()
                .then(() => {
                    alertAudio.pause();
                    alertAudio.currentTime = 0;

                    // Unshackle the operational state flags
                    isBoardLaunched = true;
                    document.getElementById('kds-audio-lock').remove();

                    // Force an immediate fetch update to populate everything cleanly
                    generateKitchenTickets('/kitchen-ticket/generate-kitchen-tickets').then(() => {
                        startKitchenAutoRefresh();
                    });
                })
                .catch(err => {
                    console.error("Audio initialization failed:", err);
                    isBoardLaunched = true;
                    document.getElementById('kds-audio-lock').remove();
                    startKitchenAutoRefresh();
                });
        });
    }

    // Remaining template rendering utility blocks (unchanged)...
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

        const existingTabsContainer = document.querySelector('.kitchen-tabbed-display');

        if (existingTabsContainer) {
            Object.entries(grouped).forEach(([status, tickets]) => {
                const countBadge = document.querySelector(`#tab-${status} .badge`);

                if (countBadge) {
                    countBadge.textContent = tickets.length;
                    countBadge.className = 'badge ms-2 px-3 py-2 small shadow-sm';
                    if (tickets.length === 0) countBadge.classList.add('bg-secondary');
                    else if (status === 'Queued') countBadge.classList.add('bg-primary');
                    else if (status === 'Preparing') countBadge.classList.add('bg-warning', 'text-dark');
                    else if (status === 'Ready') countBadge.classList.add('bg-success');
                }

                const panelContainer = document.getElementById(`panel-${status}`);

                if (panelContainer) {
                    if (tickets.length === 0) {
                        panelContainer.innerHTML = `
                            <div class="text-center py-5 my-3 text-muted border border-dashed rounded-3 bg-light">
                                <div class="fs-1 mb-2">🍽️</div>
                                <div class="fw-medium">No active tickets inside ${status} group.</div>
                            </div>
                        `;
                    }
                    else {
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

            return existingTabsContainer.outerHTML;
        }

        const tabHeaders = Object.entries(grouped).map(([status, tickets]) => {
            const isActive = status === activeStatus ? 'active' : '';
            let badgeColor = 'bg-secondary';
            
            if (status === 'Queued' && tickets.length > 0) badgeColor = 'bg-primary';
            if (status === 'Preparing' && tickets.length > 0) badgeColor = 'bg-warning text-dark';
            if (status === 'Ready' && tickets.length > 0) badgeColor = 'bg-success';

            return `
                <li class="nav-item flex-fill text-center" role="presentation">
                    <button class="nav-link w-100 py-3 fs-5 fw-bold text-uppercase position-relative ${isActive}" id="tab-${status}" data-bs-toggle="tab" data-bs-target="#panel-${status}" type="button" role="tab" aria-controls="panel-${status}" aria-selected="${status === activeStatus ? 'true' : 'false'}">
                        ${status}
                        <span class="badge ${badgeColor} ms-2 px-2.5 py-1.5 rounded-circle small shadow-sm">${tickets.length}</span>
                    </button>
                </li>
            `;
        }).join('');

        const tabContents = Object.entries(grouped).map(([status, tickets]) => {
            const isActive = status === activeStatus ? 'show active' : '';
            
            return `
                <div class="tab-pane fade ${isActive} pt-3" id="panel-${status}" role="tabpanel" aria-labelledby="tab-${status}">
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
        
        let cardBorder = 'border-neutral-200';
        let headerBg = '';
        let timerBadge = 'text-dark fw-bold';
        let icon = '⏱️ ';

        if (isDelayed) {
            cardBorder = 'border-danger border-2';
            headerBg = 'bg-danger text-white';
            timerBadge = 'fw-bold';
            icon = '🚨 ';
        }
        else if (isWarning) {
            cardBorder = 'border-warning border-2';
            headerBg = 'bg-warning text-dark';
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
                        <span class="fs-6 px-3 py-2 ${timerBadge}">${icon}${Math.floor(minutes)}m</span>
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
        }
        else if (ticketStatus === 'Preparing' && ['Ready', 'Served'].includes(item.status)) {
            isDone = true;
        }
        else if (ticketStatus === 'Ready' && ['Served'].includes(item.status)) {
            isDone = true;
        }

        let containerClass = 'border-light text-dark';
        let qtyBadgeClass = 'bg-primary text-white';
        let textClass = 'fw-bold text-dark';
        let actionIndicator = `<span class="text-muted opacity-40 fw-bold fs-5">⚡</span>`;

        if (isDone) {
            containerClass = 'bg-secondary bg-opacity-10 border-transparent opacity-60';
            qtyBadgeClass = 'bg-secondary text-white-50';
            textClass = 'text-decoration-line-through text-muted fw-normal';
            actionIndicator = `<span class="text-success fw-bold fs-5">✓</span>`;
        }
        else if (isCancelled) {
            containerClass = 'bg-danger bg-opacity-10 border-danger border-opacity-20';
            qtyBadgeClass = 'bg-danger text-white';
            textClass = 'text-decoration-line-through text-danger';
            actionIndicator = `<span class="badge bg-danger text-uppercase tracking-wider fs-7">VOID</span>`;
        }

        return `
        <div class="d-flex justify-content-between align-items-center p-4 rounded border transition-all shadow-xs user-select-none ${containerClass}" style="cursor: pointer; min-height: 58px;" data-action="toggle-kitchen-item" data-id="${item.shop_order_item_id}" data-ticket-item-id="${item.ticket_item_id}" onmousedown="this.style.transform='scale(0.98)'" onmouseup="this.style.transform='scale(1)'" onmouseleave="this.style.transform='scale(1)'">
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

    document.addEventListener('input', (event) => {
        if (event.target.id !== 'kitchen_ticket_search') return;

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const query = event.target.value;
            generateKitchenTickets('/kitchen-ticket/generate-kitchen-tickets', { search: query }, query !== '');
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
        }
        catch (error) {
            console.error(error);
            handleSystemError(error, 'update_failed', 'Failed to update kitchen item');
        }
        finally {
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
});