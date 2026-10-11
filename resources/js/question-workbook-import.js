const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character]);

document.querySelectorAll('[data-workbook-import]').forEach(container => {
    const uploadForm = container.querySelector('[data-workbook-upload]');
    const preview = container.querySelector('[data-workbook-preview]');
    const summary = container.querySelector('[data-workbook-summary]');
    const sheetsRoot = container.querySelector('[data-workbook-sheets]');
    const readingKind = container.dataset.kind === 'reading';
    const audioRoot = container.querySelector('[data-workbook-audio]');
    const imageRoot = container.querySelector('[data-workbook-images]');
    const errors = container.querySelector('[data-workbook-errors]');
    const confirmButton = container.querySelector('[data-workbook-confirm]');
    const resetButton = container.querySelector('[data-workbook-reset]');
    let token = null;
    let hasImportableQuestions = false;

    const showError = message => {
        errors.hidden = !message;
        errors.textContent = message || '';
    };
    sheetsRoot.addEventListener('input', event => {
        const editor = event.target.closest('[data-reading-sheet-editor]');
        if (!editor) return;

        const title = editor.querySelector('[name="title"]')?.value.trim() || 'Chưa có tiêu đề bài đọc';
        const body = editor.querySelector('[name="body"]')?.value || '';
        editor.querySelector('.workbook-reading-card-heading b').textContent = title;
        editor.querySelector('.workbook-reading-card-heading span').textContent = `${body.trim() ? body.trim().split(/\s+/).length : 0} từ`;
        const excerpt = body.replace(/\s+/g, ' ').trim();
        editor.querySelector('.workbook-reading-excerpt').textContent = excerpt
            ? `${excerpt.slice(0, 260)}${excerpt.length > 260 ? '…' : ''}`
            : 'Workbook chưa có đoạn văn. Thêm nội dung tại đây trước khi import.';
    });
    const csrf = () => uploadForm.querySelector('[name="_token"]')?.value || document.querySelector('meta[name="csrf-token"]')?.content || '';
    const post = async (url, data) => fetch(url, { method: 'POST', body: data, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() } });

    uploadForm.addEventListener('submit', async event => {
        event.preventDefault();
        showError('');
        confirmButton.disabled = true;
        confirmButton.textContent = 'Đang đọc workbook…';
        const data = new FormData(uploadForm);
        data.append('kind', container.dataset.kind);
        try {
            const response = await post(container.dataset.previewUrl, data);
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join('\n') || result.message || 'Không thể đọc workbook.');
            token = result.token;
            const sheets = result.sheets || [];
            hasImportableQuestions = container.dataset.kind === 'thpt_english'
                ? result.can_import === true
                : sheets.some(sheet => !sheet.blocked && sheet.question_count > 0);
            summary.textContent = container.dataset.kind === 'thpt_english'
                ? `Đã đọc ${sheets.length} phần · ${result.total_questions || 0}/40 câu hợp lệ${hasImportableQuestions ? '' : ' · cần xử lý lỗi trước khi import'}.`
                : `Đã nhận diện ${sheets.length} sheet · ${result.total_questions || 0} câu hợp lệ.`;
            sheetsRoot.innerHTML = sheets.map(sheet => {
                const errorList = sheet.errors?.length
                    ? `<ul>${sheet.errors.map(error => `<li>Dòng ${Number(error.row)}: ${escapeHtml(error.message)}</li>`).join('')}</ul>`
                    : '<small>Không có lỗi dữ liệu.</small>';
                if (!readingKind || sheet.blocked || !sheet.question_count) {
                    return `<article class="workbook-sheet-row"><div><b>${escapeHtml(sheet.title || sheet.code)}</b><span>${Number(sheet.question_count || 0)}${sheet.expected_count ? `/${Number(sheet.expected_count)}` : ''} câu</span></div>${errorList}</article>`;
                }

                const title = String(sheet.reading_title || '');
                const body = String(sheet.reading_body || '');
                const excerpt = body.replace(/\s+/g, ' ').trim();
                const previewText = excerpt ? `${excerpt.slice(0, 260)}${excerpt.length > 260 ? '…' : ''}` : 'Workbook chưa có đoạn văn. Thêm nội dung tại đây trước khi import.';
                const wordCount = body.trim() ? body.trim().split(/\s+/).length : 0;
                const open = !title || !body ? ' open' : '';
                const code = escapeHtml(sheet.code);
                return `<article class="workbook-sheet-row workbook-reading-sheet"><div><b>${escapeHtml(sheet.code)}</b><span>${Number(sheet.question_count)} câu</span></div>${errorList}<section class="workbook-reading-card" data-reading-sheet-editor data-sheet-code="${code}"><div class="workbook-reading-card-heading"><b>${escapeHtml(title || 'Chưa có tiêu đề bài đọc')}</b><span>${wordCount} từ</span></div><p class="workbook-reading-excerpt">${escapeHtml(previewText)}</p><details class="workbook-reading-fields"${open}><summary>Xem / sửa nội dung bài đọc</summary><label>Tiêu đề bài đọc<input name="title" value="${escapeHtml(title)}" maxlength="255" placeholder="Nhập tiêu đề bài đọc" required></label><label>Đoạn văn<textarea name="body" rows="7" maxlength="50000" placeholder="Dán hoặc nhập đoạn văn cần đọc" required>${escapeHtml(body)}</textarea></label></details></section></article>`;
            }).join('');
            audioRoot.innerHTML = container.dataset.kind === 'listening'
                ? sheets.filter(sheet => !sheet.blocked && sheet.question_count > 0).map(sheet => `<label class="workbook-audio-row"><span>Audio · ${escapeHtml(sheet.code)} <em>*</em></span><input type="file" name="audio[${escapeHtml(sheet.code)}]" accept=".mp3,.wav,.m4a,.ogg,audio/*" required></label>`).join('')
                : '';
            const groupImages = sheets
                .filter(sheet => sheet.requires_group_image && !sheet.blocked && sheet.question_count > 0)
                .map(sheet => `<fieldset class="workbook-image-group"><legend>Ảnh văn bản · ${escapeHtml(sheet.title || sheet.code)}</legend><div><label class="workbook-image-row"><span>Ảnh dùng chung cho ${Number(sheet.question_count)} câu <em>*</em><small>JPG, PNG hoặc WEBP</small></span><input type="file" name="group_images[${escapeHtml(sheet.part_code || sheet.exam_part)}]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required></label></div></fieldset>`);
            const questionImages = sheets
                .filter(sheet => sheet.requires_question_images && !sheet.blocked && sheet.question_count > 0)
                .map(sheet => `<fieldset class="workbook-image-group"><legend>Ảnh · TOEIC Listening Part 1 · ${escapeHtml(sheet.code)}</legend><div>${(sheet.questions || []).map(question => `<label class="workbook-image-row"><span><b>Dòng ${Number(question.row)}</b> · ${escapeHtml(question.prompt)} <em>*</em></span><input type="file" name="images[${escapeHtml(sheet.code)}][${Number(question.row)}]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required></label>`).join('')}</div></fieldset>`);
            imageRoot.innerHTML = [...groupImages, ...questionImages].join('');
            confirmButton.hidden = !hasImportableQuestions;
            confirmButton.disabled = false;
            confirmButton.textContent = `Xác nhận import ${result.total_questions || 0} câu`;
            preview.hidden = false;
            uploadForm.hidden = true;
        } catch (error) {
            showError(error.message || 'Không thể đọc workbook.');
            preview.hidden = false;
            confirmButton.hidden = true;
        }
    });

    confirmButton.addEventListener('click', async () => {
        if (!token || !hasImportableQuestions) return;
        showError('');
        const data = new FormData();
        data.append('_token', csrf());
        data.append('token', token);
        const readingEditors = [...sheetsRoot.querySelectorAll('[data-reading-sheet-editor]')];
        const emptyReading = readingEditors.find(editor => !editor.querySelector('[name="title"]')?.value.trim() || !editor.querySelector('[name="body"]')?.value.trim());
        if (emptyReading) {
            const missingInput = !emptyReading.querySelector('[name="title"]')?.value.trim()
                ? emptyReading.querySelector('[name="title"]')
                : emptyReading.querySelector('[name="body"]');
            emptyReading.querySelector('.workbook-reading-fields')?.setAttribute('open', '');
            missingInput?.focus();
            showError(`Bổ sung tiêu đề và đoạn văn cho bài ${emptyReading.dataset.sheetCode} trước khi import.`);
            return;
        }
        readingEditors.forEach(editor => {
            const code = editor.dataset.sheetCode;
            data.append(`readings[${code}][title]`, editor.querySelector('[name="title"]').value);
            data.append(`readings[${code}][body]`, editor.querySelector('[name="body"]').value);
        });
        const uploadInputs = [...audioRoot.querySelectorAll('input[type="file"]'), ...imageRoot.querySelectorAll('input[type="file"]')];
        const missingUpload = uploadInputs.find(input => !input.files?.[0]);
        if (missingUpload) {
            const label = missingUpload.closest('label')?.querySelector('span')?.textContent?.replace('*', '').trim();
            showError(`Vui lòng đính kèm ${label || 'đầy đủ file media'} trước khi xác nhận.`);
            return;
        }
        uploadInputs.forEach(input => {
            if (input.files?.[0]) data.append(input.name, input.files[0]);
        });
        confirmButton.disabled = true;
        confirmButton.textContent = 'Đang import…';
        try {
            const response = await post(container.dataset.confirmUrl, data);
            const result = await response.json();
            if (!response.ok) {
                const sheetErrors = Object.entries(result.sheets || {}).flatMap(([code, messages]) => messages.map(message => `${code}: ${message}`));
                throw new Error(sheetErrors.join('\n') || result.message || 'Không thể xác nhận import.');
            }
            const failed = Object.entries(result.errors || {}).flatMap(([code, messages]) => messages.map(message => `${code}: ${message}`));
            summary.textContent = `Đã import ${result.summary?.imported || 0}/${result.summary?.total || 0} câu ở trạng thái bản nháp.`;
            if (failed.length) showError(failed.join('\n'));
            confirmButton.hidden = true;
            if (result.redirect && !failed.length) window.location.assign(result.redirect);
        } catch (error) {
            showError(error.message || 'Không thể xác nhận import.');
            confirmButton.disabled = false;
            confirmButton.textContent = 'Thử xác nhận lại';
        }
    });

    resetButton.addEventListener('click', () => {
        token = null;
        hasImportableQuestions = false;
        uploadForm.reset();
        uploadForm.hidden = false;
        preview.hidden = true;
        confirmButton.hidden = true;
        confirmButton.disabled = false;
        confirmButton.textContent = 'Xác nhận import';
        sheetsRoot.replaceChildren();
        audioRoot.replaceChildren();
        imageRoot.replaceChildren();
        showError('');
    });
});
