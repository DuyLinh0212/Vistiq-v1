export function notify(message, failure = false) {
    document.querySelector('[data-ui-feedback]')?.remove();
    const notice = document.createElement('div');
    notice.className = failure ? 'admin-alert admin-alert-error' : 'admin-toast';
    notice.dataset.uiFeedback = '';
    notice.setAttribute('role', failure ? 'alert' : 'status');
    notice.setAttribute('aria-live', failure ? 'assertive' : 'polite');
    const text = document.createElement('span');
    text.textContent = message;
    const close = document.createElement('button');
    close.type = 'button';
    close.textContent = '×';
    close.setAttribute('aria-label', 'Đóng thông báo');
    close.addEventListener('click', () => notice.remove());
    notice.append(text, close);
    (document.querySelector('.admin-page') ?? document.querySelector('main') ?? document.body).prepend(notice);
}

document.addEventListener('click', async event => {
    const link = event.target.closest('a[data-report-export]');
    if (!link) return;
    event.preventDefault();
    if (link.getAttribute('aria-busy') === 'true') return;
    const label = link.textContent;
    link.setAttribute('aria-busy', 'true');
    link.setAttribute('aria-disabled', 'true');
    link.textContent = 'Đang tạo báo cáo…';
    try {
        const response = await fetch(link.href, { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            const result = await response.json().catch(() => ({}));
            throw new Error(response.status === 403 ? 'Bạn chưa có quyền xuất báo cáo.' : Object.values(result.errors ?? {}).flat().join(' ') || 'Không thể tạo báo cáo. Hãy thử lại hoặc thu hẹp bộ lọc.');
        }
        if (!/text\/csv|spreadsheetml/.test(response.headers.get('content-type') ?? '')) {
            throw new Error('Phiên đăng nhập đã hết hiệu lực. Hãy đăng nhập lại để xuất báo cáo.');
        }
        const url = URL.createObjectURL(await response.blob());
        const download = document.createElement('a');
        download.href = url;
        download.download = /filename="([^"]+)"/.exec(response.headers.get('content-disposition') ?? '')?.[1] ?? 'vistiq-report';
        download.click();
        setTimeout(() => URL.revokeObjectURL(url), 10000);
        notify('Đã tạo và tải báo cáo theo bộ lọc đang xem.');
    } catch (error) {
        notify(error.message, true);
    } finally {
        link.textContent = label;
        link.removeAttribute('aria-busy');
        link.removeAttribute('aria-disabled');
    }
});

document.addEventListener('submit', async event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
    if (form.hasAttribute('data-confirm') && !document.body.classList.contains('admin-mode') && !form.dataset.uiConfirmed) {
        event.preventDefault();
        if (form.dataset.uiConfirming) return;
        form.dataset.uiConfirming = 'true';
        const accepted = await window.vistiqConfirm(form.dataset.confirm, { title: form.dataset.confirmTitle, confirmLabel: form.dataset.confirmLabel ?? event.submitter?.textContent?.trim() });
        delete form.dataset.uiConfirming;
        if (accepted) {
            form.dataset.uiConfirmed = 'true';
            form.requestSubmit(event.submitter);
        }
        return;
    }
    delete form.dataset.uiConfirmed;
    if (!form.hasAttribute('data-pending-label')) return;
    if (form.dataset.uiSubmitting) {
        event.preventDefault();
        return;
    }
    form.dataset.uiSubmitting = 'true';
    form.setAttribute('aria-busy', 'true');
    form.querySelectorAll('button[type="submit"], button:not([type])').forEach(button => {
        button.dataset.uiOriginalLabel = button.textContent;
        button.disabled = true;
        button.textContent = form.dataset.pendingLabel;
    });
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-ui-submitting]').forEach(form => {
        delete form.dataset.uiSubmitting;
        form.removeAttribute('aria-busy');
        form.querySelectorAll('[data-ui-original-label]').forEach(button => {
            button.disabled = false;
            button.textContent = button.dataset.uiOriginalLabel;
        });
    });
});

document.querySelectorAll('.icon-button[aria-label]:not([title])').forEach(control => control.title = control.getAttribute('aria-label'));
if (!document.body.classList.contains('admin-mode')) {
    document.querySelectorAll('[data-dismiss-admin-toast]').forEach(button => button.addEventListener('click', () => button.closest('[data-admin-toast]')?.remove()));
}
