import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import { TableKit } from '@tiptap/extension-table';

const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
const encode = value => String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character]);

document.querySelectorAll('[data-admin-toast]').forEach(toast => {
    const dismiss = () => {
        toast.classList.add('is-leaving');
        window.setTimeout(() => toast.remove(), 240);
    };

    toast.querySelector('[data-dismiss-admin-toast]')?.addEventListener('click', dismiss);
    const timeout = Number(toast.dataset.adminToastTimeout ?? 6000);
    if (timeout > 0) {
        window.setTimeout(dismiss, timeout);
    }
});

const adminConfirmDialog = document.querySelector('[data-admin-confirm-dialog]');
const adminConfirmPanel = adminConfirmDialog?.querySelector('[role="dialog"]');
const adminConfirmTitle = adminConfirmDialog?.querySelector('#admin-confirm-title');
const adminConfirmMessage = adminConfirmDialog?.querySelector('#admin-confirm-message');
const adminConfirmSubmit = adminConfirmDialog?.querySelector('[data-admin-confirm-submit]');
const adminConfirmCancel = adminConfirmDialog?.querySelectorAll('[data-admin-confirm-cancel]') ?? [];
let pendingAdminConfirmation = null;
let adminConfirmPreviousFocus = null;

const finishAdminConfirmation = confirmed => {
    if (!pendingAdminConfirmation) {
        return;
    }

    const { resolve } = pendingAdminConfirmation;
    pendingAdminConfirmation = null;
    adminConfirmDialog.hidden = true;
    document.body.classList.remove('admin-confirm-open');
    try {
        adminConfirmPreviousFocus?.focus({ preventScroll: true });
    } catch {}
    adminConfirmPreviousFocus = null;
    resolve(confirmed);
};

const requestAdminConfirmation = (message, options = {}) => {
    if (!adminConfirmDialog || !adminConfirmPanel || !adminConfirmTitle || !adminConfirmSubmit || !adminConfirmMessage) {
        return Promise.resolve(window.confirm(message));
    }

    if (pendingAdminConfirmation) {
        finishAdminConfirmation(false);
    }

    adminConfirmTitle.textContent = options.title ?? 'Xác nhận thao tác';
    adminConfirmMessage.textContent = message;
    adminConfirmSubmit.textContent = options.confirmLabel ?? 'Xác nhận';
    adminConfirmSubmit.classList.toggle('button-danger', options.danger !== false);
    adminConfirmSubmit.classList.toggle('button-primary', options.danger === false);
    adminConfirmPreviousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    adminConfirmDialog.hidden = false;
    document.body.classList.add('admin-confirm-open');

    return new Promise(resolve => {
        pendingAdminConfirmation = { resolve };
        window.requestAnimationFrame(() => adminConfirmCancel[0]?.focus());
    });
};

