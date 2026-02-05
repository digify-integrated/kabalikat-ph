'use strict';

export const passwordAddOn = (selector = '.password-addon') => {
  const addons = document.querySelectorAll(selector);
  if (!addons.length) return;

  addons.forEach((addon) => {
    // Prefer explicit target if provided: <span class="password-addon" data-target="#password">
    const targetSel = addon.getAttribute('data-target');
    const input =
      (targetSel && document.querySelector(targetSel)) ||
      addon.previousElementSibling;

    if (!input || input.tagName !== 'INPUT') return;

    // Support a few common icon patterns
    const icon =
      addon.querySelector('i') ||
      addon.querySelector('svg') ||
      addon;

    const CLASS_EYE = 'ki-eye';
    const CLASS_EYE_OFF = 'ki-eye-slash';

    const setA11y = () => {
      addon.setAttribute('role', 'button');
      addon.setAttribute('tabindex', '0'); // normal keyboard navigation
      addon.setAttribute('aria-label', 'Toggle password visibility');
      addon.setAttribute('aria-controls', input.id || '');

      // pressed=true means "currently showing password"
      addon.setAttribute('aria-pressed', input.type !== 'password' ? 'true' : 'false');
    };

    const updateIcon = () => {
      if (!icon?.classList) return;

      const showing = input.type !== 'password';
      icon.classList.toggle(CLASS_EYE, !showing);
      icon.classList.toggle(CLASS_EYE_OFF, showing);
    };

    const toggle = () => {
      // Some browsers may prevent changing type in rare cases; guard anyway.
      try {
        input.type = input.type === 'password' ? 'text' : 'password';
      } catch {
        return;
      }

      updateIcon();
      addon.setAttribute('aria-pressed', input.type !== 'password' ? 'true' : 'false');

      // Keep typing flow uninterrupted
      input.focus({ preventScroll: true });
    };

    // Initialize
    setA11y();
    updateIcon();

    // Prevent double-binding
    if (addon.dataset.passwordAddonBound === 'true') return;
    addon.dataset.passwordAddonBound = 'true';

    addon.addEventListener('click', (e) => {
      e.preventDefault();
      toggle();
    });

    addon.addEventListener('keydown', (e) => {
      // Enter or Space should activate buttons
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggle();
      }
    });
  });
};
