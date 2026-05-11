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
                selector: '#product_form',
                rules: {
                    rules: {
                        product_name: { required: true},
                        product_type: { required: true},
                        product_status: { required: true},
                        tax_classification: { required: true},
                        base_price: { required: true},
                        cost_price: { required: true},
                        base_unit_id: { required: true},
                        inventory_flow: { required: true},
                        reorder_level: { required: true},
                    },
                    messages: {
                        product_name: { required: 'Enter the product name' },
                        product_type: { required: 'Choose the product type' },
                        product_status: { required: 'Choose the product status' },
                        tax_classification: { required: 'Choose the tax classification' },
                        base_price: { required: 'Enter the base price' },
                        cost_price: { required: 'Enter the cost price' },
                        base_unit_id: { required: 'Choose the base unit' },
                        inventory_flow: { required: 'Choose the inventory flow' },
                        reorder_level: { required: 'Enter the reorder level' },
                    },
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('product_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/products/save', {
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
                selector: '#attribute_form',
                rules: {
                    rules: {
                        attribute_id: { required: true},
                    },
                    messages: {
                        attribute_id: { required: 'Choose the product type' },
                    },
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('product_id', ctx.detailId ?? '');
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
                selector: '#bom_form',
                rules: {
                    rules: {
                        bom_product_id: { required: true},
                        quantity: { required: true},
                        stock_policy: { required: true},
                    },
                    messages: {
                        bom_product_id: { required: 'Choose the component product' },
                        quantity: { required: 'Enter the required quantity' },
                        stock_policy: { required: 'Choose the stock policy' },
                    },
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('product_id', ctx.detailId ?? '');
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
                selector: '#addon_form',
                rules: {
                    rules: {
                        addon_product_id: { required: true},
                        max_quantity: { required: true},
                    },
                    messages: {
                        addon_product_id: { required: 'Choose the add-on product' },
                        max_quantity: { required: 'Enter the max quantity' },
                    },
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('product_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
                        disableButton('submit-addon');
            
                        try {
                            const response = await fetch('/product-addon/save', {
                                method: 'POST',
                                body: formData,
                            });
            
                            if (!response.ok) {
                                throw new Error(`Save role assignment failed with status: ${response.status}`);
                            }
            
                            const data = await response.json();
            
                            if (data.success) {
                                reloadDatatable('#addon-table');
                                $('#addon-modal').modal('hide');
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message);
                            }
                        } catch (error) {
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        } finally {
                            enableButton('submit-addon');
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
                    product_id: ctx.detailId,
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
                addons: {
                    subControls: {
                        searchSelector: '#attribute-datatable-search',
                        lengthSelector: '#attribute-datatable-length',
                    },
                },
            },
            {
                url: '/products/generate-variation-table',
                selector: '#variation-table',
                serverSide: false,
                order: [[0, 'asc']],
                ajaxData: {
                    product_id: ctx.detailId,
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
                addons: {
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
                    product_id: ctx.detailId,
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
                addons: {
                    subControls: {
                        searchSelector: '#bom-datatable-search',
                        lengthSelector: '#bom-datatable-length',
                    },
                },
            },
            {
                url: '/product-addon/generate-table',
                selector: '#addon-table',
                serverSide: false,
                order: [[0, 'asc']],
                ajaxData: {
                    product_id: ctx.detailId,
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
                addons: {
                    subControls: {
                        searchSelector: '#addon-datatable-search',
                        lengthSelector: '#addon-datatable-length',
                    },
                },
            },
        ],
        details:[
            {
                url: '/products/fetch-details',
                formSelector: '#product_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('product_name').value = data.productName || '';
                    document.getElementById('sku').value = data.sku || '';
                    document.getElementById('barcode').value = data.barcode || '';
                    document.getElementById('base_price').value = data.basePrice || 0;
                    document.getElementById('cost_price').value = data.costPrice || 0;
                    document.getElementById('reorder_level').value = data.reorderLevel || 0;
                    document.getElementById('product_description').value = data.productDescription || '';
                    
                    const thumbnail = document.getElementById('product_image_thumbnail');
                    if (thumbnail) thumbnail.style.backgroundImage = `url(${data.productImage || ''})`;

                    await optionsPromise;
                    
                    $('#product_type').val(data.productType ?? '').trigger('change');
                    $('#product_status').val(data.productStatus ?? 'Active').trigger('change');
                    $('#tax_classification').val(data.taxClassification ?? '').trigger('change');
                    $('#base_unit_id').val(data.baseUnitId ?? '').trigger('change');
                    $('#inventory_flow').val(data.inventoryFlow ?? '').trigger('change');
                    $('#product_category_id').val(data.productCategoryId ?? '').trigger('change');

                    document.getElementById('track-inventory').checked = data.trackInventory === 'Yes';
                    document.getElementById('is-addon').checked = data.isAddon === 'Yes';
                    document.getElementById('show-on-pos').checked = data.showOnPos === 'Yes';
                    document.getElementById('is-purchasable').checked = data.isPurchasable === 'Yes';
                },
            },
        ],
        delete: {
            trigger: '#delete-product',
            url: '/products/delete',
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
                trigger: '.delete-addon',
                url: '/product-addon/delete',
                table: '#addon-table',
                swalTitle: 'Confirm Add-on Deletion',
                swalText: 'Are you sure you want to delete this add-on?',
                confirmButtonText: 'Delete'
            },
        ],
        upload: {
            trigger: '#product_image',
            url: '/products/upload-product-image',
        },
        lognotes: [
            {
                trigger: '.view-attribute-log-notes',
                table: 'product_attribute'
            },
            {
                trigger: '.view-bom-log-notes',
                table: 'product_bom'
            },
            {
                trigger: '.view-addon-log-notes',
                table: 'product_addon'
            },
        ],
        dropdown: [
            { url: '/unit/generate-options', dropdownSelector: '#base_unit_id' },
            { url: '/product-category/generate-options', dropdownSelector: '#product_category_id', data : { product_id : ctx.detailId, multiple: true } }
        ],
        attributeDropdown: {
            url: '/attribute/generate-product-attribute-options',
            dropdownSelector: '#attribute_id',
            data : {
                product_id : ctx.detailId,
                multiple: true
            }
        },
        bomDropdown: {
            url: '/products/generate-product-bom-options',
            dropdownSelector: '#bom_product_id',
            data : {
                product_id : ctx.detailId,
            }
        },
        addonDropdown: {
            url: '/products/generate-product-addon-options',
            dropdownSelector: '#addon_product_id',
            data : {
                product_id : ctx.detailId,
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

    imageRealtimeUploadButton(config.upload);

    document.addEventListener('click', async (event) => {
        const target = event.target;
    
        const addAttribute = target.closest('#add-attribute');
        if (addAttribute) {
            resetForm('attribute_form');

            generateDropdownOptions({
                url: config.attributeDropdown.url,
                dropdownSelector: config.attributeDropdown.dropdownSelector,
                data: config.attributeDropdown.data
            });            
        }
    
        const addBom = target.closest('#add-bom');
        if (addBom) {
            resetForm('bom_form');

            generateDropdownOptions({
                url: config.bomDropdown.url,
                dropdownSelector: config.bomDropdown.dropdownSelector,
                data: config.bomDropdown.data
            });            
        }
    
        const addAddon = target.closest('#add-addon');
        if (addAddon) {
            resetForm('addon_form');

            generateDropdownOptions({
                url: config.addonDropdown.url,
                dropdownSelector: config.addonDropdown.dropdownSelector,
                data: config.addonDropdown.data
            });
        }
    
        const generateVariant = target.closest('#generate-variant');
        if (generateVariant) {
            try {
                const csrf = getCsrfToken();
                const ctx = getPageContext();

                disableButton('generate-variant');
            
                const formData = new URLSearchParams();
                formData.append('product_id', ctx.detailId ?? '');
                formData.append('appId', ctx.appId ?? '');
                formData.append('navigationMenuId', ctx.navigationMenuId ?? '')
            
                const response = await fetch('/products/save-product-variation', {
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
                formData.append('product_id', ctx.detailId ?? '');
                formData.append('appId', ctx.appId ?? '');
                formData.append('navigationMenuId', ctx.navigationMenuId ?? '')
            
                const response = await fetch('/products/save-product-setting', {
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

    $('#product_category_id').on('change', async function () {
        try {
            const productCategoryId = $(this).val();
            const csrf = getCsrfToken();
            const ctx = getPageContext();
            
            const formData = new URLSearchParams();
            formData.append('product_category_id', productCategoryId);
            formData.append('product_id', ctx.detailId ?? '');
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
