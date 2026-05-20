import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton } from '../../form/button.js';
import { getPageContext, getCsrfToken, resetForm } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';

document.addEventListener('DOMContentLoaded', () => {
    let searchTimeout;
    let cartInitialized = false;

    const config = {
        forms: [
            {
                selector: '#product_form',
                rules: {
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const shopOrderId = sessionStorage.getItem('shop_order_id');
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('shop_order_id', shopOrderId ?? '');
                        formData.append('shop_register_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx .navigationMenuId ?? '');

                        disableButton('submit-product');

                        try {
                            const response = await fetch('/shop-order/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save order failed with status: ${response.status}`);
                            }

                            const data = await response.json();

                            if (data.success) {
                                sessionStorage.setItem('shop_order_id', data.shop_order_id);
                                $('#shop-register-order-modal').modal('hide');
                                loadCart(data.shop_order_id, {
                                    silent: true
                                });
                            } else {
                                showNotification(data.message);
                            }
                        } catch (error) {
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        } finally {
                            enableButton('submit-product');
                        }
                    },
                }
            }
        ],
        posCategory: [
            { url: '/shop-register/generate-category' }
        ],
        posProduct: [
            { url: '/shop-register/generate-product' }
        ],
    };

    const updateModalTotal = () => {
        const qty = parseFloat(document.getElementById('order_qty_input').value || 0);
        const price = parseFloat(document.getElementById('modal-product-base-price').value || 0);

        const total = qty * price;

        document.getElementById('modal-product-price').textContent =
            '₱ ' + total.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
    }

    const appendObject = (params, object = {}) => {
        Object.entries(object).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                params.append(key, value);
            }
        });
    };

    const generatePOSCategory = async (url, otherData = {}) => {
        try {

            const csrf = getCsrfToken();
            const ctx = getPageContext();

            const params = new URLSearchParams();

            params.append('detailId', ctx.detailId ?? '');
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

                const container = document.getElementById('shop-product-category-container');
                container.innerHTML = `
                    <button
                        type="button"
                        class="btn btn-primary rounded-pill px-4 py-2 product-category-filter active"
                        data-product-filter="all">

                        All

                    </button>
                    `;

                const categories = data.data || [];

                categories.forEach(category => {

                    let html = renderCategoryTab(category);

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

    const generatePOSProduct = async (url, otherData = {}) => {
        try {

            const csrf = getCsrfToken();
            const ctx = getPageContext();

            const params = new URLSearchParams();

            params.append('detailId', ctx.detailId ?? '');
            params.append('appId', ctx.appId ?? '');
            params.append('navigationMenuId', ctx.navigationMenuId ?? '');

            appendObject(params, otherData);

            /*
            |--------------------------------------------------------------------------
            | SHOW LOADING STATE
            |--------------------------------------------------------------------------
            */

            renderProductLoading();

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

                const container = document.getElementById('product-container');
                container.innerHTML = '';

                const products = data.data || [];

                /*
                |--------------------------------------------------------------------------
                | EMPTY STATE
                |--------------------------------------------------------------------------
                */

                if (!products.length) {

                    const search =
                        otherData.search?.trim() ?? '';

                    const category =
                        otherData.category_id ?? 'all';

                    container.innerHTML = renderNoProductsFound({
                        search,
                        category,
                    });

                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | PRODUCTS
                |--------------------------------------------------------------------------
                */

                products.forEach(product => {

                    let html = renderProduct(product);

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

    const renderCategoryTab = (category) => {

        return `
            <button
                type="button"
                class="btn btn-light rounded-pill px-4 py-2 product-category-filter"
                data-product-filter="${category.id}">

                ${category.name}

            </button>
        `;
    };

    const renderProduct = (product) => {
        const disabled = !product.in_stock;

        const badgeClass = disabled
            ? 'bg-danger-subtle text-danger'
            : 'bg-success-subtle text-success';

        const badgeText = disabled
            ? 'Out of Stock'
            : 'Available';

        const icon = disabled
            ? 'ki-cross-circle text-danger'
            : 'ki-basket text-muted';

        const modalAttrs = !disabled
            ? `data-bs-toggle="modal"
            data-bs-target="#shop-register-order-modal"
            data-product-id="${product.id}"
            data-product-name="${product.product_name}"
            data-price="${product.base_price}"`
            : '';

        return `
        <div class="col-6 col-md-4">

            <div 
                class="card border-0 shadow-sm h-100 product-card ${
                    disabled ? 'product-card-disabled opacity-60' : 'cursor-pointer'
                }"

                ${modalAttrs}

                style="
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                    ${disabled ? 'pointer-events: none;' : ''}
                "

                ${!disabled ? `
                    onmouseover="this.style.transform='translateY(-4px)';
                        this.classList.remove('shadow-sm');
                        this.classList.add('shadow');"

                    onmouseout="this.style.transform='translateY(0)';
                        this.classList.remove('shadow');
                        this.classList.add('shadow-sm');"
                ` : ''}
            >

                <div class="card-body d-flex flex-column justify-content-between p-5">

                    <!-- TOP -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="badge rounded-pill ${badgeClass} px-3 py-2 fw-semibold fs-8">
                            ${badgeText}
                        </span>

                        <i class="ki-duotone ${icon} fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>

                    <!-- MIDDLE -->
                    <div class="mb-4 flex-grow-1">
                        <div class="text-muted fs-8 mb-1 text-uppercase">
                            ${product.category_name}
                        </div>

                        <h5 class="fw-bold fs-2 ${
                            disabled ? 'text-muted' : 'text-gray-900'
                        } mb-2 lh-base">
                            ${product.product_name}
                        </h5>

                        ${
                            disabled && product.stock_status
                            ? `
                            <div class="text-danger fs-8 d-inline-block px-2 py-1 rounded-sm mt-1">
                                <i class="ki-duotone ki-information fs-7 me-1 text-danger"></i>
                                ${product.stock_status}
                            </div>
                            `
                            : ''
                        }
                    </div>

                    <!-- BOTTOM -->
                    <div class="d-flex align-items-end justify-content-between pt-3 border-top border-gray-100">
                        <div>
                            <div class="fs-8 text-muted text-uppercase mb-1">
                                Price
                            </div>
                            <div class="fw-bolder fs-1 ${
                                disabled ? 'text-muted' : 'text-primary'
                            }">
                                ₱ ${Number(product.price).toLocaleString(undefined, {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                })}
                            </div>
                        </div>

                        <div class="btn btn-icon btn-sm ${
                            disabled ? 'btn-light' : 'btn-light-primary'
                        } rounded-circle">
                            <i class="ki-duotone ${
                                disabled ? 'ki-information' : 'ki-arrow-right'
                            } fs-3"></i>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        `;
    };

    const renderProductLoading = () => {

        const container = document.getElementById('product-container');

        container.innerHTML = `
        <div class="col-12">

            <div class="card border-0 shadow-sm">

                <div class="card-body py-15 text-center">

                    <div class="spinner-border text-primary mb-5"
                        style="width: 3rem; height: 3rem;">
                    </div>

                    <div class="fw-bold fs-4 text-gray-800 mb-2">
                        Loading products...
                    </div>

                    <div class="text-muted">
                        Please wait while products are being prepared
                    </div>

                </div>

            </div>

        </div>
        `;
    };

    const renderNoProductsFound = ({
        search = '',
        category = 'all',
    }) => {

        let title = 'No products found';
        let description = '';

        /*
        |--------------------------------------------------------------------------
        | SEARCH + CATEGORY
        |--------------------------------------------------------------------------
        */

        if (search && category !== 'all') {

            description = `
                No products matched
                "<strong>${search}</strong>"
                under this category.
            `;
        }

        /*
        |--------------------------------------------------------------------------
        | SEARCH ONLY
        |--------------------------------------------------------------------------
        */

        else if (search) {

            description = `
                No products matched
                "<strong>${search}</strong>".
            `;
        }

        /*
        |--------------------------------------------------------------------------
        | CATEGORY ONLY
        |--------------------------------------------------------------------------
        */

        else if (category !== 'all') {

            description = `
                No products available under this category.
            `;
        }

        /*
        |--------------------------------------------------------------------------
        | DEFAULT
        |--------------------------------------------------------------------------
        */

        else {

            description = `
                No POS products are currently available.
            `;
        }

        return `
        <div class="col-12">

            <div class="card border-0 shadow-sm">

                <div class="card-body py-15 text-center">

                    <div class="mb-5">

                        <i class="ki-duotone ki-file-deleted fs-5tx text-gray-300">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>

                    </div>

                    <div class="fw-bold fs-2 text-gray-800 mb-3">
                        ${title}
                    </div>

                    <div class="text-muted fs-6 mb-6">
                        ${description}
                    </div>

                    <button
                        type="button"
                        class="btn btn-light-primary reset-product-filter">

                        Reset Filters

                    </button>

                </div>

            </div>

        </div>
        `;
    };

    const initializeCart = async () => {

        const shopOrderId = sessionStorage.getItem('shop_order_id');

        /*
        |--------------------------------------------------------------------------
        | NO ACTIVE ORDER
        |--------------------------------------------------------------------------
        */

        if (!shopOrderId) {

            resetCartUI();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | LOAD EXISTING CART
        |--------------------------------------------------------------------------
        */

        await loadCart(shopOrderId);
    };

    const loadCart = async (
        shopOrderId,
        options = {}
    ) => {

        const {
            silent = false
        } = options;

        try {

            /*
            |--------------------------------------------------------------------------
            | LOADING
            |--------------------------------------------------------------------------
            */

            if (!silent) {

                showCartLoading();
            }

            const ctx = getPageContext();

            const csrf = getCsrfToken();

            const formData = new URLSearchParams();

            formData.append(
                'shop_order_id',
                shopOrderId
            );

            formData.append(
                'appId',
                ctx.appId ?? ''
            );

            formData.append(
                'navigationMenuId',
                ctx.navigationMenuId ?? ''
            );

            const response = await fetch(
                '/shop-order/fetch-details',
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Content-Type':
                            'application/x-www-form-urlencoded; charset=UTF-8',

                        Accept: 'application/json',

                        ...(csrf
                            ? { 'X-CSRF-TOKEN': csrf }
                            : {}),
                    },
                }
            );

            if (!response.ok) {

                throw new Error(
                    `Load cart failed: ${response.status}`
                );
            }

            const data = await response.json();

            /*
            |--------------------------------------------------------------------------
            | INVALID
            |--------------------------------------------------------------------------
            */

            if (!data.success) {

                sessionStorage.removeItem(
                    'shop_order_id'
                );

                resetCartUI();

                showNotification(data.message);

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | EMPTY
            |--------------------------------------------------------------------------
            */

            if (
                !data.order ||
                !data.order.items ||
                data.order.items.length === 0
            ) {

                resetCartUI();

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | POPULATE
            |--------------------------------------------------------------------------
            */

            populateCart(data.order);

        } catch (error) {

            handleSystemError(
                error,
                'load_cart_failed',
                error.message
            );
        }
    };

    const showCartLoading = () => {

        /*
        |--------------------------------------------------------------------------
        | LOADING
        |--------------------------------------------------------------------------
        */

        $('#shop-order-loading')
            .removeClass('d-none');

        /*
        |--------------------------------------------------------------------------
        | HIDE EMPTY
        |--------------------------------------------------------------------------
        */

        $('#shop-order-empty')
            .addClass('d-none');

        /*
        |--------------------------------------------------------------------------
        | KEEP CURRENT CONTENT
        |--------------------------------------------------------------------------
        |
        | DO NOT HIDE:
        | - register-action
        | - order summary
        | - existing cart
        |
        | Prevents annoying flicker
        |
        */
    };

    const resetCartUI = () => {

        /*
        |--------------------------------------------------------------------------
        | RESET HEADER
        |--------------------------------------------------------------------------
        */

        $('#order-id').text('--');

        /*
        |--------------------------------------------------------------------------
        | CLEAR ITEMS
        |--------------------------------------------------------------------------
        */

        $('#shop-order-list')
            .html('')
            .addClass('d-none');

        /*
        |--------------------------------------------------------------------------
        | CLEAR SUMMARY
        |--------------------------------------------------------------------------
        */

        $('#order-summary-list').html('');

        $('#shop-order-summary-card')
            .addClass('d-none');

        /*
        |--------------------------------------------------------------------------
        | STATES
        |--------------------------------------------------------------------------
        */

        $('#shop-order-loading')
            .addClass('d-none');

        $('#shop-order-empty')
            .removeClass('d-none');

        /*
        |--------------------------------------------------------------------------
        | HIDE ACTIONS
        |--------------------------------------------------------------------------
        */

        toggleRegisterAction(false);

        /*
        |--------------------------------------------------------------------------
        | RESET STATE
        |--------------------------------------------------------------------------
        */

        cartInitialized = false;
    };

    const populateCart = (order) => {

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        $('#order-id').text(
            order.order_number ?? '--'
        );

        $('#order-type').val(
            order.order_type ?? 'Walk-in'
        );

        if(order.order_type === 'Dine-in'){
            $('#set-table-column').removeClass('d-none');
        }
        else{
            $('#set-table-column').addClass('d-none');
        }

        /*
        |--------------------------------------------------------------------------
        | BADGES
        |--------------------------------------------------------------------------
        */

        $('#badge-table').text(
            order.table_number
                ? `Table ${order.table_number}`
                : 'No Table'
        );

        $('#badge-payment-status').text(
            order.payment_status ?? 'Unpaid'
        );

        /*
        |--------------------------------------------------------------------------
        | HIDE STATES
        |--------------------------------------------------------------------------
        */

        $('#shop-order-loading').addClass('d-none');

        $('#shop-order-empty').addClass('d-none');

        /*
        |--------------------------------------------------------------------------
        | SHOW LIST
        |--------------------------------------------------------------------------
        */

        $('#shop-order-list')
            .removeClass('d-none');

        $('#shop-order-summary-card')
            .removeClass('d-none');

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        renderOrderSummary(order);

        /*
        |--------------------------------------------------------------------------
        | ITEMS
        |--------------------------------------------------------------------------
        */

        const html = (order.items ?? [])
            .map(renderOrderItem)
            .join('');

        $('#shop-order-list').html(html);

        /*
        |--------------------------------------------------------------------------
        | REGISTER ACTIONS
        |--------------------------------------------------------------------------
        |
        | ONLY INITIALIZE ONCE
        |
        */

        if (!cartInitialized) {

            toggleRegisterAction(true);

            cartInitialized = true;
        }

        /*
        |--------------------------------------------------------------------------
        | KT COMPONENTS
        |--------------------------------------------------------------------------
        */

        if (typeof KTComponents !== 'undefined') {

            KTComponents.init();
        }
    };

    const toggleRegisterAction = (show = false) => {
        if (show) {

            $('.register-action')
                .removeClass('d-none');
        }

        else {

            $('.register-action')
                .addClass('d-none');
        }
    };

    const formatPeso = (value = 0) => {

        return `₱ ${Number(value).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })}`;
    };

    const renderOrderItem = (item) => {

        return `

        <div class="card border-0 shadow-sm mb-3 order-item-card rounded-3">

            <div class="card-body p-4">

                <!-- HEADER -->
                <div class="d-flex justify-content-between align-items-start mb-3">

                    <!-- PRODUCT INFO -->
                    <div class="flex-grow-1 pe-3">

                        <div class="fw-bold fs-5 text-gray-900 mb-1 text-truncate">

                            ${item.product_name}

                        </div>

                        <div class="d-flex align-items-center gap-2 flex-wrap">

                            <span class="badge badge-light-dark fw-semibold">

                                ${formatPeso(item.unit_price)} / item

                            </span>

                            <span class="text-muted fs-8">

                                Qty <span class="fw-bold text-gray-800">
                                    ${item.quantity}
                                </span>

                            </span>

                        </div>

                    </div>

                    <!-- PRICE -->
                    <div class="text-end">

                        <div class="fw-bolder fs-2 text-primary lh-1">

                            ${formatPeso(item.line_total)}

                        </div>

                        <div class="text-muted fs-8 mt-1">
                            Total
                        </div>

                    </div>

                </div>

                <!-- NOTE -->
                ${
                    item.order_note
                        ? `
                        <div class="d-flex align-items-start gap-2 bg-light-warning border border-warning border-dashed rounded-3 p-3 mb-4">

                            <i class="ki-outline ki-notepad fs-4 text-warning mt-1"></i>

                            <div class="fs-7 fw-semibold text-gray-700">

                                ${item.order_note}

                            </div>

                        </div>
                    `
                        : ''
                }

                <!-- FOOTER -->
                <div class="d-flex justify-content-between align-items-center">

                    <!-- QUANTITY CONTROL (PILL STYLE) -->
                    <div
                        class="d-flex align-items-center bg-light rounded-pill px-2 py-1 gap-2 shadow-sm"
                        data-kt-dialer="true"
                        data-kt-dialer-min="1"
                        data-kt-dialer-step="1">

                        <button
                            type="button"
                            class="btn btn-icon btn-sm btn-light-primary rounded-circle"
                            data-kt-dialer-control="decrease">

                            <i class="ki-outline ki-minus fs-5"></i>

                        </button>

                        <input
                            type="text"
                            class="form-control form-control-flush fw-bold text-center bg-transparent border-0 text-gray-900 w-40px px-0"
                            value="${item.quantity}"
                            readonly>

                        <button
                            type="button"
                            class="btn btn-icon btn-sm btn-light-primary rounded-circle"
                            data-kt-dialer-control="increase">

                            <i class="ki-outline ki-plus fs-5"></i>

                        </button>

                    </div>

                    <!-- ACTIONS -->
                    <div class="d-flex align-items-center gap-2">

                        <!-- DELETE (SAFER STYLE) -->
                        <button
                            type="button"
                            class="btn btn-icon btn-sm btn-light-danger"
                            title="Remove item">

                            <i class="ki-outline ki-trash fs-4"></i>

                        </button>

                    </div>

                </div>

            </div>

        </div>
        `;
    };

    const renderOrderSummary = (order) => {

        let html = '';

        /*
        |--------------------------------------------------------------------------
        | SUBTOTAL
        |--------------------------------------------------------------------------
        */

        html += `
            <div class="d-flex justify-content-between mb-3">
                <span class="text-muted">
                    Subtotal
                </span>

                <span class="fw-semibold">
                    ${formatPeso(order.subtotal)}
                </span>
            </div>
        `;

        /*
        |--------------------------------------------------------------------------
        | VATABLE SALES
        |--------------------------------------------------------------------------
        */

        if (Number(order.vatable_sales) > 0) {

            html += `
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">
                        VATable Sales
                    </span>

                    <span class="fw-semibold">
                        ${formatPeso(order.vatable_sales)}
                    </span>
                </div>
            `;
        }

        /*
        |--------------------------------------------------------------------------
        | VAT AMOUNT
        |--------------------------------------------------------------------------
        */

        if (Number(order.vat_amount) > 0) {

            html += `
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">
                        VAT (12%)
                    </span>

                    <span class="fw-semibold">
                        ${formatPeso(order.vat_amount)}
                    </span>
                </div>
            `;
        }

        /*
        |--------------------------------------------------------------------------
        | DISCOUNTS
        |--------------------------------------------------------------------------
        */

        if (
            order.applied_discounts &&
            order.applied_discounts.length > 0
        ) {

            order.applied_discounts.forEach(discount => {

                html += `
                    <div class="d-flex justify-content-between mb-3">

                        <span class="text-warning">
                            ${discount.discount_type_name}
                        </span>

                        <span class="fw-semibold text-warning">
                            - ${formatPeso(discount.discount_amount)}
                        </span>

                    </div>
                `;
            });
        }

        /*
        |--------------------------------------------------------------------------
        | CHARGES
        |--------------------------------------------------------------------------
        */

        if (
            order.applied_charges &&
            order.applied_charges.length > 0
        ) {

            order.applied_charges.forEach(charge => {

                html += `
                    <div class="d-flex justify-content-between mb-3">

                        <span class="text-danger">
                            ${charge.charge_type_name}
                        </span>

                        <span class="fw-semibold text-danger">
                            + ${formatPeso(charge.charge_amount)}
                        </span>

                    </div>
                `;
            });
        }

        /*
        |--------------------------------------------------------------------------
        | SEPARATOR
        |--------------------------------------------------------------------------
        */

        html += `
            <div class="separator separator-dashed my-4"></div>
        `;

        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        html += `
            <div class="d-flex justify-content-between align-items-center">

                <span class="fw-bold fs-3">
                    Total
                </span>

                <span class="fw-bolder fs-1">
                    ${formatPeso(order.net_total)}
                </span>

            </div>
        `;

        $('#order-summary-list').html(html);
    };

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));
    config.posCategory.map((cfg) => generatePOSCategory(cfg.url));
    config.posProduct.map((cfg) => generatePOSProduct(cfg.url));

    initializeCart();

    $('#order-type').on('change', async function () {
        try {
            const orderType = $(this).val();
            const csrf = getCsrfToken();
            const ctx = getPageContext();
            const shopOrderId = sessionStorage.getItem('shop_order_id');
            
            const formData = new URLSearchParams();
            formData.append('order_type', orderType);
            formData.append('shop_order_id', shopOrderId ?? '');
            formData.append('shop_register_id', ctx.detailId ?? '');
            formData.append('appId', ctx.appId ?? '');
            formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
            const response = await fetch('/shop-order/save-order-type', {
                method: 'POST',
                body: formData,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    Accept: 'application/json',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                },
            });
            
            if (!response.ok) throw new Error(`Request failed with status: ${response.status}`);
            
            const data = await response.json();
            
            if (data.success) {
                if(orderType === 'Dine-in'){
                    $('#set-table-column').removeClass('d-none');
                }
                else{
                    $('#set-table-column').addClass('d-none');
                }
            }
            else {
                showNotification(data.message);
            }
        } catch (error) {
            handleSystemError(error, 'fetch_failed', `Failed to settings: ${error.message}`);
        }
    });

    document.addEventListener('input', (event) => {

        if (event.target.id !== 'product_search') {
            return;
        }

        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {

            const activeCategory =
                document.querySelector('.product-category-filter.active');

            const categoryId =
                activeCategory?.dataset.productFilter ?? 'all';

            generatePOSProduct(
                '/shop-register/generate-product',
                {
                    category_id: categoryId,
                    search: event.target.value,
                }
            );

        }, 100);
    });

    document.addEventListener('click', (event) => {

        const button = event.target.closest('.product-category-filter');

        if (!button) {
            return;
        }

        document
            .querySelectorAll('.product-category-filter')
            .forEach(btn => {

                btn.classList.remove('active');
                btn.classList.remove('btn-primary');

                btn.classList.add('btn-light');
            });

        button.classList.add('active');
        button.classList.add('btn-primary');

        button.classList.remove('btn-light');

        generatePOSProduct(
            '/shop-register/generate-product',
            {
                category_id: button.dataset.productFilter,
                search: document.getElementById('product_search').value,
            }
        );
    });

    document.addEventListener('click', (event) => {
        const resetButton =
            event.target.closest('.reset-product-filter');

        if (!resetButton) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | RESET SEARCH
        |--------------------------------------------------------------------------
        */

        document.getElementById('product_search').value = '';

        /*
        |--------------------------------------------------------------------------
        | RESET CATEGORY
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll('.product-category-filter')
            .forEach(btn => {

                btn.classList.remove('active');
                btn.classList.remove('btn-primary');

                btn.classList.add('btn-light');
            });

        const allButton =
            document.querySelector(
                '.product-category-filter[data-product-filter="all"]'
            );

        if (allButton) {

            allButton.classList.add('active');
            allButton.classList.add('btn-primary');

            allButton.classList.remove('btn-light');
        }

        /*
        |--------------------------------------------------------------------------
        | RELOAD PRODUCTS
        |--------------------------------------------------------------------------
        */

        generatePOSProduct(
            '/shop-register/generate-product',
            {
                category_id: 'all',
                search: '',
            }
        );
    });

    document.addEventListener('click', function (e) {
        const card = e.target.closest('.product-card[data-bs-toggle="modal"]');

        if (!card) return;

        const productId = card.dataset.productId;
        const productName = card.dataset.productName;
        const price = parseFloat(card.dataset.price || 0);

        // set modal values
        document.getElementById('modal_product_id').value = productId;
        document.getElementById('modal-product-name').textContent = productName;
        document.getElementById('modal-product-base-price').value = price;

        // reset quantity
        const qtyInput = document.getElementById('order_qty_input');
        qtyInput.value = 1;

        // compute initial total
        updateModalTotal();
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-kt-dialer-control="increase"], [data-kt-dialer-control="decrease"]')) {

            setTimeout(updateModalTotal, 10);
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('#new-order')) {

            sessionStorage.removeItem('shop_order_id');
            resetCartUI();
        }
    });
});