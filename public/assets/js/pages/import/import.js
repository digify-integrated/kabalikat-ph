import { initValidation } from '../../util/validation.js';
import { resetForm } from '../../form/form.js';
import { disableButton, enableButton } from '../../form/button.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { getPageContext } from '../../form/form.js';

document.addEventListener('DOMContentLoaded', () => {
    initValidation('#upload_form', {
        rules: {
            import_file: { required: true}
        },
        messages: {
            import_file: { required: 'Choose the import file' },
        },
        submitHandler: async (form) => {
            const ROUTE = document.querySelector('.upload-file-preview').classList.contains('d-none')
                            ? '/import/preview'
                            : '/import/save';

            const ctx = getPageContext();

            const formData = new FormData(form);
            formData.append('appId', ctx.appId ?? '');
            formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

            disableButton(['submit-upload', 'import', 'reset-import']);

            try {
                const response = await fetch(ROUTE, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                if (!response.ok) {
                    throw new Error(`Request failed with status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    const $preview = $('.upload-file-preview');
                    const $defaultPreview = $('.upload-file-default-preview');

                    if ($preview.hasClass('d-none')) {
                        $defaultPreview.addClass('d-none');
                        $preview.removeClass('d-none');

                        document.getElementById('upload-file-preview-table').innerHTML = data.data?.preview ?? data.preview;

                        $('#upload-modal').modal('hide');
                        enableButton(['submit-upload', 'import', 'reset-import']);
                    } else {
                        setNotification(data.message, 'success');
                        window.location.href = data.redirect_link;
                    }
                } else {
                    showNotification(data.message);
                    enableButton(['submit-upload', 'import', 'reset-import']);
                }
            } catch (error) {
                enableButton(['submit-upload', 'import', 'reset-import']);
                handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
            }
        },
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('#reset-import')) return;

        document.querySelectorAll('.upload-file-default-preview').forEach(el => el.classList.remove('d-none'));
        document.querySelectorAll('.upload-file-preview').forEach(el => el.classList.add('d-none'));

        resetForm('upload_form');
        document.getElementById('upload-file-preview-table').innerHTML = '';
    });
});
