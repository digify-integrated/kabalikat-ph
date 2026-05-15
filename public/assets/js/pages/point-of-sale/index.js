import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton } from '../../form/button.js';
import { getPageContext, getCsrfToken, resetForm } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';

document.addEventListener('DOMContentLoaded', () => {
    const appendObject = (params, object = {}) => {

        Object.entries(object).forEach(([key, value]) => {

            if (value !== undefined && value !== null) {
                params.append(key, value);
            }

        });
    };

    const generateShopRegister = async (url, otherData = {}) => {

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

                const container = document.getElementById('shop_register_container');
                container.innerHTML = '';

                const registers = data.data || [];

                // ✅ EMPTY STATE HANDLING
                if (registers.length === 0) {
                    container.innerHTML = renderEmptyShopRegister();
                    return;
                }

                // NORMAL RENDERING
                registers.forEach(register => {

                    let html = '';

                    switch (register.state) {

                        case 'INITIAL':
                            html = renderInitialRegister(register);
                            break;

                        case 'OPEN':
                            html = renderOpenRegister(register);
                            break;

                        case 'CLOSED':
                            html = renderClosedRegister(register);
                            break;
                    }

                    container.insertAdjacentHTML('beforeend', html);
                });
            }

        } catch (error) {

            handleSystemError(
                error,
                'fetch_failed',
                `Fetch request failed: ${error.message}`
            );

            throw error;
        }
    };

    const renderInitialRegister = (register) => {

        return `
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="h-5px bg-warning"></div>
                <div class="card-header border-0 pt-6 pb-3">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-warning">
                                    <i class="ki-outline ki-setting-2 fs-2x text-gray-700"></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">
                                    ${register.shop_register_name}
                                </h3>
                                <div class="text-muted fs-7 mt-1">
                                    Not initialized
                                </div>
                            </div>
                        </div>
                        <span class="badge badge-light-warning fw-semibold px-3 py-2">
                            READY
                        </span>
                    </div>
                </div>

                <div class="card-body pt-4 pb-3">
                    <div class="text-center py-6">
                        <i class="ki-outline ki-information-5 fs-3x text-gray-400 mb-4"></i>
                        <h4 class="fw-bold text-gray-800 mb-2">
                            No register session yet
                        </h4>
                        <div class="text-muted fs-7 px-4">
                            This cashier register is ready. Start the first session to begin tracking sales and cash flow.
                        </div>
                    </div>
                </div>

                <div class="px-7 pb-5">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted fs-7">State</span>
                        <span class="fw-semibold text-dark">Not started</span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted fs-7">History</span>
                        <span class="fw-semibold text-muted">Empty</span>
                    </div>
                </div>

                <div class="card-body pt-0">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#register-modal" data-shop-register-id="${register.id}" data-session="open"
                    class="btn btn-success fw-bold w-100 py-3 rounded-3 shop-register">
                        Open Register
                    </a>
                </div>
            </div>
        </div>
        `;
    };

    const renderOpenRegister = (register) => {

        return `        
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="h-5px bg-success"></div>
                <div class="card-header border-0 pt-6 pb-4">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-success">
                                    <i class="ki-outline ki-shop fs-2x text-success"></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-1">
                                    ${register.shop_register_name}
                                </h3>
                                <div class="d-flex align-items-center fs-7 text-muted">
                                    <span class="bullet bullet-dot bg-success me-2"></span>
                                    Active session
                                </div>
                            </div>
                        </div>
                        <span class="badge badge-light-success fw-bold px-4 py-2">
                            OPEN
                        </span>
                    </div>
                </div>
                <div class="px-6 mb-5">
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="bg-light-success rounded-4 px-3 py-3 text-center">
                                <div class="text-muted fs-8 mb-1">Cash</div>
                                <div class="fw-bold fs-4">
                                    ₱ ${register.open_amount}
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light-primary rounded-4 px-3 py-3 text-center">
                                <div class="text-muted fs-8 mb-1">Sales</div>
                                <div class="fw-bold fs-4">
                                    ${register.sales_count}
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light-info rounded-4 px-3 py-3 text-center">
                                <div class="text-muted fs-8 mb-1">Duration</div>
                                <div class="fw-bold fs-6">
                                    ${register.duration}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 mb-5">
                    <div class="bg-light rounded-4 p-5">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">
                                Session Timeline
                            </h5>
                            <span class="badge badge-light-success">
                                LIVE
                            </span>
                        </div>
                        <div class="timeline">
                            <div class="timeline-item align-items-center mb-7">
                                <div class="timeline-line mt-1 mb-n6"></div>
                                <div class="timeline-icon">
                                    <i class="ki-duotone ki-check-circle fs-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="timeline-content m-0">
                                    <span class="fs-7 text-gray-500 d-block">
                                        Register Opened
                                    </span>
                                    <span class="fs-6 fw-bold text-gray-800">
                                        ${register.open_time}
                                    </span>
                                </div>
                            </div>
                            <div class="timeline-item align-items-center">
                                <div class="timeline-line"></div>
                                <div class="timeline-icon">
                                    <i class="ki-duotone ki-time fs-2 text-warning">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="timeline-content m-0">
                                    <span class="fs-7 text-gray-500 d-block">
                                        Currently Active
                                    </span>
                                    <span class="fs-6 fw-bold text-gray-800">
                                        Awaiting closing
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

               <div class="card-body pt-0">
                    <div class="d-flex gap-3">
                        <a href="#"
                        data-bs-toggle="modal"
                        data-bs-target="#register-modal"
                        data-shop-register-id="${register.id}"
                        data-session="close"
                        class="btn btn-warning fw-bold w-50 py-3 rounded-3 shop-register">
                            Close Register
                        </a>

                        <a href="${register.link}"
                        class="btn btn-primary fw-bold w-50 py-3 rounded-3">
                            View Register
                        </a>
                    </div>
                </div>
            </div>
        </div>
        `;
    };

    const renderClosedRegister = (register) => {

        return `        
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="h-5px bg-danger"></div>
                <div class="card-header border-0 pt-6 pb-4">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-danger">
                                    <i class="ki-outline ki-lock-2 fs-2x text-danger"></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-1">
                                    ${register.shop_register_name}
                                </h3>
                                <div class="d-flex align-items-center fs-7 text-muted">
                                    <span class="bullet bullet-dot bg-danger me-2"></span>
                                    Closed session
                                </div>
                            </div>
                        </div>
                        <span class="badge badge-light-danger fw-bold px-4 py-2">
                            CLOSED
                        </span>
                    </div>
                </div>

                <div class="px-6 mb-5">
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="bg-light-danger rounded-4 px-3 py-3 text-center">
                                <div class="text-muted fs-8 mb-1">
                                    Closing
                                </div>
                                <div class="fw-bold fs-4">
                                    ₱ ${register.close_amount}
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light-warning rounded-4 px-3 py-3 text-center">
                                <div class="text-muted fs-8 mb-1">
                                    Sales
                                </div>
                                <div class="fw-bold fs-4">
                                    ${register.sales_count}
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light-info rounded-4 px-3 py-3 text-center">
                                <div class="text-muted fs-8 mb-1">
                                    Shift
                                </div>
                                <div class="fw-bold fs-6">
                                    ${register.duration}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 mb-5">
                    <div class="bg-light rounded-4 p-5">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">
                                Last Session
                            </h5>
                            <span class="badge badge-light-danger">
                                CLOSED
                            </span>
                        </div>
                        <div class="timeline">
                            <div class="timeline-item align-items-center mb-7">
                                <div class="timeline-line mt-1 mb-n6"></div>
                                <div class="timeline-icon">
                                    <i class="ki-duotone ki-check-circle fs-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="timeline-content m-0">
                                    <span class="fs-7 text-gray-500 d-block">
                                        Register Opened
                                    </span>
                                    <span class="fs-6 fw-bold text-gray-800">
                                        ${register.open_time}
                                    </span>
                                </div>
                            </div>
                            <div class="timeline-item align-items-center">
                                <div class="timeline-line"></div>
                                <div class="timeline-icon">
                                    <i class="ki-duotone ki-cross-circle fs-2 text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="timeline-content m-0">
                                    <span class="fs-7 text-gray-500 d-block">
                                        Register Closed
                                    </span>
                                    <span class="fs-6 fw-bold text-gray-800">
                                        ${register.close_time}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#register-modal" data-shop-register-id="${register.id}" data-session="open"
                    class="btn btn-success fw-bold w-100 py-3 rounded-3 shop-register">
                        Open Register
                    </a>
                </div>
            </div>
        </div>
        `;
    };

    const renderEmptyShopRegister = () => {

        return `
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    
                    <div class="card-body text-center py-10">

                        <i class="ki-outline ki-user fs-3x text-muted mb-4"></i>

                        <h3 class="fw-bold text-gray-800 mb-2">
                            No Shop Register Assigned
                        </h3>

                        <div class="text-muted fs-6">
                            You currently don’t have access to any shop registers.
                            Please contact an administrator to be granted access.
                        </div>
                    </div>

                </div>
            </div>
        `;
    };
    
    const setRegisterModalMode = (session) => {

        const isOpening = session === 'open';
        const isClosing = session === 'close';

        // TITLE
        document.querySelector('#register-modal .modal-title-main')?.remove();

        document.querySelector('#register-modal h2').innerText =
            isOpening ? 'Cash Count' : 'Cash Count';

        document.querySelector('#register-modal .text-muted.fs-7').innerText =
            isOpening
                ? 'Enter denominations to compute opening cash'
                : 'Enter denominations to compute closing cash';

        // BADGE
        const badge = document.querySelector('#register-modal .register-badge');

        badge.className =
            isOpening
                ? 'badge badge-light-success px-4 py-3 fs-7 fw-bold register-badge'
                : 'badge badge-light-danger px-4 py-3 fs-7 fw-bold register-badge';

        badge.innerText =
            isOpening ? 'Register Opening' : 'Register Closing';

        // TOTAL LABEL
        document.querySelector('#register-modal .total-label').innerText =
            isOpening ? 'Opening Cash Total' : 'Closing Cash Total';

        // BUTTON
        const btn = document.querySelector('#register-modal button[type="submit"]');

        btn.className =
            isOpening
                ? 'btn btn-success w-100 py-4 fw-bold rounded-3'
                : 'btn btn-danger w-100 py-4 fw-bold rounded-3';

        btn.innerText =
            isOpening ? 'Open Register' : 'Close Register';
    };

    const formatMoney = (value) => {
        return parseFloat(value || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    const computeRegisterTotal = () => {

        const inputs = document.querySelectorAll('.qty-input');

        let grandTotal = 0;

        inputs.forEach(input => {

            const qty = parseInt(input.value || 0);
            const denomination = parseFloat(input.dataset.denomination || 0);

            const subtotal = qty * denomination;

            const id = input.id.replace('open_', '');

            const subtotalEl = document.getElementById(`subtotal_${id}`);

            if (subtotalEl) {
                subtotalEl.innerText = `₱ ${formatMoney(subtotal)}`;
            }

            grandTotal += subtotal;

        });

        document.getElementById('open_total').innerText = formatMoney(grandTotal);
    };

    const config = {
        forms: [
            {
                selector: '#register_form',
                rules: {
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
        
                        disableButton('submit-data');
        
                        try {
                            const response = await fetch('/shop-register/save-session', {
                                method: 'POST',
                                body: formData
                            });
        
                            if (!response.ok) {
                                throw new Error(`Save session failed with status: ${response.status}`);
                            }
        
                            const data = await response.json();
        
                            if (data.success) {
                                showNotification(data.message, 'success');
                                $('#register-modal').modal('hide');
                                generateShopRegister('/shop-register/generate-register');
                                enableButton('submit-data');
                                resetForm('register_form');
                            }
                            else{
                                showNotification(data.message);
                                enableButton('submit-data');
                            }
                        } catch (error) {
                            enableButton('submit-data');
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        }
        
                    },
                }
            }
        ],
    }

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));

    generateShopRegister('/shop-register/generate-register');

    document.addEventListener('click', async (event) => {
        const target = event.target;

        const trigger = target.closest('.shop-register');
        if (!trigger) return;

        const shopRegisterId = trigger.dataset.shopRegisterId;
        const session = trigger.dataset.session;

        $('#shop_register_id').val(shopRegisterId);
        $('#session').val(session);

        setRegisterModalMode(session);
    });

    document.addEventListener('input', (event) => {

        if (!event.target.matches('#register_form input[type="number"]')) return;

        computeRegisterTotal();

    });

    document.addEventListener('keydown', (event) => {

        const inputs = Array.from(document.querySelectorAll('.qty-input'));

        const currentIndex = inputs.indexOf(document.activeElement);

        if (currentIndex === -1) return;

        switch (event.key) {

            case 'Enter':
            case 'ArrowDown':
                event.preventDefault();

                if (inputs[currentIndex + 1]) {
                    inputs[currentIndex + 1].focus();
                    inputs[currentIndex + 1].select();
                }

                break;

            case 'ArrowUp':
                event.preventDefault();

                if (inputs[currentIndex - 1]) {
                    inputs[currentIndex - 1].focus();
                    inputs[currentIndex - 1].select();
                }

                break;

            case 'Escape':
                document.activeElement.blur();
                break;
        }

    });
});