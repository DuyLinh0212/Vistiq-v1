@once
<style>
.identity-workspace { display:grid; gap:16px; min-width:0; }
.identity-workspace .panel { margin:0; padding:20px; min-width:0; }
.identity-workspace .page-heading { min-height:0; margin:0; align-items:start; flex-wrap:wrap; }
.identity-workspace .page-heading h1 { margin:0 0 5px; font-size:22px; overflow-wrap:anywhere; }
.identity-workspace h2 { font-size:16px; margin:0; }
.identity-workspace p { margin-bottom:0; }
.identity-workspace .section-head { margin-bottom:14px; flex-wrap:wrap; }
.identity-workspace .admin-form-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
.identity-workspace .admin-form-grid label { margin:0; font-size:12px; font-weight:600; }
.identity-workspace .admin-form-grid input:not([type=checkbox]),.identity-workspace .admin-form-grid select,.identity-workspace .admin-form-grid textarea { width:100%; min-width:0; min-height:40px; height:auto; font-size:13px; }
.identity-workspace .admin-form-grid textarea { padding:10px; }
.identity-workspace .admin-form-grid input[aria-invalid=true] { border-color:var(--rose); }
.identity-workspace .admin-form-grid small { color:var(--muted); font-weight:400; }
.identity-workspace .identity-wide { grid-column:1/-1; }
.identity-workspace .identity-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.identity-workspace .identity-meta { display:flex; align-items:center; flex-wrap:wrap; gap:8px; color:var(--muted); font-size:12px; }
.identity-workspace .identity-filter { grid-template-columns:minmax(180px,2fr) repeat(2,minmax(130px,1fr)); }
.identity-workspace .identity-filter-actions { align-self:end; justify-content:end; }
.identity-workspace .identity-table-panel { padding:0!important; overflow:hidden; }
.identity-workspace .identity-table-heading { padding:16px 20px; margin:0; }
.identity-workspace .identity-table-scroll { overflow-x:auto; }
.identity-workspace .identity-table-scroll:focus-visible { outline:2px solid var(--blue); outline-offset:-2px; }
.identity-workspace .admin-table { min-width:720px; font-size:12px; }
.identity-workspace .admin-table th { color:var(--ink-soft); background:var(--canvas); font-size:11px; white-space:nowrap; }
.identity-workspace .admin-table td { vertical-align:middle; }
.identity-workspace .admin-table tbody tr:hover { background:var(--canvas); }
.identity-workspace .identity-person { display:grid; gap:3px; }
.identity-workspace .identity-person small { color:var(--muted); font-weight:400; overflow-wrap:anywhere; }
.identity-workspace .identity-table-actions { text-align:right; }
.identity-workspace .identity-pagination { padding:14px 20px; }
.identity-workspace .identity-empty { padding:36px 20px; text-align:center; }
.identity-workspace .identity-empty p { margin:6px 0 14px; }
.identity-workspace .identity-columns { display:grid; grid-template-columns:240px minmax(0,1fr); align-items:start; gap:16px; }
.identity-workspace .identity-role-nav { display:grid; gap:6px; margin-top:14px; }
.identity-workspace .identity-role-nav a { display:grid; gap:5px; padding:12px; border:1px solid transparent; border-radius:9px; }
.identity-workspace .identity-role-nav a:hover { background:var(--canvas); }
.identity-workspace .identity-role-nav a[aria-current=page] { border-color:var(--line); color:var(--blue); background:var(--blue-soft); }
.identity-workspace .identity-role-nav strong { display:flex; justify-content:space-between; gap:8px; font-size:12px; }
.identity-workspace .identity-role-select { display:none; }
.identity-workspace .role-permission-card__header { flex-wrap:wrap; }
.identity-workspace .role-permission-group { padding:12px; margin-bottom:12px; break-inside:avoid; }
.identity-workspace .role-permission-groups { display:block; columns:2; column-gap:12px; }
.identity-workspace .role-permission-group .identity-actions { margin:10px 0; }
.identity-workspace .role-permission-option strong { font-size:12px; }
.identity-workspace .role-permission-option { background:var(--card); border-color:var(--line); }
.identity-workspace .role-permission-option:has(input:checked) { background:var(--card); border-color:var(--blue); box-shadow:inset 3px 0 0 var(--blue); }
.identity-workspace .role-permission-option:hover { background:var(--canvas); border-color:var(--blue); }
.identity-workspace .role-permission-option strong { color:var(--ink); }
.identity-workspace .role-permission-option small { font-size:12px; color:var(--ink-soft); line-height:1.55; }
.identity-workspace .role-permission-group > p { color:var(--ink-soft); font-size:11px; }
.identity-workspace .identity-page-links { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; }
.identity-workspace .identity-page-numbers { display:flex; align-items:center; flex-wrap:wrap; gap:6px; }
.identity-workspace .identity-page-numbers .button { min-width:38px; padding:0 10px; }
.identity-workspace .identity-page-numbers [aria-disabled=true] { color:var(--muted); background:var(--canvas); box-shadow:none; cursor:default; }
.identity-workspace .identity-page-numbers [aria-current=page] { color:#fff; background:var(--blue); }
.identity-workspace .identity-page-summary { margin:0; color:var(--ink-soft); font-size:12px; }
.identity-workspace .identity-create > summary { cursor:pointer; color:var(--blue); font-weight:700; }
.identity-workspace .identity-create[open] > summary { margin-bottom:18px; }
.identity-workspace .identity-details { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(0,1fr); gap:16px; align-items:start; }
.identity-workspace .identity-records { display:grid; gap:12px; margin-top:14px; }
.identity-workspace .identity-record { padding-bottom:12px; border-bottom:1px solid var(--line); overflow-wrap:anywhere; }
.identity-workspace .identity-record:last-child { padding-bottom:0; border:0; }
.identity-workspace .identity-record p { margin-top:6px; white-space:pre-line; color:var(--ink-soft); }
.identity-workspace a:focus-visible,.identity-workspace button:focus-visible,.identity-workspace summary:focus-visible { outline:3px solid var(--blue); outline-offset:3px; }
@media(max-width:1100px) { .identity-workspace .role-permission-groups { columns:1; } }
@media(max-width:768px) {
.identity-workspace .panel { padding:16px; }
.identity-workspace .identity-columns,.identity-workspace .identity-details { grid-template-columns:minmax(0,1fr); }
.identity-workspace .identity-role-sidebar { display:none; }
.identity-workspace .identity-role-select { display:block; }
.identity-workspace .identity-filter { grid-template-columns:repeat(2,minmax(0,1fr)); }
.identity-workspace .identity-filter > label:first-child,.identity-workspace .identity-filter-actions { grid-column:1/-1; }
}
@media(max-width:480px) {
.identity-workspace .admin-form-grid { grid-template-columns:minmax(0,1fr); }
.identity-workspace .identity-wide { grid-column:auto; }
.identity-workspace .identity-actions > .button { flex:1; }
.identity-workspace .identity-page-number,.identity-workspace .identity-page-gap { display:none; }
.identity-workspace .role-permission-form__footer { align-items:stretch; flex-direction:column; }
.identity-workspace .role-permission-form__footer .button { white-space:normal; }
}
@media(prefers-reduced-motion:reduce) { .identity-workspace .button { transition:none; transform:none; } }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-identity-workspace]');
    if (!root) return;
    root.querySelector('[data-role-select]')?.addEventListener('change', event => { window.location.href = event.target.value; });
    root.querySelectorAll('[data-permission-group]').forEach(group => {
        const inputs = [...group.querySelectorAll('input[type=checkbox]')];
        const count = group.querySelector('[data-permission-count]');
        const update = () => { count.textContent = `${inputs.filter(input => input.checked).length}/${inputs.length} đã chọn`; };
        group.addEventListener('change', update);
        group.querySelectorAll('[data-group-check]').forEach(button => button.addEventListener('click', () => {
            inputs.forEach(input => { input.checked = button.dataset.groupCheck === 'true'; });
            update();
        }));
        update();
    });
    root.addEventListener('submit', async event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented || form.method.toLowerCase() !== 'post') return;
        if (form.dataset.pending === 'true') { event.preventDefault(); return; }
        const isUserRoles = form.hasAttribute('data-user-roles');
        const isRolePermissions = form.hasAttribute('data-role-permissions') && Number(form.dataset.memberCount) > 0;
        if ((isUserRoles || isRolePermissions) && form.dataset.approved !== 'true') {
            const removed = [...form.querySelectorAll(isUserRoles ? '[data-original-role]' : '[data-original-permission]')].filter(input => !input.checked);
            if (removed.length) {
                event.preventDefault();
                if (form.dataset.confirming === 'true') return;
                form.dataset.confirming = 'true';
                const message = isUserRoles
                    ? `Gỡ ${removed.map(input => input.dataset.originalRole).join(', ')} khỏi ${form.dataset.userName}? Người dùng sẽ mất các quyền từ vai trò này, trừ quyền vẫn được vai trò khác cấp.`
                    : `Thu hồi ${removed.map(input => input.dataset.originalPermission).join(', ')} khỏi vai trò ${form.dataset.roleName}? Thay đổi ảnh hưởng đến ${form.dataset.memberCount} người dùng. Quyền do vai trò khác cấp vẫn được giữ.`;
                const confirmed = await (window.vistiqConfirm ? window.vistiqConfirm(message, {title:isUserRoles ? 'Gỡ vai trò của người dùng' : 'Thu hồi quyền của vai trò', confirmLabel:isUserRoles ? `Gỡ ${removed.length} vai trò` : `Thu hồi ${removed.length} quyền`}) : Promise.resolve(window.confirm(message)));
                delete form.dataset.confirming;
                if (confirmed) {
                    form.dataset.approved = 'true';
                    if (event.submitter) form.requestSubmit(event.submitter);
                    else form.requestSubmit();
                }
                return;
            }
        }
        form.dataset.pending = 'true';
        form.setAttribute('aria-busy', 'true');
        form.querySelectorAll('button[type=submit]').forEach(button => {
            button.dataset.originalLabel = button.textContent;
            button.disabled = true;
            button.textContent = form.dataset.pendingLabel || 'Đang lưu…';
        });
    });
    window.addEventListener('pageshow', () => root.querySelectorAll('form[data-pending]').forEach(form => {
        delete form.dataset.pending;
        delete form.dataset.approved;
        form.removeAttribute('aria-busy');
        form.querySelectorAll('button[data-original-label]').forEach(button => { button.disabled = false; button.textContent = button.dataset.originalLabel; });
    }));
});
</script>
@endonce
