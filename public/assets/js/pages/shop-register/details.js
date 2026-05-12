import { initValidation } from '../../util/validation.js';
import { showNotification } from '../../util/notifications.js';
import { attachLogNotesHandler, attachLogNotesClassHandler } from '../../util/log-notes.js';
import { disableButton, enableButton, detailsDeleteButton, detailsActionButton, imageRealtimeUploadButton, detailsTableActionButton, } from '../../form/button.js';
import { displayDetails, getPageContext, getCsrfToken, resetForm } from '../../form/form.js';
import { initializeDatatable, reloadDatatable } from '../../util/datatable.js';
import { handleSystemError } from '../../util/system-errors.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    let optionsPromise = Promise.resolve();
    const ctx = getPageContext();

    const config = {
        forms: [
            {
                selector: '#shop_register_form',
                rules: {
                    rules: {
                        shop_register_name: { required: true},
                        company_id: { required: true},
                        is_restaurant: { required: true},
                        shop_register_status: { required: true},
                    },
                    messages: {
                        shop_register_name: { required: 'Enter the shop register name' },
                        company_id: { required: 'Choose the company' },
                        is_restaurant: { required: 'Choose if resturant' },
                        shop_register_status: { required: 'Choose the shop register status' },
                    },
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('shop_register_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/shop-register/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save product failed with status: ${response.status}`);
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
                            enableButton('submit-data');
                        }
                    },
                },
            },
            {
                selector: '#floor_plan_form',
                rules: {
                    rules: {
                        floor_plan_id: { required: true},
                    },
                    messages: {
                        floor_plan_id: { required: 'Choose the product type' },
                    },
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('shop_register_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
                        disableButton('submit-attribute');
            
                        try {
                            const response = await fetch('/product-attribute/save', {
                                method: 'POST',
                                body: formData,
                            });
            
                            if (!response.ok) {
                                throw new Error(`Save role assignment failed with status: ${response.status}`);
                            }
            
                            const data = await response.json();
            
                            if (data.success) {
                                reloadDatatable('#attribute-table');
                                $('#attribute-modal').modal('hide');
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message);
                            }
                        } catch (error) {
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        } finally {
                            enableButton('submit-attribute');
                        }
                    },
                }
            },
            {
                selector: '#discount_form',
                rules: {
                    rules: {
                        discount_shop_register_id: { required: true},
                        quantity: { required: true},
                        stock_policy: { required: true},
                    },
                    messages: {
                        discount_shop_register_id: { required: 'Choose the component product' },
                        quantity: { required: 'Enter the required quantity' },
                        stock_policy: { required: 'Choose the stock policy' },
                    },
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('shop_register_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
                        disableButton('submit-bom');
            
                        try {
                            const response = await fetch('/product-bom/save', {
                                method: 'POST',
                                body: formData,
                            });
            
                            if (!response.ok) {
                                throw new Error(`Save role assignment failed with status: ${response.status}`);
                            }
            
                            const data = await response.json();
            
                            if (data.success) {
                                reloadDatatable('#bom-table');
                                $('#bom-modal').modal('hide');
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message);
                            }
                        } catch (error) {
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        } finally {
                            enableButton('submit-bom');
                        }
                    },
                }
            },
            {
                selector: '#charge_form',
                rules: {
                    rules: {
                        charge_shop_register_id: { required: true},
                        max_quantity: { required: true},
                    },
                    messages: {
                        charge_shop_register_id: { required: 'Choose the add-on product' },
                        max_quantity: { required: 'Enter the max quantity' },
                    },
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('shop_register_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
                        disableButton('submit-charge');
            
                        try {
                            const response = await fetch('/product-charge/save', {
                                method: 'POST',
                                body: formData,
                            });
            
                            if (!response.ok) {
                                throw new Error(`Save role assignment failed with status: ${response.status}`);
                            }
            
                            const data = await response.json();
            
                            if (data.success) {
                                reloadDatatable('#charge-table');
                                $('#charge-modal').modal('hide');
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message);
                            }
                        } catch (error) {
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        } finally {
                            enableButton('submit-charge');
                        }
                    },
                }
            },
        ],
        table: [
            {
                url: '/product-attribute/generate-table',
                selector: '#attribute-table',
                serverSide: false,
                order: [[0, 'asc']],
                ajaxData: {
                    shop_register_id: ctx.detailId,
                    page_navigation_menu_id: ctx.navigationMenuId,
                },
                 columns: [
                    { data: 'ATTRIBUTE' },
                    { data: 'ATTRIBUTE_VALUE' },
                    { data: 'ACTION' },
                ],
                columnDefs: [
                    { width: 'auto', targets: 0, responsivePriority: 1 },
                    { width: 'auto', targets: 1, responsivePriority: 2 },
                    { width: 'auto', bSortable: false, targets: 2, responsivePriority: 3 },
                ],
                charges: {
                    subControls: {
                        searchSelector: '#attribute-datatable-search',
                        lengthSelector: '#attribute-datatable-length',
                    },
                },
            },
            {
                url: '/shop-register/generate-variation-table',
                selector: '#variation-table',
                serverSide: false,
                order: [[0, 'asc']],
                ajaxData: {
                    shop_register_id: ctx.detailId,
                    page_navigation_menu_id: ctx.navigationMenuId,
                },
                 columns: [
                    { data: 'VARIANT' },
                    { data: 'ACTION' },
                ],
                columnDefs: [
                    { width: 'auto', targets: 0, responsivePriority: 1 },
                    { width: 'auto', bSortable: false, targets: 1, responsivePriority: 2 },
                ],
                charges: {
                    subControls: {
                        searchSelector: '#variation-datatable-search',
                        lengthSelector: '#variation-datatable-length',
                    },
                },
            },
            {
                url: '/product-bom/generate-table',
                selector: '#bom-table',
                serverSide: false,
                order: [[0, 'asc']],
                ajaxData: {
                    shop_register_id: ctx.detailId,
                    page_navigation_menu_id: ctx.navigationMenuId,
                },
                 columns: [
                    { data: 'BOM_PRODUCT' },
                    { data: 'QUANTITY' },
                    { data: 'STOCK_POLICY' },
                    { data: 'ACTION' },
                ],
                columnDefs: [
                    { width: 'auto', targets: 0, responsivePriority: 1 },
                    { width: 'auto', targets: 1, responsivePriority: 2 },
                    { width: 'auto', targets: 2, responsivePriority: 3 },
                    { width: 'auto', bSortable: false, targets: 3, responsivePriority: 4 },
                ],
                charges: {
                    subControls: {
                        searchSelector: '#bom-datatable-search',
                        lengthSelector: '#bom-datatable-length',
                    },
                },
            },
            {
                url: '/product-charge/generate-table',
                selector: '#charge-table',
                serverSide: false,
                order: [[0, 'asc']],
                ajaxData: {
                    shop_register_id: ctx.detailId,
                    page_navigation_menu_id: ctx.navigationMenuId,
                },
                 columns: [
                    { data: 'ADDON_PRODUCT' },
                    { data: 'MAX_QUANTITY' },
                    { data: 'ACTION' },
                ],
                columnDefs: [
                    { width: 'auto', targets: 0, responsivePriority: 1 },
                    { width: 'auto', targets: 1, responsivePriority: 2 },
                    { width: 'auto', bSortable: false, targets: 2, responsivePriority: 3 },
                ],
                charges: {
                    subControls: {
                        searchSelector: '#charge-datatable-search',
                        lengthSelector: '#charge-datatable-length',
                    },
                },
            },
        ],
        details:[
            {
                url: '/shop-register/fetch-details',
                formSelector: '#shop_register_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('shop_register_name').value = data.shopRegisterName || '';

                    await optionsPromise;
                    
                    $('#company_id').val(data.companyId ?? '').trigger('change');
                    $('#is_restaurant').val(data.isRestaurant ?? 'No').trigger('change');
                    $('#shop_register_status').val(data.shopRegisterStatus ?? 'Active').trigger('change');
                },
            },
        ],
        delete: {
            trigger: '#delete-shop-register',
            url: '/shop-register/delete',
            swalTitle: 'Confirm User Deletion',
            swalText: 'Are you sure you want to delete this product?',
            confirmButtonText: 'Delete',
        },
        table_action: [
            {
                trigger: '.delete-attribute',
                url: '/product-attribute/delete',
                table: '#attribute-table',
                swalTitle: 'Confirm Attribute Deletion',
                swalText: 'Are you sure you want to delete this attribute?',
                confirmButtonText: 'Delete'
            },
            {
                trigger: '.delete-bom',
                url: '/product-bom/delete',
                table: '#bom-table',
                swalTitle: 'Confirm Component Deletion',
                swalText: 'Are you sure you want to delete this component?',
                confirmButtonText: 'Delete'
            },
            {
                trigger: '.delete-charge',
                url: '/product-charge/delete',
                table: '#charge-table',
                swalTitle: 'Confirm Add-on Deletion',
                swalText: 'Are you sure you want to delete this add-on?',
                confirmButtonText: 'Delete'
            },
        ],
        lognotes: [
            {
                trigger: '.view-floor-plan-log-notes',
                table: 'shop_register_floor_plan'
            },
            {
                trigger: '.view-discount-log-notes',
                table: 'shop_register_discount'
            },
            {
                trigger: '.view-charge-log-notes',
                table: 'shop_register_charge'
            },
        ],
        dropdown: [
            { url: '/company/generate-options', dropdownSelector: '#company_id' },
            { url: '/warehouse/generate-options', dropdownSelector: '#warehouse_id', data : { multiple: true } },
            { url: '/payment-method/generate-options', dropdownSelector: '#payment_method_id', data : { multiple: true } },
            { url: '/user/generate-options', dropdownSelector: '#access', data : { multiple: true } },
        ],
        attributeDropdown: {
            url: '/attribute/generate-shop-register-attribute-options',
            dropdownSelector: '#floor_plan_id',
            data : {
                shop_register_id : ctx.detailId,
                multiple: true
            }
        },
        bomDropdown: {
            url: '/shop-register/generate-shop-register-bom-options',
            dropdownSelector: '#discount_shop_register_id',
            data : {
                shop_register_id : ctx.detailId,
            }
        },
        chargeDropdown: {
            url: '/shop-register/generate-shop-register-charge-options',
            dropdownSelector: '#charge_shop_register_id',
            data : {
                shop_register_id : ctx.detailId,
            }
        },
    };

    (async () => {
        try {
            optionsPromise = Promise.all(
                config.dropdown.map((cfg) => generateDropdownOptions(cfg))
            );

            const fetchDetailsPromise = Promise.all(
                config.details.map((cfg) => displayDetails(cfg))
            );

            await Promise.all([
                fetchDetailsPromise,
            ]);
        } catch (err) {
            handleSystemError(err, 'init_failed', `Initialization failed: ${err.message}`);
        }
    })();

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));
    config.table.map((cfg) => initializeDatatable(cfg))

    attachLogNotesHandler();
    config.lognotes.map((cfg) => attachLogNotesClassHandler(cfg.trigger, cfg.table));

    detailsDeleteButton(config.delete);

    config.table_action.map((cfg) => detailsTableActionButton(cfg));

    document.addEventListener('click', async (event) => {
        const target = event.target;
    
        const addAttribute = target.closest('#add-attribute');
        if (addAttribute) {
            resetForm('floor_plan_form');

            generateDropdownOptions({
                url: config.attributeDropdown.url,
                dropdownSelector: config.attributeDropdown.dropdownSelector,
                data: config.attributeDropdown.data
            });            
        }
    
        const addBom = target.closest('#add-bom');
        if (addBom) {
            resetForm('discount_form');

            generateDropdownOptions({
                url: config.bomDropdown.url,
                dropdownSelector: config.bomDropdown.dropdownSelector,
                data: config.bomDropdown.data
            });            
        }
    
        const addAddon = target.closest('#add-charge');
        if (addAddon) {
            resetForm('charge_form');

            generateDropdownOptions({
                url: config.chargeDropdown.url,
                dropdownSelector: config.chargeDropdown.dropdownSelector,
                data: config.chargeDropdown.data
            });
        }
    
        const generateVariant = target.closest('#generate-variant');
        if (generateVariant) {
            try {
                const csrf = getCsrfToken();
                const ctx = getPageContext();

                disableButton('generate-variant');
            
                const formData = new URLSearchParams();
                formData.append('shop_register_id', ctx.detailId ?? '');
                formData.append('appId', ctx.appId ?? '');
                formData.append('navigationMenuId', ctx.navigationMenuId ?? '')
            
                const response = await fetch('/shop-register/save-shop-register-variation', {
                    method: 'POST',
                    body: formData,
                    headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    Accept: 'application/json',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                    },
                });
            
                if (!response.ok) {
                    throw new Error(`Generate variant failed with status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    showNotification(data.message, 'success');
                    reloadDatatable('#variation-table');
                } else {
                    showNotification(data.message);
                }

                enableButton('generate-variant');
            } catch (error) {
                handleSystemError(error, 'fetch_failed', `Failed to settings: ${error.message}`);
            }
        }
    });

    document.addEventListener('change', async (event) => {
        const target = event.target;

        const productSetting = target.closest('.product-setting');
        if (productSetting) {
            const setting = target.dataset.setting;
            const value = target.checked ? 'Yes' : 'No';
        
            try {
                const csrf = getCsrfToken();
                const ctx = getPageContext();
            
                const formData = new URLSearchParams();
                formData.append('setting', setting);
                formData.append('value', value);
                formData.append('shop_register_id', ctx.detailId ?? '');
                formData.append('appId', ctx.appId ?? '');
                formData.append('navigationMenuId', ctx.navigationMenuId ?? '')
            
                const response = await fetch('/shop-register/save-shop-register-setting', {
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
            
                if (!data.success) {
                    showNotification(data.message);
                }
            } catch (error) {
                handleSystemError(error, 'fetch_failed', `Failed to settings: ${error.message}`);
            }
        }
    });

    $('#shop_register_category_id').on('change', async function () {
        try {
            const productCategoryId = $(this).val();
            const csrf = getCsrfToken();
            const ctx = getPageContext();
            
            const formData = new URLSearchParams();
            formData.append('shop_register_category_id', productCategoryId);
            formData.append('shop_register_id', ctx.detailId ?? '');
            formData.append('appId', ctx.appId ?? '');
            formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
            const response = await fetch('/product-category-map/save', {
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
            
            if (!data.success) {
                showNotification(data.message);
            }
        } catch (error) {
            handleSystemError(error, 'fetch_failed', `Failed to settings: ${error.message}`);
        }
    });
});
