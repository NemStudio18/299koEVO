<style>
/* Anti-Spam Honeypot Field Hidden Rule */
.hp-field, input[name="_hp_website"] {
    display: none;
    position: absolute;
    left: -9999px;
    visibility: hidden;
    pointer-events: none;
}

/* Native Password Visibility Toggle Styles */
.password-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.password-input-wrapper input {
    padding-right: 45px;
}

.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    padding: 0;
    margin: 0;
    cursor: pointer;
    color: inherit;
    opacity: 0.6;
    transition: opacity 0.2s, color 0.2s;
    height: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 5;
    box-shadow: none;
}

.toggle-password:hover {
    opacity: 1;
}

.toggle-password i {
    font-size: 1.1rem;
}
</style>

<script>
/**
 * Native Password Visibility Toggle Logic
 */
(function() {
    function initPasswordToggle() {
        document.querySelectorAll('.toggle-password').forEach(function(button) {
            if (button.dataset.initialized) return;
            
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const wrapper = this.closest('.password-input-wrapper');
                const targetId = this.getAttribute('data-target');
                const input = targetId ? document.getElementById(targetId) : (wrapper ? wrapper.querySelector('input') : null);
                const icon = this.querySelector('i') || this.querySelector('svg');
                
                if (input && icon) {
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                }
            });
            
            button.dataset.initialized = 'true';
        });
    }

    // Run on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPasswordToggle);
    } else {
        initPasswordToggle();
    }
})();
</script>
