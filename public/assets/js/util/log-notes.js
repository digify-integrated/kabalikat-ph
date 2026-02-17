import { handleSystemError } from '../util/system-errors.js';
import { getCsrfToken, getPageContext } from '../form/form.js';

export const attachLogNotesHandler = () => {
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('#log-notes-main');
        if (!btn) return;

        const ctx = getPageContext();
        logNotes(ctx.databaseTable,  ctx.detailId);
    });
};

export const attachLogNotesClassHandler = (trigger, database_table) => {
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest(trigger);
        if (!btn) return;
        

        logNotes(database_table, btn.dataset.referenceId);
    });
};

export const logNotes = async (database_table, reference_id) => {
  const logNotesContainer = document.getElementById('log-notes');

  const loaderHtml =
    '<div class="d-flex justify-content-center align-items-center py-4">' +
      '<div class="text-center">' +
        '<span class="spinner-border spinner-border-sm align-middle ms-0" aria-hidden="true"></span>' +
        '<span class="visually-hidden">Loading...</span>' +
      '</div>' +
    '</div>';

  if (logNotesContainer) {
    logNotesContainer.innerHTML = loaderHtml;
  } else {
    console.warn('logNotes: #log-notes element not found, cannot display log notes.');
  }

  try {
    const csrf = getCsrfToken();
    const ctx = getPageContext();

    const formData = new URLSearchParams();
    formData.append('appId', ctx.appId ?? '');
    formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
    formData.append('databaseTable', database_table);
    formData.append('referenceId', reference_id);

    const response = await fetch('/audit-log/fetch', {
      method: 'POST',
      body: formData,
      headers: {
        Accept: 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch log notes. HTTP status: ${response.status}`);
    }

    const data = await response.json();

    if (data.success) {
      if (logNotesContainer) logNotesContainer.innerHTML = data.log_notes;
    } else if (data.invalid_session) {
      setNotification(data.title, data.message, data.message_type);
      window.location.href = data.redirect_link;
    } else {
      showNotification(data.title, data.message, data.message_type);
      if (logNotesContainer) logNotesContainer.innerHTML = '';
    }
  } catch (error) {
    if (logNotesContainer) logNotesContainer.innerHTML = '';

    handleSystemError(error, 'fetch_failed', `Log notes fetch failed: ${error.message}`);
  }
};