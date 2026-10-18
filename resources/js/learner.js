import './confirmation-dialog.js';
import './ui-feedback.js';
import './exam-runner.js';
import './personal-library.js';
import { initializeDictionarySearch } from './dictionary-search.js';

initializeDictionarySearch();

const examCatalog = document.querySelector('[data-exam-catalog]');

if (examCatalog) {
    const filterForm = examCatalog.querySelector('[data-exam-filter-form]');
    const examTypeFilter = filterForm.querySelector('[data-exam-type-filter]');

    examCatalog.querySelectorAll('[data-exam-tab]').forEach(tab => {
        tab.addEventListener('click', () => {
            examTypeFilter.value = tab.dataset.examTab;
            filterForm.requestSubmit();
        });
    });

    filterForm.querySelectorAll('[data-submit-on-change]').forEach(select => {
        select.addEventListener('change', () => filterForm.requestSubmit());
    });
}

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
    }
});
