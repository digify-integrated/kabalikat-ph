import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton } from '../../form/button.js';
import { getPageContext, getCsrfToken, resetForm } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';

document.addEventListener('DOMContentLoaded', () => {
    let searchTimeout;
    let cartInitialized = false;
    let selectedFloorPlanId = null;
    let selectedTableId = null;
    
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

    const loadFloorPlans = async (shopOrderId) => {

        try {

            const ctx = getPageContext();

            const csrf = getCsrfToken();

            const formData = new URLSearchParams();

            formData.append('shop_order_id', shopOrderId);

            formData.append('shop_register_id', ctx.detailId ?? '');

            const response = await fetch(
                '/shop-order/fetch-floor-plans',
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        Accept: 'application/json',
                        ...(csrf
                            ? { 'X-CSRF-TOKEN': csrf }
                            : {}),
                    },
                }
            );

            if (!response.ok) {

                throw new Error(
                    `Failed to load floor plans: ${response.status}`
                );
            }

            const data = await response.json();

            if (!data.success) {

                showNotification(data.message);

                return;
            }

            renderFloorPlans(data.floorPlans);

            /*
            |--------------------------------------------------------------------------
            | AUTO SELECT FIRST
            |--------------------------------------------------------------------------
            */

            if (data.floorPlans.length > 0) {

                selectedFloorPlanId =
                    data.floorPlans[0].id;

                await loadFloorTables(
                    selectedFloorPlanId,
                    shopOrderId
                );
            }

        } catch (error) {

            handleSystemError(
                error,
                'load_floor_plans_failed',
                error.message
            );
        }
    };

    const loadFloorTables = async (
        floorPlanId,
        shopOrderId
    ) => {

        try {

            $('#shop-floor-table-container').html(`
            
                <div class="col-12 text-center py-15">

                    <div class="spinner-border text-success mb-3"></div>

                    <div class="fw-semibold text-muted">
                        Loading tables...
                    </div>

                </div>
            `);

            const csrf = getCsrfToken();

            const formData = new URLSearchParams();

            formData.append('floor_plan_id', floorPlanId);

            formData.append('shop_order_id', shopOrderId);

            const response = await fetch(
                '/shop-order/fetch-floor-tables',
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        Accept: 'application/json',
                        ...(csrf
                            ? { 'X-CSRF-TOKEN': csrf }
                            : {}),
                    },
                }
            );

            if (!response.ok) {

                throw new Error(
                    `Failed to load tables: ${response.status}`
                );
            }

            const data = await response.json();

            if (!data.success) {

                showNotification(data.message);

                return;
            }

            renderFloorTables(data.tables);

        } catch (error) {

            handleSystemError(
                error,
                'load_floor_tables_failed',
                error.message
            );
        }
    };

    const assignTableToOrder = async (
        floorPlanTableId
    ) => {

        try {

            const shopOrderId =
                sessionStorage.getItem('shop_order_id');

            const csrf = getCsrfToken();

            const formData = new URLSearchParams();

            formData.append(
                'shop_order_id',
                shopOrderId
            );

            formData.append(
                'floor_plan_table_id',
                floorPlanTableId
            );

            const response = await fetch(
                '/shop-order/save-table',
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        Accept: 'application/json',
                        ...(csrf
                            ? { 'X-CSRF-TOKEN': csrf }
                            : {}),
                    },
                }
            );

            if (!response.ok) {

                throw new Error(
                    `Update table failed: ${response.status}`
                );
            }

            const data = await response.json();

            if (!data.success) {

                showNotification(data.message);

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE BADGE
            |--------------------------------------------------------------------------
            */

            $('#badge-table').text(
                `${data.floor_plan_name} • Table ${data.table_number}`
            );

            /*
            |--------------------------------------------------------------------------
            | RELOAD TABLES ONLY
            |--------------------------------------------------------------------------
            */

            await loadFloorTables(
                selectedFloorPlanId,
                shopOrderId
            );

        } catch (error) {

            handleSystemError(
                error,
                'assign_table_failed',
                error.message
            );
        }
    };

    const updateOrderItemQuantity = async ({
        shopOrderItemId,
        action,
    }) => {

        try {

            const csrf = getCsrfToken();

            const shopOrderId =
                sessionStorage.getItem('shop_order_id');

            const formData = new URLSearchParams();

            formData.append(
                'shop_order_item_id',
                shopOrderItemId
            );

            formData.append(
                'shop_order_id',
                shopOrderId
            );

            formData.append(
                'action',
                action
            );

            const response = await fetch(
                '/shop-order/save-item-quantity',
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        Accept: 'application/json',
                        ...(csrf
                            ? { 'X-CSRF-TOKEN': csrf }
                            : {}),
                    },
                }
            );

            if (!response.ok) {

                throw new Error(
                    `Update failed: ${response.status}`
                );
            }

            const data = await response.json();

            if (!data.success) {

                showNotification(data.message);

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | EMPTY ORDER
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
            | REFRESH ONLY CART
            |--------------------------------------------------------------------------
            */

            refreshCartContent(data.order);

        } catch (error) {

            handleSystemError(
                error,
                'update_order_item_failed',
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

    const refreshCartContent = (order) => {

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        $('#order-id').text(
            order.order_number ?? '--'
        );

        /*
        |--------------------------------------------------------------------------
        | BADGES
        |--------------------------------------------------------------------------
        */

        $('#badge-order-type').text(
            order.order_type ?? 'Walk-in'
        );

        $('#badge-payment-status').text(
            order.payment_status ?? 'Unpaid'
        );

        $('#badge-table').text(
            order.table_number
                ? `${order.floor_plan_name} • Table ${order.table_number}`
                : 'No Table'
        );

        /*
        |--------------------------------------------------------------------------
        | ITEMS
        |--------------------------------------------------------------------------
        */

        const itemsHtml = order.items
            .map(renderOrderItem)
            .join('');

        $('#shop-order-list').html(itemsHtml);

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        renderOrderSummary(order);

        /*
        |--------------------------------------------------------------------------
        | REINIT COMPONENTS
        |--------------------------------------------------------------------------
        */

        if (typeof KTComponents !== 'undefined') {

            KTComponents.init();
        }
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
                ? `${order.floor_plan_name} • Table ${order.table_number}`
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

                   <!-- QUANTITY CONTROL -->
                    <div
                        class="
                            d-flex
                            align-items-center
                            bg-light
                            rounded-pill
                            px-2
                            py-1
                            gap-2
                            shadow-sm
                        ">

                        <!-- DECREASE -->
                        <button
                            type="button"
                            class="
                                btn
                                btn-icon
                                btn-sm
                                btn-light-primary
                                rounded-circle
                                decrease-item-qty
                            "
                            data-shop-order-item-id="${item.id}">

                            <i class="ki-outline ki-minus fs-5"></i>

                        </button>

                        <!-- QUANTITY -->
                        <input
                            type="text"
                            class="
                                form-control
                                form-control-flush
                                fw-bold
                                text-center
                                bg-transparent
                                border-0
                                text-gray-900
                                w-40px
                                px-0
                            "
                            value="${item.quantity}"
                            readonly>

                        <!-- INCREASE -->
                        <button
                            type="button"
                            class="
                                btn
                                btn-icon
                                btn-sm
                                btn-light-primary
                                rounded-circle
                                increase-item-qty
                            "
                            data-shop-order-item-id="${item.id}">

                            <i class="ki-outline ki-plus fs-5"></i>

                        </button>

                    </div>

                    <!-- DELETE -->
                    <button
                        type="button"
                        class="
                            btn
                            btn-icon
                            btn-sm
                            btn-light-danger
                            delete-order-item
                        "
                        data-shop-order-item-id="${item.id}"
                        title="Remove item">

                        <i class="ki-outline ki-trash fs-4"></i>

                    </button>

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
            <div class="d-flex justify-content-between align-items-center mb-3">

                <span class="fw-semibold text-gray-700">
                    Subtotal
                </span>

                <span class="fw-bold text-gray-900">
                    ${formatPeso(order.subtotal ?? 0)}
                </span>

            </div>
        `;

        /*
        |--------------------------------------------------------------------------
        | VATABLE SALES
        |--------------------------------------------------------------------------
        */

        if ((order.vatable_sales ?? 0) > 0) {

            html += `
                <div class="d-flex justify-content-between align-items-center mb-3">

                    <span class="fw-semibold text-gray-700">
                        VATable Sales
                    </span>

                    <span class="fw-semibold text-gray-900">
                        ${formatPeso(order.vatable_sales ?? 0)}
                    </span>

                </div>
            `;
        }

        /*
        |--------------------------------------------------------------------------
        | VAT AMOUNT
        |--------------------------------------------------------------------------
        */

        if ((order.vat_amount ?? 0) > 0) {

            html += `
                <div class="d-flex justify-content-between align-items-center mb-3">

                    <span class="fw-semibold text-gray-700">
                        VAT (12%)
                    </span>

                    <span class="fw-semibold text-gray-900">
                        ${formatPeso(order.vat_amount ?? 0)}
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
            Array.isArray(order.discounts)
            &&
            order.discounts.length > 0
        ) {

            order.discounts.forEach((discount) => {

                html += `
                    <div class="d-flex justify-content-between align-items-center mb-2">

                        <span class="fw-semibold text-danger">

                            ${discount.discount_type_name}

                        </span>

                        <span class="fw-bold text-danger">

                            - ${formatPeso(discount.discount_amount ?? 0)}

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
            Array.isArray(order.charges)
            &&
            order.charges.length > 0
        ) {

            order.charges.forEach((charge) => {

                html += `
                    <div class="d-flex justify-content-between align-items-center mb-2">

                        <span class="fw-semibold text-primary">

                            ${charge.charge_type_name}

                        </span>

                        <span class="fw-bold text-primary">

                            + ${formatPeso(charge.charge_amount ?? 0)}

                        </span>

                    </div>
                `;
            });
        }

        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        html += `
            <div class="separator separator-dashed my-4"></div>

            <div class="d-flex justify-content-between align-items-center">

                <span class="fw-bolder fs-3 text-gray-900">
                    Total
                </span>

                <span class="fw-bolder fs-2 text-success">

                    ${formatPeso(order.net_total ?? 0)}

                </span>

            </div>
        `;

        $('#order-summary-list').html(html);
    };

    const renderFloorPlans = (floorPlans) => {

        const html = floorPlans.map((plan, index) => {

            return `
            
            <button
                type="button"
                class="
                    btn
                    floor-plan-filter
                    rounded-pill
                    px-6
                    py-3
                    fw-bold
                    fs-6
                    ${
                        index === 0
                        ? 'btn-success'
                        : 'btn-light'
                    }
                "
                data-floor-plan-id="${plan.id}">

                ${plan.floor_plan_name}

            </button>
            `;
        }).join('');

        $('#shop-floor-plan-container').html(html);
    };

    const renderFloorTables = (tables) => {

        const html = tables.map((table) => {

            const isSelected =
                table.is_selected;

            const isOccupied =
                table.is_occupied;

            let cardClass =
                'border-gray-200 border-hover-primary';

            let badgeClass =
                'badge-light-primary';

            let badgeText =
                'Available';

            if (isSelected) {

                cardClass =
                    'border-success bg-success bg-opacity-10';

                badgeClass =
                    'badge-success';

                badgeText =
                    'Selected';
            }

            else if (isOccupied) {

                cardClass =
                    'bg-light opacity-75 border-gray-300';

                badgeClass =
                    'badge-light-danger';

                badgeText =
                    'Occupied';
            }

            return `
            
            <div class="col-6 col-md-4 col-xl-3">

                <div
                    class="
                        table-card
                        card
                        shadow-sm
                        rounded-4
                        h-100
                        ${cardClass}
                        ${
                            !isOccupied || isSelected
                            ? 'cursor-pointer selectable-table'
                            : ''
                        }
                    "
                    data-floor-plan-table-id="${table.id}">

                    <div class="card-body p-5">

                        <div
                            class="d-flex justify-content-between align-items-start mb-5">

                            <div>

                                <div class="
                                    fw-bold
                                    fs-2
                                    ${
                                        isSelected
                                        ? 'text-success'
                                        : 'text-gray-900'
                                    }
                                    mb-1
                                ">

                                    Table ${table.table_number}

                                </div>

                                <div class="text-muted fw-semibold fs-7">

                                    ${table.seats} Seats

                                </div>

                            </div>

                            <span class="badge ${badgeClass} fw-bold">

                                ${badgeText}

                            </span>

                        </div>

                        <div
                            class="d-flex justify-content-between align-items-center">

                            <div class="d-flex gap-1">

                                ${Array(table.seats)
                                    .fill('')
                                    .map(() => `
                                    
                                        <i class="
                                            ki-outline
                                            ki-profile-user
                                            fs-4
                                            ${
                                                isSelected
                                                ? 'text-success'
                                                : 'text-muted'
                                            }
                                        "></i>
                                    `)
                                    .join('')}

                            </div>

                            ${
                                isSelected
                                ? `
                                    <i class="
                                        ki-duotone
                                        ki-check-circle
                                        fs-1
                                        text-success
                                    ">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                `
                                : `
                                    <i class="
                                        ki-outline
                                        ki-arrow-right
                                        fs-2
                                        text-muted
                                    "></i>
                                `
                            }

                        </div>

                    </div>

                </div>

            </div>
            `;
        }).join('');

        $('#shop-floor-table-container').html(html);
    };

    const renderAvailableDiscounts = (discounts) => {

        if (!discounts.length) {
            $('#available-discount-list').html(`
                <div class="text-muted text-center py-10">
                    No available discounts
                </div>
            `);
            return;
        }

        const html = discounts.map(discount => {

            const isVariable = discount.is_variable === 'Yes';

            return `
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-4">

                    <!-- HEADER -->
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="fw-bold fs-5 text-gray-900">
                            ${discount.discount_type_name}
                        </div>

                        <div class="text-end fs-7 text-muted">
                            ${
                                isVariable
                                    ? `<span class="badge bg-light text-dark">Variable</span>`
                                    : (
                                        discount.value_type === 'Percentage'
                                            ? `<span class="text-primary fw-semibold">${discount.discount_value}%</span>`
                                            : `<span class="text-primary fw-semibold">${formatPeso(discount.discount_value)}</span>`
                                    )
                            }
                        </div>
                    </div>

                    <!-- VARIABLE INPUT -->
                    ${
                        isVariable
                            ? `
                            <div class="mb-3">
                                <label class="form-label fs-8 text-muted mb-1">Value</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control form-control-sm variable-discount-value"
                                    placeholder="Enter amount">
                            </div>
                            `
                            : ''
                    }

                    <!-- REFERENCE DETAILS -->
                    <div class="row g-2 mb-2">

                        <div class="col-6">
                            <label class="form-label fs-8 text-muted mb-1">Reference Name</label>
                            <input
                                type="text"
                                class="form-control form-control-sm discount-reference-name"
                                placeholder="e.g. Senior Citizen">
                        </div>

                        <div class="col-6">
                            <label class="form-label fs-8 text-muted mb-1">Reference No.</label>
                            <input
                                type="text"
                                class="form-control form-control-sm discount-reference-number"
                                placeholder="ID / Number">
                        </div>

                    </div>

                    <!-- REMARKS -->
                    <div class="mb-3">
                        <label class="form-label fs-8 text-muted mb-1">Remarks</label>
                        <textarea
                            class="form-control form-control-sm discount-remarks"
                            rows="2"
                            placeholder="Optional notes"></textarea>
                    </div>

                    <!-- ACTION -->
                    <div class="d-flex justify-content-end">
                        <button
                            type="button"
                            class="btn btn-success btn-sm px-4 apply-discount-button"
                            data-discount-id="${discount.id}"
                            data-variable="${discount.is_variable}"
                            data-value-type="${discount.value_type}">
                            Apply
                        </button>
                    </div>

                </div>
            </div>
            `;
        }).join('');

        $('#available-discount-list').html(html);
    };

    const renderAppliedDiscounts = (discounts) => {

        if (!discounts.length) {
            $('#applied-discount-list').html(`
                <div class="text-muted text-center py-5">
                    No applied discounts
                </div>
            `);
            return;
        }

        const html = discounts.map(discount => {

            return `
            <div class="border-0 shadow-sm rounded-3 p-3 mb-3 bg-light">

                <div class="d-flex justify-content-between align-items-start">

                    <!-- LEFT -->
                    <div class="me-3">

                        <div class="fw-bold text-gray-900">
                            ${discount.discount_type_name}
                        </div>

                        <div class="fs-7 text-muted">
                            ${
                                discount.value_type === 'Percentage'
                                    ? `${discount.discount_value}%`
                                    : formatPeso(discount.discount_value)
                            }
                        </div>

                        <!-- REFERENCE INFO -->
                        ${
                            (discount.reference_name || discount.reference_number)
                                ? `
                                <div class="fs-8 text-muted mt-1">
                                    ${discount.reference_name ? `<span>${discount.reference_name}</span>` : ''}
                                    ${discount.reference_number ? `<span class="ms-1">• ${discount.reference_number}</span>` : ''}
                                </div>
                                `
                                : ''
                        }

                        <!-- APPLIED BY -->
                        ${
                            discount.applied_by
                                ? `
                                <div class="fs-8 text-muted">
                                    Applied by: <span class="fw-semibold">${discount.applied_by_name}</span>
                                </div>
                                `
                                : ''
                        }

                        <!-- REMARKS -->
                        ${
                            discount.remarks
                                ? `
                                <div class="fs-8 text-gray-600 mt-1">
                                    ${discount.remarks}
                                </div>
                                `
                                : ''
                        }

                    </div>

                    <!-- RIGHT -->
                    <div class="text-end">

                        <div class="fw-bold text-success fs-6 mb-2">
                            - ${formatPeso(discount.discount_amount)}
                        </div>

                        <button
                            type="button"
                            class="btn btn-light-danger btn-sm remove-discount-button"
                            data-applied-id="${discount.id}">
                            Remove
                        </button>

                    </div>

                </div>
            </div>
            `;
        }).join('');

        $('#applied-discount-list').html(html);
    };

    const renderAvailableCharges = (charges) => {

        if (!charges.length) {
            $('#available-charge-list').html(`
                <div class="text-muted text-center py-10">
                    No available charges
                </div>
            `);
            return;
        }

        const html = charges.map(charge => {

            const isVariable = charge.is_variable === 'Yes';

            return `
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-4">

                    <!-- HEADER -->
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="fw-bold fs-5 text-gray-900">
                            ${charge.charge_type_name}
                        </div>

                        <div class="fs-7 text-muted text-end">
                            ${
                                isVariable
                                    ? `<span class="badge bg-light text-dark">Variable</span>`
                                    : (
                                        charge.value_type === 'Percentage'
                                            ? `<span class="text-danger fw-semibold">${charge.charge_value}%</span>`
                                            : `<span class="text-danger fw-semibold">${formatPeso(charge.charge_value)}</span>`
                                    )
                            }
                        </div>
                    </div>

                    <!-- VARIABLE INPUT -->
                    ${
                        isVariable
                            ? `
                            <div class="mb-3">
                                <label class="form-label fs-8 text-muted mb-1">Value</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control form-control-sm variable-charge-value"
                                    placeholder="Enter amount">
                            </div>
                            `
                            : ''
                    }

                    <!-- REMARKS -->
                    <div class="mb-3">
                        <label class="form-label fs-8 text-muted mb-1">Remarks</label>
                        <textarea
                            class="form-control form-control-sm charge-remarks"
                            rows="2"
                            placeholder="Optional notes"></textarea>
                    </div>

                    <!-- ACTION -->
                    <div class="d-flex justify-content-end">
                        <button
                            type="button"
                            class="btn btn-danger btn-sm px-4 apply-charge-button"
                            data-charge-id="${charge.id}"
                            data-variable="${charge.is_variable}"
                            data-value-type="${charge.value_type}">
                            Apply
                        </button>
                    </div>

                </div>
            </div>
            `;
        }).join('');

        $('#available-charge-list').html(html);
    };

    const renderAppliedCharges = (charges) => {

        if (!charges.length) {
            $('#applied-charge-list').html(`
                <div class="text-muted text-center py-5 fw-semibold">
                    No applied charges
                </div>
            `);
            return;
        }

        const html = charges.map(charge => {

            return `
            <div class="border-0 shadow-sm rounded-3 p-3 mb-3 bg-light">

                <div class="d-flex justify-content-between align-items-start">

                    <!-- LEFT -->
                    <div class="me-3">

                        <div class="fw-bold text-gray-900">
                            ${charge.charge_type_name}
                        </div>

                        <div class="fs-7 text-muted">
                            ${
                                charge.value_type === 'Percentage'
                                    ? `${charge.charge_value}%`
                                    : formatPeso(charge.charge_value)
                            }
                        </div>

                        <!-- APPLIED BY -->
                        ${
                            charge.applied_by
                                ? `
                                <div class="fs-8 text-muted">
                                    Applied by: <span class="fw-semibold">${charge.applied_by_name}</span>
                                </div>
                                `
                                : ''
                        }

                        <!-- REMARKS -->
                        ${
                            charge.remarks
                                ? `
                                <div class="fs-8 text-gray-600 mt-1">
                                    ${charge.remarks}
                                </div>
                                `
                                : ''
                        }

                    </div>

                    <!-- RIGHT -->
                    <div class="text-end">

                        <div class="fw-bold text-danger fs-6 mb-2">
                            + ${formatPeso(charge.charge_amount)}
                        </div>

                        <button
                            type="button"
                            class="btn btn-light-danger btn-sm remove-charge-button"
                            data-applied-id="${charge.id}">
                            Remove
                        </button>

                    </div>

                </div>
            </div>
            `;
        }).join('');

        $('#applied-charge-list').html(html);
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

                /*
                |--------------------------------------------------------------------------
                | SHOW/HIDE TABLE BUTTON
                |--------------------------------------------------------------------------
                */

                if (orderType === 'Dine-in') {

                    $('#set-table-column')
                        .removeClass('d-none');
                }

                else {

                    $('#set-table-column')
                        .addClass('d-none');
                }

                /*
                |--------------------------------------------------------------------------
                | UPDATE BADGE
                |--------------------------------------------------------------------------
                */

                $('#badge-order-type').text(orderType);

                /*
                |--------------------------------------------------------------------------
                | RELEASE TABLE UI
                |--------------------------------------------------------------------------
                */

                if (data.table_removed) {

                    $('#badge-table').text('No Table');
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

    $(document).on('click', '#set-table', async function () {

        const shopOrderId = sessionStorage.getItem('shop_order_id');

        if (!shopOrderId) {

            showNotification('Create an order first.');

            return;
        }

        await loadFloorPlans(shopOrderId);
    });

    $(document).on(
        'click',
        '.floor-plan-filter',
        async function () {

            $('.floor-plan-filter')
                .removeClass('btn-success')
                .addClass('btn-light');

            $(this)
                .removeClass('btn-light')
                .addClass('btn-success');

            selectedFloorPlanId =
                $(this).data('floor-plan-id');

            const shopOrderId =
                sessionStorage.getItem('shop_order_id');

            await loadFloorTables(
                selectedFloorPlanId,
                shopOrderId
            );
        }
    );

    $(document).on(
        'click',
        '.selectable-table',
        async function () {

            const floorPlanTableId =
                $(this).data('floor-plan-table-id');

            await assignTableToOrder(
                floorPlanTableId
            );
        }
    );

    $(document).on(
        'click',
        '.increase-item-qty',
        async function () {

            const shopOrderItemId =
                $(this).data('shop-order-item-id');

            await updateOrderItemQuantity({
                shopOrderItemId,
                action: 'increase',
            });
        }
    );

    $(document).on(
        'click',
        '.decrease-item-qty',
        async function () {

            const shopOrderItemId =
                $(this).data('shop-order-item-id');

            await updateOrderItemQuantity({
                shopOrderItemId,
                action: 'decrease',
            });
        }
    );

    $(document).on(
        'click',
        '.delete-order-item',
        async function () {

            const shopOrderItemId =
                $(this).data('shop-order-item-id');

            await updateOrderItemQuantity({
                shopOrderItemId,
                action: 'delete',
            });
        }
    );

    $(document).on('click', '#manage-discount-button', async function () {

        try {

            const shopOrderId =
                sessionStorage.getItem('shop_order_id');

            if (!shopOrderId) {
                showNotification('No active order.');
                return;
            }

            const csrf = getCsrfToken();

            const response = await fetch(
                '/shop-order/fetch-discounts',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type':
                            'application/x-www-form-urlencoded; charset=UTF-8',
                        Accept: 'application/json',
                        ...(csrf
                            ? { 'X-CSRF-TOKEN': csrf }
                            : {}),
                    },
                    body: new URLSearchParams({
                        shop_order_id: shopOrderId,
                    }),
                }
            );

            const data = await response.json();

            if (!data.success) {
                showNotification(data.message);
                return;
            }

            renderAvailableDiscounts(
                data.available_discounts
            );

            renderAppliedDiscounts(
                data.applied_discounts
            );

        } catch (error) {

            handleSystemError(
                error,
                'fetch_failed',
                error.message
            );
        }
    });

    $(document).on(
        'click',
        '.apply-discount-button',
        async function () {

            try {

                const button = $(this);

                const card =
                    button.closest('.card');

                const discountTypeId =
                    button.data('discount-id');

                const isVariable =
                    button.data('variable');

                const valueType =
                    button.data('value-type');

                let discountValue = null;

                let referenceName = card
                        .find('.discount-reference-name')
                        .val();

                let referenceNumber = card
                        .find('.discount-reference-number')
                        .val();

                let remarks = card
                        .find('.discount-remarks')
                        .val();

                /*
                |--------------------------------------------------------------------------
                | VARIABLE VALUE
                |--------------------------------------------------------------------------
                */

                if (isVariable === 'Yes') {

                    discountValue = card
                        .find('.variable-discount-value')
                        .val();

                    if (
                        discountValue === ''
                        || discountValue === null
                    ) {

                        showNotification(
                            'Enter a discount value.'
                        );

                        return;
                    }

                    discountValue =
                        parseFloat(discountValue);

                    if (
                        isNaN(discountValue)
                        || discountValue <= 0
                    ) {

                        showNotification(
                            'Invalid discount value.'
                        );

                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PERCENTAGE LIMIT
                    |--------------------------------------------------------------------------
                    */

                    if (
                        valueType === 'Percentage'
                        && discountValue > 100
                    ) {

                        showNotification(
                            'Percentage discount cannot exceed 100%.'
                        );

                        return;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | REQUEST
                |--------------------------------------------------------------------------
                */

                const csrf = getCsrfToken();

                const shopOrderId =
                    sessionStorage.getItem(
                        'shop_order_id'
                    );

                button.prop('disabled', true);

                const response = await fetch(
                    '/shop-order/save-discount',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type':
                                'application/x-www-form-urlencoded; charset=UTF-8',
                            Accept: 'application/json',
                            ...(csrf
                                ? {
                                    'X-CSRF-TOKEN': csrf
                                }
                                : {}),
                        },
                        body: new URLSearchParams({

                            shop_order_id:
                                shopOrderId,

                            discount_type_id:
                                discountTypeId,

                            discount_value:
                                discountValue ?? '',

                            reference_name:
                                referenceName ?? '',

                            reference_number:
                                referenceNumber ?? '',

                            remarks:
                                remarks ?? '',
                        }),
                    }
                );

                const data =
                    await response.json();

                button.prop('disabled', false);

                /*
                |--------------------------------------------------------------------------
                | FAILED
                |--------------------------------------------------------------------------
                */

                if (!data.success) {

                    showNotification(
                        data.message
                    );

                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | REFRESH
                |--------------------------------------------------------------------------
                */

                populateCart(data.order);

                renderAvailableDiscounts(
                    data.available_discounts
                );

                renderAppliedDiscounts(
                    data.applied_discounts
                );

                showNotification(
                    data.message,
                    'success'
                );

            } catch (error) {

                $('.apply-discount-button')
                    .prop('disabled', false);

                handleSystemError(
                    error,
                    'save_failed',
                    error.message
                );
            }
        }
    );

    $(document).on(
        'click',
        '.remove-discount-button',
        async function () {

            try {

                const appliedId =
                    $(this).data('applied-id');

                const csrf = getCsrfToken();

                const response = await fetch(
                    '/shop-order/delete-discount',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type':
                                'application/x-www-form-urlencoded; charset=UTF-8',
                            Accept: 'application/json',
                            ...(csrf
                                ? { 'X-CSRF-TOKEN': csrf }
                                : {}),
                        },
                        body: new URLSearchParams({
                            applied_discount_id: appliedId,
                        }),
                    }
                );

                const data = await response.json();

                if (!data.success) {
                    showNotification(data.message);
                    return;
                }

                populateCart(data.order);

                renderAvailableDiscounts(
                    data.available_discounts
                );

                renderAppliedDiscounts(
                    data.applied_discounts
                );

            } catch (error) {

                handleSystemError(
                    error,
                    'delete_failed',
                    error.message
                );
            }
        }
    );

    $(document).on(
        'click',
        '#manage-charge-button',
        async function () {

            try {

                const shopOrderId =
                    sessionStorage.getItem(
                        'shop_order_id'
                    );

                if (!shopOrderId) {

                    showNotification(
                        'No active order.'
                    );

                    return;
                }

                const csrf = getCsrfToken();

                const response = await fetch(
                    '/shop-order/fetch-charges',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type':
                                'application/x-www-form-urlencoded; charset=UTF-8',
                            Accept: 'application/json',
                            ...(csrf
                                ? {
                                    'X-CSRF-TOKEN': csrf
                                }
                                : {}),
                        },
                        body: new URLSearchParams({
                            shop_order_id: shopOrderId,
                        }),
                    }
                );

                const data =
                    await response.json();

                if (!data.success) {

                    showNotification(
                        data.message
                    );

                    return;
                }

                renderAvailableCharges(
                    data.available_charges
                );

                renderAppliedCharges(
                    data.applied_charges
                );

            } catch (error) {

                handleSystemError(
                    error,
                    'fetch_failed',
                    error.message
                );
            }
        }
    );

    $(document).on(
        'click',
        '.apply-charge-button',
        async function () {

            try {

                const button = $(this);

                const card =
                    button.closest('.card');

                const chargeTypeId =
                    button.data('charge-id');

                const isVariable =
                    button.data('variable');

                const valueType =
                    button.data('value-type');

                let chargeValue = null;

                let remarks = card
                        .find('.charge-remarks')
                        .val();

                /*
                |--------------------------------------------------------------------------
                | VARIABLE VALUE
                |--------------------------------------------------------------------------
                */

                if (isVariable === 'Yes') {

                    chargeValue = card
                        .find('.variable-charge-value')
                        .val();

                    if (
                        chargeValue === ''
                        || chargeValue === null
                    ) {

                        showNotification(
                            'Enter a charge value.'
                        );

                        return;
                    }

                    chargeValue =
                        parseFloat(chargeValue);

                    if (
                        isNaN(chargeValue)
                        || chargeValue <= 0
                    ) {

                        showNotification(
                            'Invalid charge value.'
                        );

                        return;
                    }

                    if (
                        valueType === 'Percentage'
                        && chargeValue > 100
                    ) {

                        showNotification(
                            'Percentage charge cannot exceed 100%.'
                        );

                        return;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | REQUEST
                |--------------------------------------------------------------------------
                */

                const csrf = getCsrfToken();

                const shopOrderId =
                    sessionStorage.getItem(
                        'shop_order_id'
                    );

                button.prop('disabled', true);

                const response = await fetch(
                    '/shop-order/save-charge',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type':
                                'application/x-www-form-urlencoded; charset=UTF-8',
                            Accept: 'application/json',
                            ...(csrf
                                ? {
                                    'X-CSRF-TOKEN': csrf
                                }
                                : {}),
                        },
                        body: new URLSearchParams({

                            shop_order_id:
                                shopOrderId,

                            charge_type_id:
                                chargeTypeId,

                            charge_value:
                                chargeValue ?? '',

                            remarks:
                                remarks ?? '',
                        }),
                    }
                );

                const data =
                    await response.json();

                button.prop('disabled', false);

                if (!data.success) {

                    showNotification(
                        data.message
                    );

                    return;
                }

                populateCart(data.order);

                renderAvailableCharges(
                    data.available_charges
                );

                renderAppliedCharges(
                    data.applied_charges
                );

                showNotification(
                    data.message,
                    'success'
                );

            } catch (error) {

                $('.apply-charge-button')
                    .prop('disabled', false);

                handleSystemError(
                    error,
                    'save_failed',
                    error.message
                );
            }
        }
    );

    $(document).on(
        'click',
        '.remove-charge-button',
        async function () {

            try {

                const chargeId =
                    $(this).data(
                        'applied-id'
                    );

                const csrf = getCsrfToken();

                const response = await fetch(
                    '/shop-order/delete-charge',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type':
                                'application/x-www-form-urlencoded; charset=UTF-8',
                            Accept: 'application/json',
                            ...(csrf
                                ? { 'X-CSRF-TOKEN': csrf }
                                : {}),
                        },
                        body: new URLSearchParams({
                            shop_order_applied_charge_id:
                                chargeId,
                        }),
                    }
                );

                const data = await response.json();

                if (!data.success) {

                    showNotification(data.message);
                    return;
                }

                populateCart(data.order);

                renderAvailableCharges(
                    data.available_charges
                );

                renderAppliedCharges(
                    data.applied_charges
                );

            } catch (error) {

                handleSystemError(
                    error,
                    'delete_failed',
                    error.message
                );
            }
        }
    );

});