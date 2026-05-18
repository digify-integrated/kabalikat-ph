import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton } from '../../form/button.js';
import { getPageContext, getCsrfToken, resetForm } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';

document.addEventListener('DOMContentLoaded', () => {
    let searchTimeout;

    const config = {
        forms: [
            {
                selector: '#product_form',
                rules: {
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
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
                                showNotification(data.message, 'success');
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

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));
    config.posCategory.map((cfg) => generatePOSCategory(cfg.url));
    config.posProduct.map((cfg) => generatePOSProduct(cfg.url));

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

    function updateModalTotal() {
        const qty = parseFloat(document.getElementById('order_qty_input').value || 0);
        const price = parseFloat(document.getElementById('modal-product-base-price').value || 0);

        const total = qty * price;

        document.getElementById('modal-product-price').textContent =
            '₱ ' + total.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-kt-dialer-control="increase"], [data-kt-dialer-control="decrease"]')) {

            setTimeout(updateModalTotal, 10);
        }
    });
});