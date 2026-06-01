import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton } from '../../form/button.js';
import { getPageContext, getCsrfToken, resetForm } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';
import { appendObject } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const generateKitchenRoutes = async (url, otherData = {}) => {
        try {
            const csrf = getCsrfToken();
            const ctx = getPageContext();

            const params = new URLSearchParams();

            params.append('appId', ctx.appId ?? '');
            params.append('navigationMenuId', ctx.navigationMenuId ?? '');

            appendObject(params, otherData);

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

            if (data?.success) {
                const container = document.getElementById('kitchen-route-container');

                if (!container) {
                    console.error('Kitchen route container not found.');
                    return;
                }

                container.innerHTML = '';

                const routes = data.data || [];

                if (routes.length === 0) {
                    container.innerHTML = renderEmptyKitchenRoutes();

                    return;
                }

                routes.forEach(route => {
                    container.insertAdjacentHTML('beforeend',renderKitchenRoute(route));
                });
            }

        } 
        catch (error) {
            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
        }

    };

    const renderKitchenRoute = (route) => {
        return `
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                    <div class="h-5px bg-primary"></div>

                    <div class="card-header border-0 pt-6 pb-4">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-50px me-4">
                                    <div class="symbol-label bg-light-primary">
                                        <i class="ki-outline ki-cheque fs-2x text-primary"></i>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="fw-bold mb-1">${route.kitchen_route_name}</h3>

                                    <div class="fs-7 text-muted">${route.kitchen_route_type}</div>
                                </div>
                            </div>

                            <span class="badge badge-light-success fw-bold px-4 py-2">LIVE</span>
                        </div>
                    </div>

                    <div class="px-6 mb-5">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="bg-light-warning rounded-4 px-4 py-4 text-center">
                                    <div class="text-muted fs-8 mb-1">
                                        Queued
                                    </div>

                                    <div class="fw-bold fs-2 text-warning">
                                        ${route.queued_count}
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="bg-light-primary rounded-4 px-4 py-4 text-center">
                                    <div class="text-muted fs-8 mb-1">
                                        Preparing
                                    </div>

                                    <div class="fw-bold fs-2 text-primary">
                                        ${route.preparing_count}
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="bg-light-success rounded-4 px-4 py-4 text-center">
                                    <div class="text-muted fs-8 mb-1">
                                        Ready
                                    </div>

                                    <div class="fw-bold fs-2 text-success">
                                        ${route.ready_count}
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="bg-light-dark rounded-4 px-4 py-4 text-center">
                                    <div class="text-muted fs-8 mb-1">
                                        Completed
                                    </div>

                                    <div class="fw-bold fs-2 text-dark">
                                        ${route.completed_count}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 mb-5">
                        <div class="bg-light rounded-4 p-5">
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted fs-7">Active Tickets</span>

                                <span class="fw-bold">${route.active_ticket_count}</span>
                            </div>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted fs-7">Oldest Pending</span>

                                <span class="fw-bold">${route.oldest_ticket_time ?? 'None'}</span>
                            </div>

                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted fs-7">Last Activity</span>

                                <span class="fw-bold">${route.last_activity}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body pt-0">
                        <div class="d-flex gap-3">
                            <a href="${route.link}" class="btn btn-primary fw-bold w-100 py-3 rounded-3">
                                View Kitchen Display
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;
    };

    const renderEmptyKitchenRoutes = () => {
        return `
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body text-center py-10">
                        <i class="ki-outline ki-chef fs-3x text-muted mb-4"></i>

                        <h3 class="fw-bold text-gray-800 mb-2">No Kitchen Routes Found</h3>

                        <div class="text-muted fs-6">
                            There are currently no active kitchen routes configured.
                        </div>
                    </div>
                </div>
            </div>
        `;
    };

    generateKitchenRoutes('/kitchen-ticket/generate-kitchen-routes');
});