window.vistiqConfirm = requestAdminConfirmation;
adminConfirmCancel.forEach(control => control.addEventListener('click', () => finishAdminConfirmation(false)));
adminConfirmSubmit?.addEventListener('click', () => finishAdminConfirmation(true));
document.addEventListener('keydown', event => {
    if (!pendingAdminConfirmation || adminConfirmDialog?.hidden) {
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        finishAdminConfirmation(false);
        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    const focusable = [adminConfirmCancel[0], adminConfirmSubmit].filter(control => control instanceof HTMLElement && !control.disabled);
    if (focusable.length < 2) {
        return;
    }

    const currentIndex = focusable.indexOf(document.activeElement);
    const nextIndex = event.shiftKey
        ? (currentIndex <= 0 ? focusable.length - 1 : currentIndex - 1)
        : (currentIndex === focusable.length - 1 ? 0 : currentIndex + 1);
    event.preventDefault();
    focusable[nextIndex].focus();
});

document.querySelectorAll('[data-rich-editor]').forEach(textarea => {
    const wrapper = document.createElement('div'), toolbar = document.createElement('div'), surface = document.createElement('div');
    wrapper.className = 'rich-editor'; toolbar.className = 'assessment-actions'; surface.className = 'rich-editor-surface';
    textarea.before(wrapper); wrapper.append(toolbar, surface, textarea); textarea.hidden = true;
    const editor = new Editor({ element: surface, extensions: [StarterKit, Image, TableKit.configure({ table: { resizable: true } })], content: textarea.value, onUpdate: ({ editor: instance }) => { textarea.value = instance.getHTML(); textarea.dispatchEvent(new Event('input', { bubbles: true })); } });
    const actions = { 'Đậm': () => editor.chain().focus().toggleBold().run(), 'Nghiêng': () => editor.chain().focus().toggleItalic().run(), 'Heading': () => editor.chain().focus().toggleHeading({ level: 2 }).run(), 'Đoạn văn': () => editor.chain().focus().setParagraph().run(), 'Danh sách': () => editor.chain().focus().toggleBulletList().run(), 'Bảng': () => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(), 'Ảnh': () => { const src = prompt('URL ảnh HTTPS hoặc đường dẫn media của dự án'); if (src && (/^https:\/\//.test(src) || src.startsWith('/'))) { editor.chain().focus().setImage({ src }).run(); } }, 'Paragraph label': () => { const label = prompt('Nhãn đoạn (A, B, C...)'); if (label) { editor.chain().focus().insertContent({ type: 'paragraph', content: [{ type: 'text', text: `${label}. ` }] }).run(); } }, 'HTML / Text': () => { textarea.hidden = !textarea.hidden; surface.hidden = !textarea.hidden; } };
    Object.entries(actions).forEach(([label, action]) => { const button = document.createElement('button'); button.type = 'button'; button.className = 'button button-quiet'; button.textContent = label; button.addEventListener('click', action); toolbar.append(button); });
    textarea.addEventListener('change', () => editor.commands.setContent(textarea.value, { emitUpdate: false }));
});

document.querySelectorAll('[data-unsaved-form]').forEach(form => {
    let dirty = false, saving = false;
    form.addEventListener('input', () => { dirty = true; }); form.addEventListener('change', () => { dirty = true; });
    form.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });
    if (!form.hasAttribute('data-autosave')) { return; }
    setInterval(async () => {
        if (!form.hasAttribute('data-autosave') || !dirty || saving || !navigator.onLine) { return; }
        saving = true; const data = new FormData(form), sent = new URLSearchParams(data).toString(), status = form.querySelector('[data-autosave-status]') ?? document.querySelector('[data-autosave-status]');
        try {
            const response = await fetch(form.action, { method: 'POST', body: data, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
            const result = await response.json(); if (!response.ok) { throw new Error(result.message ?? 'Không thể lưu'); }
            if (new URLSearchParams(new FormData(form)).toString() === sent) { dirty = false; }
            form.querySelector('[name="revision_counter"]').value = result.revision_counter; if (status) { status.textContent = 'Đã lưu draft'; }
        } catch (error) { if (status) { status.textContent = error.message; } form.removeAttribute('data-autosave'); }
        finally { saving = false; }
    }, 5000);
});

document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', async event => {
    if (form.dataset.confirmApproved === 'true') {
        delete form.dataset.confirmApproved;
        return;
    }

    event.preventDefault();
    if (form.dataset.confirmPending === 'true') {
        return;
    }

    form.dataset.confirmPending = 'true';
    const confirmed = await requestAdminConfirmation(form.dataset.confirm, {
        title: form.dataset.confirmTitle,
        confirmLabel: form.dataset.confirmLabel,
        danger: form.dataset.confirmTone !== 'safe',
    });
    delete form.dataset.confirmPending;

    if (!confirmed) {
        return;
    }

    form.dataset.confirmApproved = 'true';
    if (event.submitter instanceof HTMLElement) {
        form.requestSubmit(event.submitter);
    } else {
        form.requestSubmit();
    }
    form.querySelectorAll('button[type="submit"], input[type="submit"], button:not([type])').forEach(control => { control.disabled = true; });
}));

const questionBulkForm = document.querySelector('#question-bulk');
const questionBulkEndpoint = questionBulkForm?.getAttribute('action');
questionBulkForm?.querySelector('[data-delete-selected-questions]')?.addEventListener('click', () => {
    if (!document.querySelector('input[form="question-bulk"][name="ids[]"]:checked') && !questionBulkForm.querySelector('input[name="matching_ids[]"]')) {
        alert('Hãy chọn ít nhất một câu hỏi cần xóa.');
        return;
    }

    questionBulkForm.querySelector('[name="action"]').value = 'delete_unused';
    questionBulkForm.requestSubmit();
});
questionBulkForm?.addEventListener('submit', async event => {
    const action = questionBulkForm.querySelector('[name="action"]');
    if (action.value !== 'delete_unused') {
        return;
    }

    event.preventDefault();

    const initialData = new FormData(questionBulkForm);
    const questionIds = [...new Set([...initialData.entries()]
        .filter(([name]) => name === 'ids[]' || name === 'matching_ids[]')
        .map(([, value]) => String(value)))];
    const progress = document.querySelector('[data-delete-progress]');
    const progressBar = progress?.querySelector('[data-delete-progress-bar]');
    const progressLabel = progress?.querySelector('[data-delete-progress-label]');
    const progressCount = progress?.querySelector('[data-delete-progress-count]');

    if (questionIds.length === 0) {
        action.value = '';
        alert('Hãy chọn ít nhất một câu hỏi cần xóa.');
        return;
    }

    if (questionBulkForm.dataset.confirmPending === 'true') {
        return;
    }

    questionBulkForm.dataset.confirmPending = 'true';
    const confirmed = await requestAdminConfirmation('Xóa các câu đã chọn chưa được dùng trong đề? Câu đã xuất bản hoặc đang được dùng sẽ được giữ lại.', {
        title: 'Xác nhận xóa câu hỏi',
        confirmLabel: 'Xóa câu hỏi',
    });
    delete questionBulkForm.dataset.confirmPending;

    if (!confirmed) {
        action.value = '';
        return;
    }

    if (progress) {
        progress.hidden = false;
    }

    questionBulkForm.querySelectorAll('button, input, select').forEach(control => { control.disabled = true; });
    document.querySelectorAll('input[form="question-bulk"][name="ids[]"], [data-select-all]').forEach(control => { control.disabled = true; });

    const operationKey = crypto.randomUUID();
    const batchSize = 10;
    const deleteNextBatch = async offset => {
        const batchIds = questionIds.slice(offset, offset + batchSize);
        const batchData = new FormData();
        initialData.forEach((value, name) => batchData.append(name, value));
        ['ids[]', 'matching_ids[]', 'operation_key', 'delete_offset', 'selection_total', 'final_batch'].forEach(name => batchData.delete(name));
        batchIds.forEach(id => batchData.append('ids[]', id));
        batchData.append('action', 'delete_unused');
        batchData.append('operation_key', operationKey);
        batchData.append('delete_offset', String(offset));
        batchData.append('selection_total', String(questionIds.length));
        const isFinalBatch = offset + batchIds.length === questionIds.length;
        batchData.append('final_batch', isFinalBatch ? '1' : '0');

        const response = await fetch(questionBulkEndpoint, {
            method: 'POST',
            body: batchData,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(result.message ?? 'Không thể xóa câu hỏi.');
        }

        const percent = Math.round((result.processed / result.total) * 100);
        if (progressBar) {
            progressBar.value = percent;
        }
        if (progressCount) {
            progressCount.textContent = `${percent}%`;
        }
        if (progressLabel) {
            progressLabel.textContent = `Đã xử lý ${result.processed}/${result.total} câu · xóa ${result.deleted}, bỏ qua ${result.skipped}`;
        }

        if (result.completed) {
            window.location.reload();
            return;
        }

        await deleteNextBatch(offset + batchIds.length);
    };

    deleteNextBatch(0).catch(error => {
        if (progressLabel) {
            progressLabel.textContent = `${error.message} Hãy tải lại trang để kiểm tra kết quả.`;
        }
        if (progressCount) {
            progressCount.textContent = 'Có lỗi';
        }
        if (progress) {
            progress.setAttribute('data-delete-error', 'true');
        }
    });
});

let questionSearchTimer;

document.querySelectorAll('[data-question-search]').forEach(form => {
    const search = form.querySelector('[name="q"]');

    search.addEventListener('input', () => {
        window.clearTimeout(questionSearchTimer);
        questionSearchTimer = window.setTimeout(() => form.requestSubmit(), 450);
    });

    form.addEventListener('submit', () => window.clearTimeout(questionSearchTimer));
});

document.querySelectorAll('[data-question-filters]').forEach(form => {
    const catalog = JSON.parse(form.querySelector('[data-question-filter-catalog]').textContent);
    const exam = form.querySelector('[name="exam"]');
    const part = form.querySelector('[name="part"]');
    const type = form.querySelector('[name="type"]');
    const query = form.querySelector('[name="q"]');
    const search = document.querySelector('[data-question-search] [name="q"]');
    const replaceOptions = (select, options, placeholder) => {
        const selectedValue = select.value;
        select.replaceChildren(new Option(placeholder, ''), ...options.map(option => new Option(option.name, option.code)));
        select.value = options.some(option => option.code === selectedValue) ? selectedValue : '';
    };
    const availableParts = () => catalog.parts.filter(option => !exam.value || option.exam === exam.value);
    const updateTypes = () => {
        const allowedTypes = new Set(availableParts().filter(option => !part.value || option.code === part.value).flatMap(option => option.types));
        replaceOptions(type, catalog.types.filter(option => allowedTypes.has(option.code)), 'Tất cả loại câu hỏi');
    };
    const updateParts = () => {
        replaceOptions(part, availableParts(), 'Tất cả Part / kỹ năng');
        updateTypes();
    };
    form.querySelectorAll('select').forEach(select => select.addEventListener('change', () => {
        if (select === exam) {
            updateParts();
        } else if (select === part) {
            updateTypes();
        }

        if (search && query) {
            query.value = search.value;
        }

        window.clearTimeout(questionSearchTimer);
        form.requestSubmit();
    }));
    updateParts();
});

const questionEditor = document.querySelector('[data-question-editor]');
if (questionEditor) {
    const exam = questionEditor.querySelector('[data-editor-exam]'), skill = questionEditor.querySelector('[data-editor-skill]'), part = questionEditor.querySelector('[data-editor-part]'), type = questionEditor.querySelector('[data-question-type]');
    if (part.selectedOptions[0]) { exam.value = part.selectedOptions[0].dataset.exam; skill.value = part.selectedOptions[0].dataset.skill; }
    const filterParts = () => { [...part.options].forEach(option => { option.hidden = option.dataset.exam !== exam.value || option.dataset.skill !== skill.value; }); if (part.selectedOptions[0]?.hidden) { part.value = [...part.options].find(option => !option.hidden)?.value ?? ''; } };
    const changeType = () => {
        const code = type.selectedOptions[0].dataset.code;
        const family = ['multiple_choice', 'multiple_response', 'true_false_not_given', 'yes_no_not_given'].includes(code) ? 'choice' : code.startsWith('matching') || code === 'matching' ? 'matching' : ['essay', 'speaking'].includes(code) ? code : 'text';
        questionEditor.querySelectorAll('[data-type-family]').forEach(element => { const visible = element.dataset.typeFamily.split(' ').includes(family); element.hidden = !visible; element.querySelectorAll('input, textarea').forEach(input => { input.disabled = !visible; }); if (element.matches('textarea')) { element.disabled = !visible; } });
        questionEditor.querySelectorAll('[data-choice-answer]').forEach(element => { element.hidden = family !== 'choice'; element.querySelector('input').disabled = family !== 'choice'; });
    };
    const filterTypes = () => {
        const examCode = part.selectedOptions[0]?.dataset.examCode, currentSkill = part.selectedOptions[0]?.dataset.skill;
        [...type.options].forEach(option => {
            const code = option.dataset.code;
            option.hidden = examCode === 'toeic' ? code !== 'multiple_choice' : currentSkill === 'writing' ? code !== 'essay' : currentSkill === 'speaking' ? code !== 'speaking' : ['essay', 'speaking'].includes(code);
        });
        if (type.selectedOptions[0]?.hidden) { type.value = [...type.options].find(option => !option.hidden)?.value ?? ''; }
        changeType();
    };
    const updateCatalog = () => { filterParts(); filterTypes(); };
    exam.addEventListener('change', updateCatalog); skill.addEventListener('change', updateCatalog); part.addEventListener('change', filterTypes); type.addEventListener('change', changeType); updateCatalog();

    const addOption = questionEditor.querySelector('[data-add-question-option]');
    addOption?.addEventListener('click', () => {
        const next = [...questionEditor.querySelectorAll('[data-option-row]')].find(row => row.hidden);
        if (next) { next.hidden = false; next.querySelector('input[type="text"], input:not([type])').focus(); }
        if (![...questionEditor.querySelectorAll('[data-option-row]')].some(row => row.hidden)) { addOption.hidden = true; }
    });

    const imageSelect = questionEditor.querySelector('[data-image-select]'), audioSelect = questionEditor.querySelector('[data-audio-select]');
    const updatePreview = (select, selector, isImage) => {
        const preview = questionEditor.querySelector(selector), url = select.selectedOptions[0]?.dataset.url;
        preview.hidden = !url;
        if (!url) { return; }
        const media = preview.querySelector(isImage ? 'img' : 'audio');
        media.src = url;
    };
    imageSelect?.addEventListener('change', () => updatePreview(imageSelect, '[data-image-preview]', true));
    audioSelect?.addEventListener('change', () => updatePreview(audioSelect, '[data-audio-preview]', false));
}

const mediaUpload = document.querySelector('[data-media-upload]');
if (mediaUpload) {
    const fileInput = mediaUpload.querySelector('[data-media-file]'), button = mediaUpload.querySelector('[data-upload-media]'), status = mediaUpload.querySelector('[data-media-upload-status]');
    button.addEventListener('click', () => {
        const file = fileInput.files[0];
        if (!file) { status.textContent = 'Chọn ảnh hoặc audio trước.'; return; }
        const data = new FormData();
        data.append('file', file); data.append('title', file.name); data.append('question_id', mediaUpload.dataset.questionId);
        const token = questionEditor.querySelector('[name="_token"]').value;
        const request = new XMLHttpRequest();
        button.disabled = true; status.textContent = 'Đang upload…';
        request.open('POST', mediaUpload.dataset.uploadUrl);
        request.setRequestHeader('Accept', 'application/json'); request.setRequestHeader('X-CSRF-TOKEN', token);
        request.addEventListener('load', () => {
            button.disabled = false;
            if (request.status < 200 || request.status >= 300) {
                let error = 'Upload thất bại. Kiểm tra cấu hình lưu trữ và thử lại.';
                try { error = JSON.parse(request.responseText).message ?? error; } catch { /* Keep the concise upload error. */ }
                status.textContent = error;
                return;
            }
            const asset = JSON.parse(request.responseText), isImage = file.type.startsWith('image/');
            const select = questionEditor.querySelector(isImage ? '[data-image-select]' : '[data-audio-select]');
            const option = new Option(file.name, asset.id, true, true); option.dataset.url = asset.url; select.add(option); select.value = String(asset.id); select.dispatchEvent(new Event('change', { bubbles: true }));
            status.textContent = isImage ? 'Ảnh đã lưu trên Cloudinary.' : 'Audio đã lưu trên R2.'; fileInput.value = '';
        });
        request.upload.addEventListener('progress', event => { if (event.lengthComputable) { status.textContent = `Đang upload ${Math.round((event.loaded / event.total) * 100)}%…`; } });
        request.addEventListener('error', () => { button.disabled = false; status.textContent = 'Mất kết nối khi upload. Thử lại.'; });
        request.send(data);
    });
}

const questionImport = document.querySelector('[data-question-import]');
if (questionImport) {
    const progress = questionImport.querySelector('[data-import-progress]');
    const bar = questionImport.querySelector('[data-import-progress-bar]');
    const label = questionImport.querySelector('[data-import-progress-label]');
    const count = questionImport.querySelector('[data-import-progress-count]');
    const feedback = questionImport.querySelector('[data-import-feedback]');
    const submit = questionImport.querySelector('button[type="submit"]');

    questionImport.addEventListener('submit', event => {
        event.preventDefault();
        if (!questionImport.reportValidity()) { return; }

        const token = crypto.randomUUID();
        questionImport.querySelector('[name="import_key"]').value = token;
        questionImport.querySelector('[name="offset"]').value = '0';
        progress.hidden = false;
        feedback.hidden = true;
        submit.disabled = true;
        label.textContent = 'Đang tải file CSV…';

        const updateProgress = value => {
            const percent = Math.max(0, Math.min(99, Math.round(value)));
            bar.value = percent;
            count.textContent = `${percent}%`;
        };

        const showFailure = message => {
            submit.disabled = false;
            feedback.hidden = false;
            feedback.textContent = message;
            label.textContent = 'Import bị gián đoạn';
        };

        const sendBatch = offset => {
            questionImport.querySelector('[name="offset"]').value = String(offset);
            const request = new XMLHttpRequest();
            const formData = new FormData(questionImport);

            request.open('POST', questionImport.action);
            request.setRequestHeader('Accept', 'application/json');
            if (offset === 0) {
                request.upload.addEventListener('progress', event => {
                    if (event.lengthComputable) { updateProgress((event.loaded / event.total) * 20); }
                });
                request.upload.addEventListener('load', () => { label.textContent = 'Đang phân tích và lưu câu hỏi…'; });
            }
            request.addEventListener('load', () => {
                let result;
                try { result = JSON.parse(request.responseText); }
                catch { showFailure('Máy chủ không trả về kết quả import hợp lệ. Hãy kiểm tra log trước khi tải lại file để tránh nhập trùng.'); return; }

                if (request.status >= 200 && request.status < 300 && result.status === 'processing') {
                    label.textContent = `Đang lưu câu hỏi ${result.processed} / ${result.total}…`;
                    updateProgress(20 + (result.processed / result.total) * 75);
                    sendBatch(result.next_offset);
                    return;
                }

                if (request.status >= 200 && request.status < 300 && result.summary) {
                    bar.value = 100;
                    count.textContent = '100%';
                    label.textContent = `Hoàn tất: ${result.summary.imported} câu hỏi đã nhập.`;
                    window.location.assign(result.redirect);
                    return;
                }

                showFailure(result.message ?? Object.values(result.errors ?? {}).flat().join(' ') ?? 'Import không hoàn tất. Hãy kiểm tra file CSV và thử lại.');
            });
            request.addEventListener('error', () => showFailure('Mất kết nối trong khi import. Một phần câu hỏi có thể đã được lưu; hãy kiểm tra trạng thái trước khi tải lại file.'));
            request.send(formData);
        };

        sendBatch(0);
    });
}

const builder = document.querySelector('[data-exam-builder]');
if (builder) {
    const catalog = JSON.parse(builder.querySelector('[data-builder-catalog]').textContent), serialized = builder.querySelector('[data-sections-json]');
    let sections = JSON.parse(serialized.value || '[]');
    const sync = () => { serialized.value = JSON.stringify(sections); builder.dispatchEvent(new Event('input', { bubbles: true })); builder.querySelector('[data-order-summary]').innerHTML = sections.map((section, index) => `<p>${index + 1}. ${encode(catalog.parts.find(part => part.id === Number(section.exam_part_id))?.name)} · ${section.question_ids.map(id => `#${id}`).join(' → ')}</p>`).join(''); };
    const render = (markDirty = true) => {
        const examId = Number(builder.querySelector('[data-exam-type]').value);
        builder.querySelector('[data-builder-sections]').innerHTML = sections.map((section, index) => `<article class="panel" data-section-index="${index}"><div class="assessment-actions"><label>Part<select data-builder-part>${catalog.parts.filter(part => part.type === examId).map(part => `<option value="${part.id}" ${part.id === Number(section.exam_part_id) ? 'selected' : ''}>${encode(part.skill)} · ${encode(part.name)}</option>`).join('')}</select></label><label>Phút trong phần (Mini/Skill)<input type="number" data-section-duration min="0" max="240" value="${section.duration_minutes ?? 0}"></label><button type="button" data-section-up>↑ Phần</button><button type="button" data-section-down>↓ Phần</button><button type="button" data-remove-section>Xóa phần</button></div><label>Thêm câu hỏi<select data-add-question><option value="">Chọn câu</option>${catalog.questions.filter(question => question.part === Number(section.exam_part_id)).map(question => `<option value="${question.id}">#${question.id} · ${encode(question.status)} · ${encode(question.prompt.slice(0, 100))}</option>`).join('')}</select></label><ol>${section.question_ids.map((id, order) => `<li draggable="true" data-question-order="${order}">#${id} · ${encode(catalog.questions.find(question => question.id === Number(id))?.prompt.slice(0, 90))} <button type="button" data-order-up aria-label="Lên">↑</button><button type="button" data-order-down aria-label="Xuống">↓</button><button type="button" data-order-remove>Xóa</button></li>`).join('')}</ol></article>`).join(''); if (markDirty) { sync(); }
    };
    builder.querySelector('[data-add-section]').addEventListener('click', () => { const part = catalog.parts.find(part => part.type === Number(builder.querySelector('[data-exam-type]').value)); if (part) { sections.push({ exam_part_id: part.id, question_ids: [] }); render(); } });
    builder.querySelector('[data-exam-type]').addEventListener('change', () => { render(); });
    builder.addEventListener('change', event => {
        const row = event.target.closest('[data-section-index]'); if (!row) { return; } const section = sections[Number(row.dataset.sectionIndex)];
        if (event.target.matches('[data-builder-part]')) { section.exam_part_id = Number(event.target.value); section.question_ids = []; render(); }
        if (event.target.matches('[data-section-duration]')) { const duration = Number(event.target.value); if (duration > 0) { section.duration_minutes = duration; } else { delete section.duration_minutes; } sync(); }
        if (event.target.matches('[data-add-question]') && event.target.value) { const id = Number(event.target.value); if (sections.some(part => part.question_ids.includes(id))) { alert('Câu hỏi đã có trong đề.'); } else { section.question_ids.push(id); render(); } }
    });
    builder.addEventListener('click', event => {
        const button = event.target.closest('button'); if (!button) { return; }
        if (button.dataset.wizardStep !== undefined) { builder.querySelectorAll('[data-wizard-panel]').forEach(panel => { panel.hidden = panel.dataset.wizardPanel !== button.dataset.wizardStep; }); }
        const row = button.closest('[data-section-index]'); if (!row) { return; } const index = Number(row.dataset.sectionIndex), section = sections[index], order = Number(button.closest('[data-question-order]')?.dataset.questionOrder);
        if (button.matches('[data-remove-section]')) { sections.splice(index, 1); }
        if (button.matches('[data-section-up]') && index > 0) { [sections[index - 1], sections[index]] = [sections[index], sections[index - 1]]; }
        if (button.matches('[data-section-down]') && index < sections.length - 1) { [sections[index + 1], sections[index]] = [sections[index], sections[index + 1]]; }
        if (button.matches('[data-order-remove]')) { section.question_ids.splice(order, 1); }
        if (button.matches('[data-order-up]') && order > 0) { [section.question_ids[order - 1], section.question_ids[order]] = [section.question_ids[order], section.question_ids[order - 1]]; }
        if (button.matches('[data-order-down]') && order < section.question_ids.length - 1) { [section.question_ids[order + 1], section.question_ids[order]] = [section.question_ids[order], section.question_ids[order + 1]]; }
        render();
    });
    let dragged = null;
    builder.addEventListener('dragstart', event => { const item = event.target.closest('[data-question-order]'); if (item) { dragged = { section: Number(item.closest('[data-section-index]').dataset.sectionIndex), order: Number(item.dataset.questionOrder) }; } });
    builder.addEventListener('dragover', event => event.preventDefault());
    builder.addEventListener('drop', event => { event.preventDefault(); const item = event.target.closest('[data-question-order]'); if (!item || !dragged) { return; } const section = Number(item.closest('[data-section-index]').dataset.sectionIndex); if (section === dragged.section) { const [id] = sections[section].question_ids.splice(dragged.order, 1); sections[section].question_ids.splice(Number(item.dataset.questionOrder), 0, id); render(); } dragged = null; });
    render(false);
}
document.querySelector('[data-exam-validate]')?.addEventListener('click', async event => {
    const output = document.querySelector('[data-validation-output]');
    try { const response = await fetch(event.currentTarget.dataset.examValidate, { headers: { Accept: 'application/json' } }); const data = await response.json(); output.textContent = response.ok ? data.errors.length ? data.errors.join('\n') : 'Cấu trúc hợp lệ.' : data.message; output.closest('[data-wizard-panel]').hidden = false; }
    catch { output.textContent = 'Không thể kiểm tra. Hãy kết nối mạng.'; }
});
const selectAllQuestions = document.querySelector('[data-select-all]');
const matchingIdsScript = questionBulkForm?.querySelector('[data-question-matching-ids]');
const matchingQuestionIds = matchingIdsScript ? JSON.parse(matchingIdsScript.textContent) : [];
const questionSelectionSummary = questionBulkForm?.querySelector('[data-question-selection-summary]');
let selectingAllMatchingQuestions = false;

function updateQuestionSelectionSummary() {
    if (!questionBulkForm || !selectAllQuestions || !questionSelectionSummary) {
        return;
    }

    const visibleQuestionCheckboxes = [...document.querySelectorAll('input[form="question-bulk"][name="ids[]"]')];
    const selectedVisibleQuestions = visibleQuestionCheckboxes.filter(checkbox => checkbox.checked).length;
    const selectedMatchingQuestions = questionBulkForm.querySelectorAll('input[name="matching_ids[]"]').length;
    const selectedCount = selectingAllMatchingQuestions ? selectedMatchingQuestions : selectedVisibleQuestions;
    const allMatchingSelected = selectingAllMatchingQuestions && selectedMatchingQuestions === matchingQuestionIds.length;

    selectAllQuestions.checked = allMatchingSelected;
    selectAllQuestions.indeterminate = selectedCount > 0 && !allMatchingSelected;
    questionSelectionSummary.textContent = selectingAllMatchingQuestions
        ? `Đã chọn ${selectedMatchingQuestions}/${matchingQuestionIds.length} câu phù hợp bộ lọc.`
        : selectedVisibleQuestions > 0 ? `Đã chọn ${selectedVisibleQuestions} câu trên trang này.` : '';
}

function setMatchingQuestionSelection(isSelected) {
    selectingAllMatchingQuestions = isSelected;
    questionBulkForm?.querySelectorAll('input[name="matching_ids[]"]').forEach(input => input.remove());

    if (isSelected && questionBulkForm) {
        matchingQuestionIds.forEach(questionId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'matching_ids[]';
            input.value = questionId;
            questionBulkForm.append(input);
        });
    }

    document.querySelectorAll('input[form="question-bulk"][name="ids[]"]').forEach(input => { input.checked = isSelected; });
    updateQuestionSelectionSummary();
}

selectAllQuestions?.addEventListener('change', event => setMatchingQuestionSelection(event.currentTarget.checked));
document.querySelectorAll('input[form="question-bulk"][name="ids[]"]').forEach(checkbox => checkbox.addEventListener('change', () => {
    if (selectingAllMatchingQuestions && questionBulkForm) {
        const existingInput = [...questionBulkForm.querySelectorAll('input[name="matching_ids[]"]')].find(input => input.value === checkbox.value);
        if (checkbox.checked && !existingInput) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'matching_ids[]';
            input.value = checkbox.value;
            questionBulkForm.append(input);
        } else if (!checkbox.checked) {
            existingInput?.remove();
        }
    }

    updateQuestionSelectionSummary();
}));

document.addEventListener('click', event => {
    const addButton = event.target.closest('[data-knowledge-add]');
    if (addButton) {
        const form = addButton.closest('form');
        const listName = addButton.dataset.knowledgeAdd;
        const list = form?.querySelector(`[data-knowledge-list="${listName}"]`);
        const template = form?.querySelector(`template[data-knowledge-template="${listName}"]`);
        if (!list || !template) { return; }

        const index = Number(list.dataset.nextIndex || 0);
        list.dataset.nextIndex = String(index + 1);
        list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(index)));
        list.querySelector('[data-knowledge-row]:last-child input, [data-knowledge-row]:last-child select')?.focus();
        return;
    }

    const removeButton = event.target.closest('[data-knowledge-remove]');
    if (removeButton) {
        removeButton.closest('[data-knowledge-row]')?.remove();
    }
});
