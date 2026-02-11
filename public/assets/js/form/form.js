export const resetForm = (formId) => {
  const form = document.getElementById(formId);
  if (!form) return;

  form.reset();

  const selects = form.querySelectorAll('.form-select');
  for (const s of selects) {
    s.value = '';
    s.dispatchEvent(new Event('change', { bubbles: true }));
  }

  const invalids = form.querySelectorAll('.is-invalid');
  for (const el of invalids) el.classList.remove('is-invalid');

  const hiddens = form.querySelectorAll('input[type="hidden"]:not([name="csrf_token"])');
  for (const h of hiddens) h.value = '';
};

export const getCsrfToken = (
  metaName = 'csrf-token',
  doc = document
) => doc.querySelector(`meta[name="${metaName}"]`)?.content ?? '';

export const getPageContext = () => {
  const el = document.getElementById('kt_app_body');
  return {
    appId: el?.dataset?.appId ?? null,
    navigationMenuId: el?.dataset?.navigationMenuId ?? null,
  };
};