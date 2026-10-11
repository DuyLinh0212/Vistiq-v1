import './admin-workspace.js';
import.meta.glob('./resource-library.js', { eager: true });
import './question-bank.js';
import './question-workbook-import.js';
import './question-editor.js';
import './reading-library.js';
import './exam-runner.js';

const adminLoginForm = document.querySelector('[data-admin-login-form]');
if (adminLoginForm) {
    const submit = adminLoginForm.querySelector('[type="submit"]');
    adminLoginForm.addEventListener('submit', () => {
        submit.disabled = true;
        submit.querySelector('[data-login-button-label]').textContent = 'Đang đăng nhập…';
        adminLoginForm.setAttribute('aria-busy', 'true');
    });
    window.addEventListener('pageshow', () => {
        submit.disabled = false;
        submit.querySelector('[data-login-button-label]').textContent = 'Đăng nhập';
        adminLoginForm.removeAttribute('aria-busy');
    });
    const password = adminLoginForm.querySelector('[name="password"]');
    for (const eventName of ['keyup', 'keydown']) {
        password.addEventListener(eventName, event => {
            adminLoginForm.querySelector('[data-caps-lock]').hidden = !event.getModifierState('CapsLock');
        });
    }
}

const syncAuthLightScene = (scene, isOn) => {
    scene.dataset.authLightState = isOn ? 'on' : 'off';
    const status = scene.querySelector('[data-auth-light-status]');

    if (status) {
        status.textContent = isOn
            ? 'Trời đêm · đèn pin đang bật'
            : 'Trời sáng · đèn pin đang tắt';
    }
};

document.addEventListener('change', (event) => {
    if (event.target.matches('.answer-option input[type="radio"]')) {
        const options = event.target.closest('.answer-options');

        options?.querySelectorAll('.answer-option').forEach((option) => {
            option.classList.toggle('is-picked', option.contains(event.target) && event.target.checked);
        });
    }
});

document.addEventListener('click', (event) => {
    const saveButton = event.target.closest('.save-heart, .save-button, .bookmark-button');

    if (saveButton) {
        event.preventDefault();
        saveButton.classList.toggle('is-saved');
        saveButton.setAttribute('aria-pressed', String(saveButton.classList.contains('is-saved')));

        if (saveButton.classList.contains('save-button')) {
            saveButton.innerHTML = saveButton.classList.contains('is-saved')
                ? '<svg class="dictionary-icon app-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"></path></svg> <span>Đã lưu vào kho kiến thức</span>'
                : '<svg class="dictionary-icon app-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"></path></svg> <span>Lưu vào kho kiến thức</span>';
        }
    }

    const passwordButton = event.target.closest('.password-input button, [data-password-toggle]');

    if (passwordButton) {
        const controlledInput = passwordButton.getAttribute('aria-controls');
        const passwordInput = controlledInput
            ? document.getElementById(controlledInput)
            : passwordButton.parentElement.querySelector('input');

        if (!passwordInput) {
            return;
        }

        const isVisible = passwordInput.type === 'text';
        const isNowVisible = !isVisible;

        passwordInput.type = isNowVisible ? 'text' : 'password';
        passwordButton.setAttribute('aria-label', isVisible ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
        passwordButton.setAttribute('aria-pressed', String(isNowVisible));
        passwordButton.closest('.auth-password-wrap')?.classList.toggle('is-light-on', isNowVisible);

        const authShell = passwordButton.closest('.auth-shell');
        const authScene = authShell?.querySelector('[data-auth-light-scene]');
        const hasVisiblePassword = [...(authShell?.querySelectorAll('[data-password-toggle]') ?? [])].some((toggle) => {
            const inputId = toggle.getAttribute('aria-controls');

            return inputId && document.getElementById(inputId)?.type === 'text';
        });

        authShell?.classList.toggle('is-flashlight-on', hasVisiblePassword);

        if (authScene) {
            syncAuthLightScene(authScene, hasVisiblePassword);
        }
    }
});
