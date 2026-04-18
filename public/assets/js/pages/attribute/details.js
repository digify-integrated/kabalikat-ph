import { initValidation } from '../../util/validation.js';
import { showNotification } from '../../util/notifications.js';
import { attachLogNotesHandler, attachLogNotesClassHandler } from '../../util/log-notes.js';
import {
  disableButton,
  enableButton,
  detailsDeleteButton,
  detailsTableActionButton,
} from '../../form/button.js';
import { displayDetails, getPageContext, resetForm } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';
import { initializeDatatable, reloadDatatable } from '../../util/datatable.js';

document.addEventListener('DOMContentLoaded', () => {
    const ctx = getPageContext();

    let optionsPromise = Promise.resolve();

    const config = {
        forms: [
            {
                selector: '#attribute_form',
                rules: {
                    rules: {
                        attribute_name: { required: true},
                        selection_type: { required: true },
                    },
                    messages: {
                        attribute_name: { required: 'Enter the display name' },
                        selection_type: { required: 'Choose the selection type' },
                    },
                    submitHandler: async (form) => {
                        const ctx2 = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('attribute_id', ctx2.detailId ?? '');
                        formData.append('appId', ctx2.appId ?? '');
                        formData.append('navigationMenuId', ctx2.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/attribute/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save attribute failed with status: ${response.status}`);
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
                }
            },
            {
                selector: '#attribute_value_form',
                rules: {
                     rules: {
                        attribute_value: { required: true},
                    },
                    messages: {
                        attribute_value: { required: 'Enter the value' },
                    },
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('attribute_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-attribute-value');

                        try {
                            const response = await fetch('/attribute-value/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save attribute value failed with status: ${response.status}`);
                            }

                            const data = await response.json();

                            if (data.success) {
                                reloadDatatable('#attribute-value-table');
                                $('#attribute-value-modal').modal('hide');
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message);
                            }
                        } catch (error) {
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        } finally {
                            enableButton('submit-attribute-value');
                        }
                    },
                }
            },
        ],
        table: {
            url: '/attribute-value/generate-table',
            selector: '#attribute-value-table',
            serverSide: false,
            order: [[0, 'asc']],
            ajaxData: {
                attribute_id: ctx.detailId,
                page_navigation_menu_id: ctx.navigationMenuId,
            },
            columns: [
                { data: 'VALUE' },
                { data: 'ACTION' },
            ],
            columnDefs: [
                { width: 'auto', targets: 0, responsivePriority: 1 },
                { width: 'auto', bSortable: false, targets: 1, responsivePriority: 2 },
            ],
            addons: {
                subControls: {
                    searchSelector: '#attribute-value-datatable-search',
                    lengthSelector: '#attribute-value-datatable-length',
                },
            },
        },
        detailsList: [
            {
                url: '/attribute/fetch-details',
                formSelector: '#attribute_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('attribute_name').value = data.attributeName || '';

                    $('#selection_type').val(data.selectionType).trigger('change');

                    await optionsPromise;
                },
            }
        ],
        delete: {
            trigger: '#delete-attribute',
            url: '/attribute/delete',
            swalTitle: 'Confirm Attribute Deletion',
            swalText: 'Are you sure you want to delete this attribute?',
            confirmButtonText: 'Delete',
        },
        table_action: {
            trigger: '.delete-attribute-value',
            url: '/attribute-value/delete',
            table: '#attribute-value-table',
            swalTitle: 'Confirm Attribute Value Deletion',
            swalText: 'Are you sure you want to delete this attribute value?',
            confirmButtonText: 'Delete'
        },
        permission_toggle: {
            trigger: '.update-attribute-value',
            url: '/attribute-value/update',
        },
        lognotes: {
            trigger: '.view-attribute-value-log-notes',
            table: 'attribute_value'
        }
    };

    (async () => {
        try {
        const attributeValueTablePromise = Promise.resolve().then(() =>
            initializeDatatable(config.table)
        );

        const fetchDetailsPromise = Promise.all(
            config.detailsList.map((cfg) => displayDetails(cfg))
        );

        await Promise.all([
            fetchDetailsPromise,
            attributeValueTablePromise,
        ]);
        } catch (err) {
            handleSystemError(err, 'init_failed', `Initialization failed: ${err.message}`);
        }
    })();

    config.forms.forEach((cfg) => {
        initValidation(cfg.selector, cfg.rules);
    });

    attachLogNotesHandler();
    attachLogNotesClassHandler(config.lognotes.trigger, config.lognotes.table);

    detailsDeleteButton(config.delete);

    detailsTableActionButton(config.table_action);

    document.addEventListener('click', async (event) => {
        const target = event.target;

        const addAttributeValueBtn = target.closest('#add-attribute-value');
        if (addAttributeValueBtn) {
            resetForm('attribute_value_form');
        }
    });
});
