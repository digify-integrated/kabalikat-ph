export const passwordAddOn = () => {
  const addons = document.querySelectorAll('.password-addon');

  addons.forEach(addon => {
    const inputField = addon.previousElementSibling; // the input before the span
    const eyeIcon = addon.querySelector('i');

    const updateIcon = () => {
      if (inputField.type === 'password') {
        eyeIcon.classList.remove('ri-eye-off-line');
        eyeIcon.classList.add('ri-eye-line');
      }
      else {
        eyeIcon.classList.remove('ri-eye-line');
        eyeIcon.classList.add('ri-eye-off-line');
      }
    };

    updateIcon();

    addon.setAttribute('tabindex', '99999');
    addon.setAttribute('role', 'button');
    addon.setAttribute('aria-label', 'Toggle password visibility');

    const togglePassword = () => {
      inputField.type = inputField.type === 'password' ? 'text' : 'password';
      eyeIcon.classList.toggle('ri-eye-line');
      eyeIcon.classList.toggle('ri-eye-off-line');
    };

    addon.addEventListener('click', togglePassword);
    addon.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        togglePassword();
      }
    });
  });
};
