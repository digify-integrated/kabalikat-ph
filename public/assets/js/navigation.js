import { setHereClassForMenu } from './util/menu.js';
import { checkNotification } from './util/notifications.js';

document.addEventListener('DOMContentLoaded', () => {
    checkNotification();
    setHereClassForMenu('#kt_app_header_menu', '.menu-item');
});
