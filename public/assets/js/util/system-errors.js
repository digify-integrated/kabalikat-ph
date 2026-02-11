import { copyToClipboard } from '../form/button.js';

/** Fast escape (single pass) */
const HTML_ESCAPE_RE = /[&<>"']/g;
const HTML_ESCAPE_MAP = {
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#039;',
};

const escapeHtml = (value) =>
  String(value ?? '').replace(HTML_ESCAPE_RE, (ch) => HTML_ESCAPE_MAP[ch]);

/** Tight loop, avoids Object.entries allocations */
const formatLaravelErrors = (errorsObj) => {
  if (!errorsObj || typeof errorsObj !== 'object') return '';

  const parts = [];
  for (const field in errorsObj) {
    if (!Object.prototype.hasOwnProperty.call(errorsObj, field)) continue;

    const messages = errorsObj[field];
    if (messages == null) continue;

    if (Array.isArray(messages)) {
      for (let i = 0; i < messages.length; i++) {
        parts.push('<li>', escapeHtml(field), ': ', escapeHtml(messages[i]), '</li>');
      }
    } else {
      parts.push('<li>', escapeHtml(field), ': ', escapeHtml(messages), '</li>');
    }
  }

  return parts.length ? `<ul>${parts.join('')}</ul>` : '';
};

const safePre = (label, text) =>
  `<strong>${escapeHtml(label)}:</strong>` +
  `<pre style="white-space:pre-wrap;max-height:240px;overflow:auto;">${escapeHtml(
    text
  )}</pre>`;

/**
 * Read body once as text; parse JSON only if it looks like JSON
 * (or content-type suggests it). Uses clone() so callers can still consume response.
 */
async function readResponseBody(response) {
  if (!(response instanceof Response)) return { kind: 'none', data: '' };

  const contentType = response.headers.get('content-type') || '';
  const looksJson =
    contentType.includes('application/json') ||
    contentType.includes('+json');

  try {
    const text = await response.clone().text();
    if (!text) return { kind: 'text', data: '' };

    // Parse if content-type says JSON or the payload “looks” like JSON
    if (looksJson || text[0] === '{' || text[0] === '[') {
      try {
        return { kind: 'json', data: JSON.parse(text) };
      } catch {
        // fall through to text
      }
    }

    return { kind: 'text', data: text };
  } catch {
    return { kind: 'none', data: '' };
  }
}

/** Cache DOM once (no per-call init) */
const errorDialogEl = document.getElementById('error-dialog');
const copyBtnEl = document.getElementById('copy-error-message');

/** Bind once */
if (copyBtnEl && errorDialogEl) {
  copyBtnEl.addEventListener('click', async (e) => {
    e.preventDefault();
    const text = errorDialogEl.textContent?.trim() || '';
    // avoid awaiting unless you care about handling failures
    try {
      await copyToClipboard({ text });
    } catch {
      // optional: show a toast / ignore
    }
  });
}

export const showErrorDialog = (errorHtml) => {
  if (!errorDialogEl) {
    console.error('Error dialog element not found.');
    return;
  }

  // InnerHTML is intentional here (you build safe HTML via escapeHtml)
  errorDialogEl.innerHTML = errorHtml;
  $('#system-error-modal').modal('show');
};

export const handleSystemError = async (xhr, status, error) => {
  const out = [];

  // Case 1: native Error
  if (xhr instanceof Error) {
    out.push(
      '<strong>Status:</strong> ', escapeHtml(status || 'error'), '<br/>',
      '<strong>Error:</strong> ', escapeHtml(error || xhr.message || 'Unknown error'), '<br/>',
      '<strong>Error Name:</strong> ', escapeHtml(xhr.name), '<br/>'
    );
    if (xhr.stack) out.push(safePre('Stack Trace', xhr.stack));
    showErrorDialog(out.join(''));
    return;
  }

  // Case 2: Fetch Response
  if (xhr instanceof Response) {
    out.push(
      '<strong>HTTP Status:</strong> ',
      escapeHtml(`${xhr.status} ${xhr.statusText}`),
      '<br/>',
      '<strong>URL:</strong> ',
      escapeHtml(xhr.url),
      '<br/>'
    );

    const body = await readResponseBody(xhr);

    if (body.kind === 'json') {
      const data = body.data || {};
      const msg = data.message || error || xhr.statusText || 'Request failed';

      out.push('<strong>Message:</strong> ', escapeHtml(msg), '<br/>');

      if (data.errors) out.push('<strong>Validation Errors:</strong>', formatLaravelErrors(data.errors));
      if (data.exception) out.push('<br/><strong>Exception:</strong> ', escapeHtml(data.exception));
      if (data.file) out.push('<br/><strong>File:</strong> ', escapeHtml(data.file));
      if (data.line) out.push('<br/><strong>Line:</strong> ', escapeHtml(data.line));

      out.push(safePre('Response JSON', JSON.stringify(data, null, 2)));
    } else if (body.kind === 'text') {
      out.push(
        '<strong>Message:</strong> ',
        escapeHtml(error || xhr.statusText || 'Request failed'),
        '<br/>',
        safePre('Response Body', body.data || 'No response body')
      );
    } else {
      out.push(
        '<strong>Message:</strong> ',
        escapeHtml(error || xhr.statusText || 'Request failed'),
        '<br/>',
        '<strong>Response Body:</strong> Could not be read<br/>'
      );
    }

    showErrorDialog(out.join(''));
    return;
  }

  // Case 3: XHR-ish object
  out.push(
    '<strong>Status:</strong> ', escapeHtml(status || 'error'), '<br/>',
    '<strong>Error:</strong> ', escapeHtml(error || 'Unknown error'), '<br/>',
    '<strong>Status Code:</strong> ', escapeHtml(xhr?.status ?? 'Unknown'), '<br/>'
  );

  const text = xhr?.responseText ?? '';
  if (text) {
    // quick JSON sniff
    const c0 = text[0];
    if (c0 === '{' || c0 === '[') {
      try {
        const json = JSON.parse(text);
        const msg = json.message || error || 'Request failed';
        out.push('<strong>Message:</strong> ', escapeHtml(msg), '<br/>');
        if (json.errors) out.push('<strong>Validation Errors:</strong>', formatLaravelErrors(json.errors));
        out.push(safePre('Response JSON', JSON.stringify(json, null, 2)));
        showErrorDialog(out.join(''));
        return;
      } catch {
        // fall through to plain text
      }
    }
    out.push(safePre('Response Body', text));
  } else {
    out.push(safePre('Response Body', 'No response text'));
  }

  showErrorDialog(out.join(''));
};
