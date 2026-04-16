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
            selector: '#company_form',
            rules: {
                rules: {
                    company_name: { required: true},
                    address: { required: true },
                    city_id: { required: true },
                    currency_id: { required: true },
                },
                messages: {
                    company_name: { required: 'Enter the display name' },
                    address: { required: 'Enter the address' },
                    city_id: { required: 'Select the city' },
                    currency_id: { required: 'Select the currency' },
                },
                submitHandler: async (form) => {
                    const ctx = getPageContext();

                    const formData = new URLSearchParams(new FormData(form));
                    formData.append('company_id', ctx.detailId ?? '');
                    formData.append('appId', ctx.appId ?? '');
                    formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                    disableButton('submit-data');

                    try {
                        const response = await fetch('/company/save', {
                            method: 'POST',
                            body: formData,
                        });

                        if (!response.ok) {
                            throw new Error(`Save comapny failed with status: ${response.status}`);
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
            url: '/company/fetch-details',
            formSelector: '#company_form',
            busyHideTargets: ['#submit-data'],
            onSuccess: async (data) => {
                document.getElementById('company_name').value = data.companyName || '';
                document.getElementById('address').value = data.address || '';
                document.getElementById('tax_id').value = data.taxId || '';
                document.getElementById('phone').value = data.phone || '';
                document.getElementById('telephone').value = data.telephone || '';
                document.getElementById('email').value = data.email || '';
                document.getElementById('website').value = data.website || '';

                const thumbnail = document.getElementById('company_thumbnail');
                if (thumbnail) thumbnail.style.backgroundImage = `url(${data.companyLogo || ''})`;

                await optionsPromise;

                $('#city_id').val(data.cityId).trigger('change');
                $('#currency_id').val(data.currencyId).trigger('change');
            },
        },
        dropdown: [
            { url: '/city/generate-options', dropdownSelector: '#city_id' },
            { url: '/currency/generate-options', dropdownSelector: '#currency_id' },
        ],
        delete: {
            trigger: '#delete-company',
            url: '/company/delete',
            swalTitle: 'Confirm Company Deletion',
            swalText: 'Are you sure you want to delete this company?',
            confirmButtonText: 'Delete',
        },
        upload: {
            trigger: '#company_logo',
            url: '/company/upload-company-logo',
        },
    };

    (async () => {
        try {
            optionsPromise = Promise.all(
                config.dropdown.map((cfg) =>
                generateDropdownOptions({
                    url: cfg.url,
                    dropdownSelector: cfg.dropdownSelector,
                })
                )
            );

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

    imageRealtimeUploadButton(config.upload);
});
