import { initValidation } from '../util/validation.js';
import { showNotification } from '../util/notifications.js';
import { handleSystemError } from '../util/system-errors.js';
import { disableButton, enableButton, passwordAddOn } from '../form/button.js';

let loginRequestController = null;
let isSubmitting = false;

const DEFAULT_REDIRECT = '/';
const AUTH_ENDPOINT = '/authenticate';

const isAbortError = (err) =>
  err?.name === 'AbortError' || err?.code === DOMException.ABORT_ERR;

const parseMaybeJson = async (response) => {
  const ct = response.headers.get('content-type') || '';
  if (!/\bapplication\/json\b|\+json\b/i.test(ct)) return null;
  try {
    return await response.json();
  } catch {
    return null;
  }
};

const notify = (data, fallbackMessage = 'Login failed') => {
  showNotification( data?.message || fallbackMessage);
};

const getRedirect = (data) =>
  typeof data?.redirect_link === 'string' && data.redirect_link.trim()
    ? data.redirect_link
    : DEFAULT_REDIRECT;

const abortInFlight = () => {
  if (loginRequestController) loginRequestController.abort();
  loginRequestController = new AbortController();
  return loginRequestController.signal;
};

const setSubmittingUI = (submitting) => {
  if (submitting) disableButton('signin');
  else enableButton('signin');
};

document.addEventListener('DOMContentLoaded', () => {
  passwordAddOn();

  initValidation('#login_form', {
    rules: {
      email: { required: true, email: true },
      password: { required: true },
    },
    messages: {
      email: { required: 'Please enter your email' },
      password: { required: 'Please enter your password' },
    },

    submitHandler: async (form) => {
      if (isSubmitting) return;
      isSubmitting = true;

      const signal = abortInFlight();
      setSubmittingUI(true);

      try {
        const formData = new URLSearchParams(new FormData(form));

        const response = await fetch(AUTH_ENDPOINT, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
          },
          signal,
        });

        const data = await parseMaybeJson(response);

        if (data?.success === false) {
          notify(data);
          return;
        }

        if (response.ok && data?.success === true) {
          window.location.assign(getRedirect(data));
          return;
        }

        if (response.ok && data && data.success == null) {
          notify(data, 'Unexpected server response.');
          return;
        }

        await handleSystemError(response, 'error', `Request failed (${response.status})`);
      } catch (err) {
        if (isAbortError(err)) return;
        await handleSystemError(err, 'error', 'Network error');
      } finally {
        setSubmittingUI(false);
        loginRequestController = null;
        isSubmitting = false;
      }
    },
  });
});
