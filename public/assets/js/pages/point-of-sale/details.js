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

        return `
        <div class="col-6 col-md-4 col-xl-3">

            <button
                type="button"
                ${disabled ? 'disabled' : ''}
                class="card border-0 shadow-sm h-100 w-100 text-start product-card ${disabled ? 'product-card-disabled' : ''}"
                data-product-id="${product.id}">

                <div class="card-body">

                    <!-- STATUS -->
                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <span class="badge rounded-pill ${badgeClass} px-3 py-2">
                            ${badgeText}
                        </span>

                        <i class="ki-duotone ${icon} fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>

                    </div>

                    <!-- PRODUCT -->
                    <div class="mb-4">

                        <h5 class="fw-bold ${disabled ? 'text-muted' : 'text-dark'} mb-1">
                            ${product.product_name}
                        </h5>

                        <div class="text-muted small">
                            ${product.category_name}
                        </div>

                        ${
                            disabled
                            ? `
                            <div class="text-danger fs-8 mt-2">
                                ${product.stock_status}
                            </div>
                            `
                            : ''
                        }

                    </div>

                    <!-- PRICE -->
                    <div class="d-flex align-items-center justify-content-between">

                        <div>

                            <div class="small text-muted mb-1">
                                Price
                            </div>

                            <div class="fw-bold fs-2 ${disabled ? 'text-muted' : 'text-primary'}">
                                ₱${product.price}
                            </div>

                        </div>

                        <div class="${disabled ? 'text-danger' : 'text-primary'}">

                            <i class="ki-duotone ${disabled ? 'ki-information' : 'ki-arrow-right'} fs-2"></i>

                        </div>

                    </div>

                </div>

            </button>

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

    generatePOSCategory('/shop-register/generate-category');
    generatePOSProduct('/shop-register/generate-product');

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

    let searchTimeout;

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

        }, 300);
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
});