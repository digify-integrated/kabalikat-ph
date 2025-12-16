'use strict';

export function initValidation(selector = '.needs-validation') {
    const forms = document.querySelectorAll(selector);

    if (!forms.length) {
        return;
    }

    Array.prototype.forEach.call(forms, function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
}
