import { handleSystemError } from '../modules/system-errors.js';

export const disableButton = (buttonIds) => {
  const ids = Array.isArray(buttonIds) ? buttonIds : [buttonIds];

  ids.forEach((id) => {
    const btn = document.getElementById(id);
    if (!btn) {
      console.warn(`disableButton: button with ID "${id}" not found`);
      return;
    }

    if (!btn.dataset.originalText) btn.dataset.originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = `<span><span class="spinner-border spinner-border-sm align-middle ms-0"></span></span>`;
  });
};

export const enableButton = (buttonIds) => {
  const ids = Array.isArray(buttonIds) ? buttonIds : [buttonIds];

  ids.forEach((id) => {
    const btn = document.getElementById(id);
    if (!btn) {
      console.warn(`enableButton: button with ID "${id}" not found`);
      return;
    }

    btn.disabled = false;
    if (btn.dataset.originalText) {
      btn.innerHTML = btn.dataset.originalText;
      delete btn.dataset.originalText;
    }
  });
};