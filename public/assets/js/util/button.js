const SPINNER_HTML =
  '<span class="spinner-border spinner-border-sm align-middle ms-0" aria-hidden="true"></span>' +
  '<span class="visually-hidden">Loading...</span>';

const toIdArray = (buttonIds) => (Array.isArray(buttonIds) ? buttonIds : [buttonIds]).filter(Boolean);

const forEachButtonById = (buttonIds, fn, callerName) => {
  toIdArray(buttonIds).forEach((id) => {
    const btn = document.getElementById(id);
    if (!btn) {
      console.warn(`${callerName}: button with ID "${id}" not found`);
      return;
    }
    fn(btn, id);
  });
};

export const disableButton = (buttonIds, { spinnerHtml = SPINNER_HTML } = {}) => {
  forEachButtonById(
    buttonIds,
    (btn) => {
      if (!btn.dataset.originalText) btn.dataset.originalText = btn.innerHTML;

      btn.disabled = true;

      if (btn.innerHTML !== spinnerHtml) btn.innerHTML = spinnerHtml;
    },
    "disableButton"
  );
};

export const enableButton = (buttonIds) => {
  forEachButtonById(
    buttonIds,
    (btn) => {
      btn.disabled = false;

      const original = btn.dataset.originalText;
      if (original != null) {
        btn.innerHTML = original;
        delete btn.dataset.originalText;
      }
    },
    "enableButton"
  );
};
