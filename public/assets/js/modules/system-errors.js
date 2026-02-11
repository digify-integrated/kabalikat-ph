import { copyToClipboard } from './form-utilities.js';

const escapeHtml = (value) => {
    const s = String(value ?? '');
    return s
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
};

const formatLaravelErrors = (errorsObj) => {
    const items = [];
    for (const [field, messages] of Object.entries(errorsObj || {})) {
        const msgs = Array.isArray(messages) ? messages : [messages];
        for (const m of msgs) items.push(`${field}: ${m}`);
    }
    if (!items.length) return '';
    return `<ul>${items.map(i => `<li>${escapeHtml(i)}</li>`).join('')}</ul>`;
};

const safePre = (label, text) =>
  `<strong>${escapeHtml(label)}:</strong><pre style="white-space:pre-wrap;max-height:240px;overflow:auto;">${escapeHtml(text)}</pre>`;

async function readResponseBody(response) {
    const contentType = response.headers.get('content-type') || '';
    if (contentType.includes('application/json') || contentType.includes('+json')) {
        try {
            return { kind: 'json', data: await response.json() };
        } catch {}
    }
    try {
        return { kind: 'text', data: await response.text() };
    } catch {
        return { kind: 'none', data: '' };
    }
}

export const handleSystemError = async (xhr, status, error) => {
    let html = '';

    if (xhr instanceof Error) {
        html += `<strong>Status:</strong> ${escapeHtml(status || 'error')}<br/>`;
        html += `<strong>Error:</strong> ${escapeHtml(error || xhr.message || 'Unknown error')}<br/>`;
        html += `<strong>Error Name:</strong> ${escapeHtml(xhr.name)}<br/>`;
        if (xhr.stack) html += safePre('Stack Trace', xhr.stack);
        showErrorDialog(html);
        return;
    }

    if (xhr instanceof Response) {
        html += `<strong>HTTP Status:</strong> ${escapeHtml(`${xhr.status} ${xhr.statusText}`)}<br/>`;
        html += `<strong>URL:</strong> ${escapeHtml(xhr.url)}<br/>`;

        const body = await readResponseBody(xhr);

        if (body.kind === 'json') {
            const data = body.data || {};
            const msg = data.message || error || xhr.statusText || 'Request failed';

            html += `<strong>Message:</strong> ${escapeHtml(msg)}<br/>`;

            if (data.errors) {
                html += `<strong>Validation Errors:</strong>${formatLaravelErrors(data.errors)}`;
            }

            if (data.exception) html += `<br/><strong>Exception:</strong> ${escapeHtml(data.exception)}`;
            if (data.file) html += `<br/><strong>File:</strong> ${escapeHtml(data.file)}`;
            if (data.line) html += `<br/><strong>Line:</strong> ${escapeHtml(data.line)}`;

            html += safePre('Response JSON', JSON.stringify(data, null, 2));
        } else if (body.kind === 'text') {
            html += `<strong>Message:</strong> ${escapeHtml(error || xhr.statusText || 'Request failed')}<br/>`;
            html += safePre('Response Body', body.data || 'No response body');
        } else {
            html += `<strong>Message:</strong> ${escapeHtml(error || xhr.statusText || 'Request failed')}<br/>`;
            html += `<strong>Response Body:</strong> Could not be read<br/>`;
        }

        showErrorDialog(html);
        return;
    }

    html += `<strong>Status:</strong> ${escapeHtml(status || 'error')}<br/>`;
    html += `<strong>Error:</strong> ${escapeHtml(error || 'Unknown error')}<br/>`;
    html += `<strong>Status Code:</strong> ${escapeHtml(xhr?.status ?? 'Unknown')}<br/>`;

    const text = xhr?.responseText ?? '';
    try {
        const json = JSON.parse(text);
        const msg = json.message || error || 'Request failed';
        html += `<strong>Message:</strong> ${escapeHtml(msg)}<br/>`;
        if (json.errors) html += `<strong>Validation Errors:</strong>${formatLaravelErrors(json.errors)}`;
        html += safePre('Response JSON', JSON.stringify(json, null, 2));
    } catch {
        html += safePre('Response Body', text || 'No response text');
    }

    showErrorDialog(html);
};

export const showErrorDialog = (error) => {
    const errorDialogElement = document.getElementById('error-dialog');

    if (errorDialogElement) {
        errorDialogElement.innerHTML = error;
        $('#system-error-modal').modal('show');
    } else {
        console.error('Error dialog element not found.');
    }


    document.getElementById('copy-error-message').addEventListener('click', async () => {
        const text = document.getElementById('error-dialog')?.textContent?.trim() || '';

        await copyToClipboard({
            text: text,
        });
    });
};
