const VALID_TYPES = new Set(['success', 'info', 'warning', 'error']);

const STORAGE_KEY = 'notificationPayload';

let toastrConfigured = false;
const ensureToastrConfigured = () => {
  if (toastrConfigured) return;
  toastrConfigured = true;

  toastr.options = {
    closeButton: true,
    progressBar: true,
    preventDuplicates: true,
    positionClass: 'toastr-top-right',
    timeOut: 2000,
  };
};

export const showNotification = (message, type = 'error', timeOut) => {
  ensureToastrConfigured();

  if (!VALID_TYPES.has(type)) {
    console.error(`Invalid toastr type: ${type}`);
    type = 'info';
  }

  if (typeof timeOut === 'number') toastr.options.timeOut = timeOut;

  toastr[type](String(message ?? ''), '');
};

export const setNotification = (message, type = 'info', timeOut) => {
  const payload = { message, type, timeOut };
  sessionStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
};

export const checkNotification = () => {
  const raw = sessionStorage.getItem(STORAGE_KEY);
  if (!raw) return;

  sessionStorage.removeItem(STORAGE_KEY);

  try {
    const { message, type, timeOut } = JSON.parse(raw) || {};
    if (message != null && type) showNotification(message, type, timeOut);
  } catch (e) {
    console.error('Failed to parse notification payload.', e);
  }
};
