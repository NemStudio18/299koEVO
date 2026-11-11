document.addEventListener('DOMContentLoaded', function () {
    // Helper for validation display
    function updateValidation(inputId, validationId, rules) {
        const input = document.getElementById(inputId);
        const validation = document.getElementById(validationId);
        if (!input || !validation) return;

        input.addEventListener('input', function () {
            const value = this.value;
            let message = '';
            let isValid = true;

            if (value.length > 0) {
                for (const rule of rules) {
                    if (!rule.test(value, this)) {
                        message = '✗ ' + rule.message;
                        isValid = false;
                        break;
                    }
                }
                if (isValid) {
                    message = '✓ ' + (rules[0].success || 'Valide');
                }
            }

            validation.textContent = message;
            validation.className = 'field-validation ' + (value.length === 0 ? '' : (isValid ? 'valid' : 'invalid'));
        });
    }

    // Login Validations
    updateValidation('adminEmail', 'email-validation', [
        { test: (v, i) => i.validity.valid, message: 'Veuillez entrer un email valide', success: 'Email valide' }
    ]);
    updateValidation('adminPwd', 'password-validation', [
        { test: (v) => v.length > 0, message: 'Mot de passe requis', success: 'Mot de passe saisi' }
    ]);

    // Registration Validations
    updateValidation('register-username', 'username-validation', [
        { test: (v) => v.length >= 3, message: 'Au moins 3 caractères requis' },
        { test: (v) => v.length <= 60, message: 'Maximum 60 caractères' }
    ]);
    updateValidation('register-email', 'email-validation', [
        { test: (v, i) => i.validity.valid, message: 'Veuillez entrer un email valide' }
    ]);

    const regPwd = document.getElementById('register-password');
    const regPwdConf = document.getElementById('register-password-confirm');
    const regPwdReq = document.getElementById('req-length');

    if (regPwd) {
        regPwd.addEventListener('input', function () {
            const length = this.value.length;
            if (regPwdReq) regPwdReq.className = 'requirement ' + (length >= 8 ? 'valid' : 'invalid');

            const validation = document.getElementById('password-validation');
            if (validation) {
                if (length === 0) {
                    validation.textContent = '';
                } else if (length < 8) {
                    validation.textContent = '✗ Au moins 8 caractères requis (' + length + '/8)';
                    validation.className = 'field-validation invalid';
                } else {
                    validation.textContent = '✓ Mot de passe valide';
                    validation.className = 'field-validation valid';
                }
            }
            if (regPwdConf) fireEvent(regPwdConf, 'input');
        });
    }

    if (regPwdConf && regPwd) {
        updateValidation('register-password-confirm', 'password-confirm-validation', [
            { test: (v) => v === regPwd.value, message: 'Les mots de passe ne correspondent pas', success: 'Les mots de passe correspondent' }
        ]);
    }

    function fireEvent(element, eventName) {
        const event = new Event(eventName, { bubbles: true });
        element.dispatchEvent(event);
    }

    // Spoiler / Help Toggle
    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('.auth-spoiler-toggle');
        if (toggle) {
            const spoiler = toggle.closest('.auth-spoiler');
            const type = spoiler.dataset.type;

            if (type === 'accordion') {
                spoiler.classList.toggle('active');
            } else if (type === 'modal') {
                const modalId = spoiler.dataset.modal;
                const modal = document.getElementById(modalId);
                if (modal) modal.style.display = 'flex';
            }
        }

        if (e.target.closest('.auth-modal-close') || e.target.classList.contains('auth-modal')) {
            const modal = e.target.closest('.auth-modal');
            if (modal) modal.style.display = 'none';
        }
    });
});

