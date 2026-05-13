import { initValidation } from '../../util/validation.js';
import { showNotification } from '../../util/notifications.js';
import { attachLogNotesHandler, attachLogNotesClassHandler } from '../../util/log-notes.js';
import { disableButton, enableButton, detailsDeleteButton, detailsActionButton, imageRealtimeUploadButton, detailsTableActionButton, } from '../../form/button.js';
import { displayDetails, handleActionFetch, getPageContext, getCsrfToken, resetForm } from '../../form/form.js';
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
                selector: '#discount_form',
                rules: {
                    rules: {
                        discount_type_id: { required: true},
                        discount_automatic_application: { required: true},
                    },
                    messages: {
                        discount_type_id: { required: 'Choose the discount' },
                        discount_automatic_application: { required: 'Choose the automatic application' },
                    },
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('shop_register_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
                        disableButton('submit-discount');
            
                        try {
                            const response = await fetch('/shop-register-discount/save', {
                                method: 'POST',
                                body: formData,
                            });
            
                            if (!response.ok) {
                                throw new Error(`Save discount failed with status: ${response.status}`);
                            }
            
                            const data = await response.json();
            
                            if (data.success) {
                                reloadDatatable('#discount-table');
                                $('#discount-modal').modal('hide');
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message);
                            }
                        } catch (error) {
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        } finally {
                            enableButton('submit-discount');
                        }
                    },
                }
            },
            {
                selector: '#charge_form',
                rules: {
                    rules: {
                        charge_type_id: { required: true},
                        charge_automatic_application: { required: true},
                    },
                    messages: {
                        charge_type_id: { required: 'Choose the charge' },
                        charge_automatic_application: { required: 'Choose the automatic application' },
                    },
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('shop_register_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
                        disableButton('submit-charge');
            
                        try {
                            const response = await fetch('/shop-register-charge/save', {
                                method: 'POST',
                                body: formData,
                            });
            
                            if (!response.ok) {
                                throw new Error(`Save charge failed with status: ${response.status}`);
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
                url: '/shop-register-discount/generate-table',
                selector: '#discount-table',
                serverSide: false,
                order: [[0, 'asc']],
                ajaxData: {
                    shop_register_id: ctx.detailId,
                    page_navigation_menu_id: ctx.navigationMenuId,
                },
                 columns: [
                    { data: 'DISCOUNT' },
                    { data: 'IS_VARIABLE' },
                    { data: 'DISCOUNT_VALUE' },
                    { data: 'AUTOMATIC_APPLICATION' },
                    { data: 'ACTION' },
                ],
                columnDefs: [
                    { width: 'auto', targets: 0, responsivePriority: 1 },
                    { width: 'auto', targets: 1, responsivePriority: 2 },
                    { width: 'auto', targets: 2, responsivePriority: 3 },
                    { width: 'auto', targets: 3, responsivePriority: 4 },
                    { width: 'auto', bSortable: false, targets: 4, responsivePriority: 5 },
                ],
                charges: {
                    subControls: {
                        searchSelector: '#discount-datatable-search',
                        lengthSelector: '#discount-datatable-length',
                    },
                },
            },
            {
                url: '/shop-register-charge/generate-table',
                selector: '#charge-table',
                serverSide: false,
                order: [[0, 'asc']],
                ajaxData: {
                    shop_register_id: ctx.detailId,
                    page_navigation_menu_id: ctx.navigationMenuId,
                },
                 columns: [
                    { data: 'CHARGE' },
                    { data: 'IS_VARIABLE' },
                    { data: 'CHARGE_VALUE' },
                    { data: 'AUTOMATIC_APPLICATION' },
                    { data: 'ACTION' },
                ],
                columnDefs: [
                    { width: 'auto', targets: 0, responsivePriority: 1 },
                    { width: 'auto', targets: 1, responsivePriority: 2 },
                    { width: 'auto', targets: 2, responsivePriority: 3 },
                    { width: 'auto', targets: 3, responsivePriority: 4 },
                    { width: 'auto', bSortable: false, targets: 4, responsivePriority: 5 },
                ],
                charges: {
                    subControls: {
                        searchSelector: '#discount-datatable-search',
                        lengthSelector: '#discount-datatable-length',
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
                    
                    $('#warehouse_id').val(data.warehouseId ?? '').trigger('change');
                    $('#floor_plan_id').val(data.floorPlanId ?? '').trigger('change');
                    $('#payment_method_id').val(data.paymentMethodId ?? '').trigger('change');
                    $('#user_account_id').val(data.accessId ?? '').trigger('change');
                },
            },
        ],
        delete: {
            trigger: '#delete-shop-register',
            url: '/shop-register/delete',
            swalTitle: 'Confirm Shop Register Deletion',
            swalText: 'Are you sure you want to delete this product?',
            confirmButtonText: 'Delete',
        },
        table_action: [
            {
                trigger: '.delete-discount',
                url: '/shop-register-discount/delete',
                table: '#discount-table',
                swalTitle: 'Confirm Discount Deletion',
                swalText: 'Are you sure you want to delete this discount?',
                confirmButtonText: 'Delete'
            },
            {
                trigger: '.delete-charge',
                url: '/shop-register-charge/delete',
                table: '#charge-table',
                swalTitle: 'Confirm Charge Deletion',
                swalText: 'Are you sure you want to delete this charge',
                confirmButtonText: 'Delete'
            },
        ],
        lognotes: [
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
            { url: '/discount-type/generate-options', dropdownSelector: '#discount_type_id' },
            { url: '/charge-type/generate-options', dropdownSelector: '#charge_type_id' },
            { url: '/warehouse/generate-options', dropdownSelector: '#warehouse_id', data : { multiple: true } },
            { url: '/floor-plan/generate-options', dropdownSelector: '#floor_plan_id', data : { multiple: true } },
            { url: '/payment-method/generate-options', dropdownSelector: '#payment_method_id', data : { multiple: true } },
            { url: '/user/generate-options', dropdownSelector: '#user_account_id', data : { multiple: true } },
        ],
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
    
        const addDiscount = target.closest('#add-discount');
        if (addDiscount) {
            resetForm('discount_form');
        }

        const updateShopOrderDiscount = target.closest('.update-discount');
        if (updateShopOrderDiscount) {
            const referenceId = updateShopOrderDiscount.dataset.referenceId;
        
            await handleActionFetch({
                triggerElement: updateShopOrderDiscount,
                url: '/shop-register-discount/fetch-details',
                referenceKey: 'referenceId',
        
                onSuccess: (data) => {
                    const item = data.data;
        
                    document.getElementById('shop_register_discount_id').value = referenceId;
        
                    $('#discount_type_id').val(data.discountTypeId).trigger('change');
                    $('#discount_automatic_application').val(data.automaticApplication).trigger('change');
                }
            });
        }
    
        const addCharge = target.closest('#add-charge');
        if (addCharge) {
            resetForm('charge_form');
        }

        const updateShopOrderCharge = target.closest('.update-charge');
        if (updateShopOrderCharge) {
            const referenceId = updateShopOrderCharge.dataset.referenceId;
        
            await handleActionFetch({
                triggerElement: updateShopOrderCharge,
                url: '/shop-register-charge/fetch-details',
                referenceKey: 'referenceId',
        
                onSuccess: (data) => {
                    const item = data.data;
        
                    document.getElementById('shop_register_charge_id').value = referenceId;
        
                    $('#charge_type_id').val(data.chargeTypeId).trigger('change');
                    $('#charge_automatic_application').val(data.automaticApplication).trigger('change');
                }
            });
        }
    });

    $('#warehouse_id').on('change', async function () {
        try {
            const warehouseId = $(this).val();
            const csrf = getCsrfToken();
            const ctx = getPageContext();
            
            const formData = new URLSearchParams();
            formData.append('warehouse_id', warehouseId);
            formData.append('shop_register_id', ctx.detailId ?? '');
            formData.append('appId', ctx.appId ?? '');
            formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
            const response = await fetch('/shop-register-warehouse/save', {
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

    $('#floor_plan_id').on('change', async function () {
        try {
            const floorPlanId = $(this).val();
            const csrf = getCsrfToken();
            const ctx = getPageContext();
            
            const formData = new URLSearchParams();
            formData.append('floor_plan_id', floorPlanId);
            formData.append('shop_register_id', ctx.detailId ?? '');
            formData.append('appId', ctx.appId ?? '');
            formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
            const response = await fetch('/shop-register-floor-plan/save', {
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

    $('#payment_method_id').on('change', async function () {
        try {
            const paymentMethodId = $(this).val();
            const csrf = getCsrfToken();
            const ctx = getPageContext();
            
            const formData = new URLSearchParams();
            formData.append('payment_method_id', paymentMethodId);
            formData.append('shop_register_id', ctx.detailId ?? '');
            formData.append('appId', ctx.appId ?? '');
            formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
            const response = await fetch('/shop-register-payment-method/save', {
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

    $('#user_account_id').on('change', async function () {
        try {
            const userAccountId = $(this).val();
            const csrf = getCsrfToken();
            const ctx = getPageContext();
            
            const formData = new URLSearchParams();
            formData.append('user_account_id', userAccountId);
            formData.append('shop_register_id', ctx.detailId ?? '');
            formData.append('appId', ctx.appId ?? '');
            formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
            const response = await fetch('/shop-register-access/save', {
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
