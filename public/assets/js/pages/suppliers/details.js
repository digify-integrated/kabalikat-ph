import { initValidation } from '../../util/validation.js';
import { showNotification } from '../../util/notifications.js';
import { attachLogNotesHandler } from '../../util/log-notes.js';
import { disableButton, enableButton, detailsDeleteButton, imageRealtimeUploadButton } from '../../form/button.js';
import { generateDropdownOptions } from '../../form/field.js';
import { displayDetails, getPageContext } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';

document.addEventListener('DOMContentLoaded', () => {
    let optionsPromise = Promise.resolve();

    const config = {
        form: {
            selector: '#supplier_form',
            rules: {
                rules: {
                    supplier_name: { required: true},
                    address: { required: true },
                    city_id: { required: true },
                },
                messages: {
                    supplier_name: { required: 'Enter the display name' },
                    address: { required: 'Enter the address' },
                    city_id: { required: 'Select the city' },
                },
                submitHandler: async (form) => {
                    const ctx = getPageContext();

                    const formData = new URLSearchParams(new FormData(form));
                    formData.append('supplier_id', ctx.detailId ?? '');
                    formData.append('appId', ctx.appId ?? '');
                    formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                    disableButton('submit-data');

                    try {
                        const response = await fetch('/supplier/save', {
                            method: 'POST',
                            body: formData,
                        });

                        if (!response.ok) {
                            throw new Error(`Save supplier failed with status: ${response.status}`);
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
        details: {
            url: '/supplier/fetch-details',
            formSelector: '#supplier_form',
            busyHideTargets: ['#submit-data'],
            onSuccess: async (data) => {
                document.getElementById('supplier_name').value = data.supplierName || '';
                document.getElementById('address').value = data.address || '';
                document.getElementById('contact_person').value = data.contactPerson || '';
                document.getElementById('phone').value = data.phone || '';
                document.getElementById('telephone').value = data.telephone || '';
                document.getElementById('email').value = data.email || '';

                await optionsPromise;

                $('#city_id').val(data.cityId).trigger('change');
                $('#supplier_status').val(data.supplierStatus).trigger('change');
            },
        },
        dropdown: {
            url: '/city/generate-options',
            dropdownSelector: '#city_id',
        },
        delete: {
            trigger: '#delete-supplier',
            url: '/supplier/delete',
            swalTitle: 'Confirm Supplier Deletion',
            swalText: 'Are you sure you want to delete this supplier?',
            confirmButtonText: 'Delete',
        }
    };

    (async () => {
        try {
            optionsPromise = generateDropdownOptions(config.dropdown);

            await displayDetails(config.details);

            await Promise.all([
                optionsPromise
            ]);
        } catch (err) {
            handleSystemError(err, 'init_failed', `Initialization failed: ${err.message}`);
        }
    })();

    initValidation(config.form.selector, config.form.rules);

    attachLogNotesHandler();

    detailsDeleteButton(config.delete);
});
