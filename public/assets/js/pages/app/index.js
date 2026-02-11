import { initializeDatatable, initializeDatatableControls, reloadDatatable } from '../../util/datatable.js';
import { initializeExportFeature } from '../../util/export.js';
import { handleSystemError } from '../../util/system-errors.js';
import { getCsrfToken } from '../../form/form.js';

document.addEventListener('DOMContentLoaded', () => {
    initializeDatatable({
        url: '/generate-app-table',
        selector: '#app-table',
        columns: [
            { data: 'CHECK_BOX' },
            { data: 'APP_NAME' }
        ],
        columnDefs: [
            { width: '5%', bSortable: false, targets: 0, responsivePriority: 1 },
            { width: 'auto', targets: 1, responsivePriority: 2 }
        ],
        onRowClick: (rowData) => {
            if (rowData?.LINK) window.open(rowData.LINK, '_blank');
        }
    });

    initializeDatatableControls('#app-table');
    initializeExportFeature('app');

    document.addEventListener('click', async (event) => {
        const routes = [
            ['#delete-app', handleDeleteMultipleApps],
        ];

        for (const [selector, handler] of routes) {
            const el = event.target.closest(selector);
            if (!el) continue;

            event.preventDefault();
            await handler(event, el);
            return;
        }
    });

    async function handleDeleteMultipleApps(event, el) {
        const csrf = getCsrfToken();
        const URL = '/delete-multiple-app';

        const appModuleIds = Array.from(
            document.querySelectorAll('.datatable-checkbox-children:checked')
        ).map((checkbox) => checkbox.value);

        if (appModuleIds.length === 0) {
            showNotification('Please select the apps you want to delete.');
            return;
        }

        const { isConfirmed } = await Swal.fire({
            title: 'Confirm Multiple Apps Deletion',
            text: `Are you sure you want to delete these apps?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary',
            },
            buttonsStyling: false,
        });

        if (!isConfirmed) return;

        try {
            const body = new URLSearchParams();
            appModuleIds.forEach((id) => body.append('app_module_id[]', id));

            const response = await fetch(URL, {
            method: 'POST',
            body,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                Accept: 'application/json',
            },
            });

            if (!response.ok) {
                const errorText = await response.text().catch(() => '');
                throw new Error(`Deletion failed (${response.status}). ${errorText}`);
            }

            const data = await response.json();

            if (data?.success) {
                showNotification(data.message, 'success');
                reloadDatatable('#app-table');
            } else {
                showNotification(data?.message ?? 'Deletion failed.');
            }
        } catch (error) {
            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
        }
    }


});