import { initValidation } from '../util/validation.js';
import { showNotification } from '../util/notifications.js';
import { passwordAddOn } from '../util/password.js';

document.addEventListener('DOMContentLoaded', () => {
    initValidation();
    passwordAddOn();
});
