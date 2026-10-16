@php
    $permissionCount = collect($permissionGroups)->sum(fn (array $group): int => count($group['permissions']));
    $isSuperAdmin = $selectedRole?->code === 'super-admin';
    $isAdmin = $selectedRole?->code === 'admin';
    $isSystemRole = $isSuperAdmin || $isAdmin;
@endphp

<div class="role-workspace-page">
    @if(session('status'))
        <div class="alert alert-success" role="status" style="margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: 6px; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" role="alert" style="margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: 6px; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;">
            <strong>Đã xảy ra lỗi:</strong>
            <ul style="margin: 0.5rem 0 0 1.25rem; padding: 0;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <header class="role-page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 0.25rem 0;">Phân quyền theo vai trò</h1>
            <p style="margin: 0; color: #52525b; font-size: 0.875rem;">Quản lý vai trò và gán quyền hệ thống. Danh mục quyền do mã nguồn quản lý (chỉ đọc).</p>
        </div>
        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <span class="badge" style="padding: 0.25rem 0.5rem; background: #e4e4e7; border-radius: 4px; font-size: 0.8125rem;">{{ $permissionCount }} quyền hệ thống</span>
            <button type="button" class="button button-primary" onclick="document.getElementById('create-role-dialog').showModal()" style="font-size: 0.875rem;">
                + Thêm vai trò mới
            </button>
        </div>
    </header>

    {{-- Mobile Switcher --}}
    <div class="role-mobile-select" style="margin-bottom: 1rem; display: none;">
        <label for="role-select-mobile" style="font-size: 0.875rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Chọn vai trò:</label>
        <select id="role-select-mobile" class="input" style="width: 100%;" onchange="window.location.href = this.value;">
            @foreach($roles as $role)
                <option value="{{ route('admin.roles.index', ['selected' => $role->id]) }}" @selected($selectedRole && $selectedRole->id === $role->id)>
                    {{ $role->name }} ({{ $role->users_count }} người dùng)
                </option>
            @endforeach
        </select>
    </div>

    <div class="role-two-columns" style="display: grid; grid-template-columns: 280px 1fr; gap: 1.5rem; align-items: start;">
        {{-- Left Column: Role List --}}
        <aside class="panel role-sidebar" style="background: #fff; border: 1px solid #e4e4e7; border-radius: 8px; padding: 1rem;">
            <h2 style="font-size: 1rem; font-weight: 600; margin: 0 0 0.75rem 0; padding-bottom: 0.5rem; border-bottom: 1px solid #f4f4f5;">
                Danh sách vai trò ({{ $roles->count() }})
            </h2>

            <nav aria-label="Danh sách vai trò">
                <ul style="list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.375rem;">
                    @forelse($roles as $role)
                        @php($isActive = $selectedRole && $selectedRole->id === $role->id)
                        <li>
                            <a href="{{ route('admin.roles.index', ['selected' => $role->id]) }}"
                               style="display: flex; flex-direction: column; padding: 0.625rem 0.75rem; border-radius: 6px; text-decoration: none; transition: background 0.15s; {{ $isActive ? 'background: #f4f4f5; border-left: 3px solid #18181b;' : 'background: transparent;' }}"
                               aria-current="{{ $isActive ? 'page' : 'false' }}">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong style="color: #18181b; font-size: 0.9375rem;">{{ $role->name }}</strong>
                                    <span style="font-size: 0.75rem; background: #e4e4e7; color: #3f3f46; padding: 0.125rem 0.375rem; border-radius: 9999px;">
                                        {{ $role->users_count }}
                                    </span>
                                </div>
                                <div style="display: flex; gap: 0.5rem; align-items: center; margin-top: 0.25rem;">
                                    <code style="font-size: 0.75rem; color: #71717a;">{{ $role->code }}</code>
                                    @if($role->code === 'super-admin' || $role->code === 'admin')
                                        <span style="font-size: 0.6875rem; color: #4b5563; background: #f3f4f6; padding: 0.0625rem 0.3125rem; border-radius: 3px;">Hệ thống</span>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @empty
                        <li style="color: #71717a; font-size: 0.875rem; padding: 0.5rem;">Chưa có vai trò nào.</li>
                    @endforelse
                </ul>
            </nav>
        </aside>

        {{-- Right / Main Area: Role Detail & Permission Matrix --}}
        <main class="panel role-main" style="background: #fff; border: 1px solid #e4e4e7; border-radius: 8px; padding: 1.5rem;">
            @if($selectedRole)
                <header style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f4f4f5; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                            <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0;">{{ $selectedRole->name }}</h2>
                            <code style="font-size: 0.8125rem; color: #71717a; background: #f4f4f5; padding: 0.125rem 0.375rem; border-radius: 4px;">{{ $selectedRole->code }}</code>
                            @if($isSuperAdmin)
                                <span style="font-size: 0.75rem; color: #b45309; background: #fef3c7; padding: 0.125rem 0.375rem; border-radius: 4px; font-weight: 600;">Super Admin (Bảo vệ)</span>
                            @elseif($isAdmin)
                                <span style="font-size: 0.75rem; color: #1e40af; background: #dbeafe; padding: 0.125rem 0.375rem; border-radius: 4px; font-weight: 600;">Mặc định: Toàn quyền</span>
                            @endif
                        </div>
                        <p style="margin: 0.25rem 0 0 0; color: #52525b; font-size: 0.875rem;">
                            {{ $selectedRole->description ?: 'Chưa có mô tả cho vai trò này.' }}
                            — Đang gán cho <strong>{{ $selectedRole->users_count }}</strong> người dùng.
                        </p>
                    </div>

                    @if(! $isSystemRole)
                        <button type="button" class="button button-danger" onclick="document.getElementById('delete-role-dialog').showModal()" style="font-size: 0.8125rem; color: #b91c1c; border-color: #fca5a5; background: #fef2f2;">
                            Xóa vai trò
                        </button>
                    @endif
                </header>

                @if($isSuperAdmin)
                    <div style="padding: 1rem; background: #fefce8; border: 1px solid #fef08a; border-radius: 6px; color: #713f12; font-size: 0.875rem; margin-bottom: 1.5rem;">
                        <strong>Lưu ý:</strong> Vai trò Super Admin luôn sở hữu toàn bộ quyền trong hệ thống để đảm bảo tính sẵn sàng quản trị và không thể chỉnh sửa phân quyền.
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.roles.update', $selectedRole) }}" class="role-permission-form">
                        @csrf

                        @if(! $isSystemRole)
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; background: #fafafa; border: 1px solid #f4f4f5; border-radius: 6px;">
                                <div>
                                    <label for="role-edit-name" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.25rem;">Tên vai trò *</label>
                                    <input id="role-edit-name" name="name" type="text" class="input" style="width: 100%; padding: 0.5rem; border: 1px solid #d4d4d8; border-radius: 4px;" value="{{ old('name', $selectedRole->name) }}" required>
                                </div>
                                <div>
                                    <label for="role-edit-description" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.25rem;">Mô tả</label>
                                    <input id="role-edit-description" name="description" type="text" class="input" style="width: 100%; padding: 0.5rem; border: 1px solid #d4d4d8; border-radius: 4px;" value="{{ old('description', $selectedRole->description) }}">
                                </div>
                            </div>
                        @endif

                        <div style="margin-bottom: 1rem;">
                            <h3 style="font-size: 1rem; font-weight: 600; margin: 0 0 0.5rem 0;">Gán quyền hệ thống</h3>
                            <p style="font-size: 0.8125rem; color: #71717a; margin: 0 0 1rem 0;">Tích chọn các quyền mà thành viên thuộc vai trò này được phép truy cập.</p>
                        </div>

                        <div class="role-permission-groups" style="display: flex; flex-direction: column; gap: 1.25rem;">
                            @foreach($permissionGroups as $group)
                                <fieldset class="role-permission-group" style="border: 1px solid #e4e4e7; border-radius: 6px; padding: 1rem; margin: 0;">
                                    <legend style="padding: 0 0.5rem; font-weight: 600; font-size: 0.9375rem; color: #18181b;">
                                        {{ $group['label'] }}
                                    </legend>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                        <p style="margin: 0; font-size: 0.8125rem; color: #71717a;">{{ $group['description'] }}</p>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button type="button" class="button button-quiet" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="toggleGroupCheckboxes('{{ $group['code'] }}', true)">
                                                Chọn tất cả
                                            </button>
                                            <button type="button" class="button button-quiet" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="toggleGroupCheckboxes('{{ $group['code'] }}', false)">
                                                Bỏ chọn
                                            </button>
                                        </div>
                                    </div>

                                    <div class="role-permission-options" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.75rem;">
                                        @foreach($group['permissions'] as $permission)
                                            @php($permissionInputId = 'role-'.$selectedRole->id.'-perm-'.$permission['id'])
                                            <label for="{{ $permissionInputId }}"
                                                   style="display: flex; align-items: flex-start; gap: 0.625rem; padding: 0.625rem; border: 1px solid #f4f4f5; border-radius: 4px; background: #fafafa; cursor: pointer; transition: background 0.15s;">
                                                <input
                                                    id="{{ $permissionInputId }}"
                                                    class="perm-checkbox-{{ $group['code'] }}"
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="{{ $permission['id'] }}"
                                                    @checked($selectedRole->permissions->contains('id', $permission['id']))
                                                    style="margin-top: 0.25rem;"
                                                >
                                                <span style="font-size: 0.8125rem;">
                                                    <strong style="display: block; color: #18181b;">{{ $permission['name'] }}</strong>
                                                    <span style="display: block; color: #71717a; font-size: 0.75rem; margin-top: 0.125rem;">{{ $permission['description'] }}</span>
                                                    <code style="display: inline-block; font-size: 0.6875rem; color: #52525b; margin-top: 0.25rem; background: #e4e4e7; padding: 0.0625rem 0.25rem; border-radius: 3px;">{{ $permission['code'] }}</code>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endforeach
                        </div>

                        <footer style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 1px solid #f4f4f5;">
                            <span style="font-size: 0.8125rem; color: #71717a;">Quyền thay đổi sẽ có hiệu lực ngay lập tức với các phiên đăng nhập.</span>
                            <button type="submit" class="button button-primary" style="font-size: 0.875rem;">
                                Lưu quyền {{ $selectedRole->name }}
                            </button>
                        </footer>
                    </form>
                @endif
            @else
                <div style="padding: 2rem; text-align: center; color: #71717a;">
                    Chọn một vai trò ở cột bên trái để xem và phân quyền.
                </div>
            @endif
        </main>
    </div>

    {{-- Create Role Modal Dialog --}}
    <dialog id="create-role-dialog" style="padding: 1.5rem; border: 1px solid #e4e4e7; border-radius: 8px; width: 500px; max-width: 90vw; background: #fff; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);">
        <form method="POST" action="{{ route('admin.roles.store') }}">
            @csrf
            <header style="margin-bottom: 1.25rem;">
                <h3 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 0.25rem 0;">Tạo vai trò mới</h3>
                <p style="margin: 0; color: #71717a; font-size: 0.875rem;">Thêm vai trò quản trị mới và cấu hình quyền hạn.</p>
            </header>

            <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label for="create-role-code" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.25rem;">Mã vai trò *</label>
                    <input id="create-role-code" name="code" type="text" placeholder="vidu: content-reviewer" pattern="^[a-z0-9\-]+$" required style="width: 100%; padding: 0.5rem; border: 1px solid #d4d4d8; border-radius: 4px; font-size: 0.875rem;">
                    <small style="color: #71717a; font-size: 0.75rem;">Chỉ dùng chữ thường, số và gạch ngang (không thể đổi sau khi tạo).</small>
                </div>
                <div>
                    <label for="create-role-name" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.25rem;">Tên vai trò *</label>
                    <input id="create-role-name" name="name" type="text" placeholder="Người duyệt nội dung" required style="width: 100%; padding: 0.5rem; border: 1px solid #d4d4d8; border-radius: 4px; font-size: 0.875rem;">
                </div>
                <div>
                    <label for="create-role-description" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.25rem;">Mô tả</label>
                    <textarea id="create-role-description" name="description" rows="3" placeholder="Mô tả trách nhiệm của vai trò này..." style="width: 100%; padding: 0.5rem; border: 1px solid #d4d4d8; border-radius: 4px; font-size: 0.875rem;"></textarea>
                </div>
            </div>

            <footer style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="button button-secondary" onclick="document.getElementById('create-role-dialog').close()" style="font-size: 0.875rem;">
                    Hủy
                </button>
                <button type="submit" class="button button-primary" style="font-size: 0.875rem;">
                    Tạo vai trò
                </button>
            </footer>
        </form>
    </dialog>

    {{-- Delete Role Confirmation Dialog --}}
    @if($selectedRole && ! $isSystemRole)
        <dialog id="delete-role-dialog" style="padding: 1.5rem; border: 1px solid #fecaca; border-radius: 8px; width: 520px; max-width: 90vw; background: #fff; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);">
            <form method="POST" action="{{ route('admin.roles.destroy', $selectedRole) }}">
                @csrf
                @method('DELETE')

                <header style="margin-bottom: 1rem;">
                    <h3 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 0.5rem 0; color: #991b1b;">
                        Xác nhận xóa vai trò {{ $selectedRole->name }}
                    </h3>
                    <div style="background: #fef2f2; border: 1px solid #fee2e2; border-radius: 6px; padding: 0.875rem; color: #991b1b; font-size: 0.875rem; line-height: 1.5;">
                        <p style="margin: 0 0 0.5rem 0; font-weight: 600;">
                            Cảnh báo: Vai trò này hiện đang được gán cho {{ $selectedRole->users_count }} người dùng.
                        </p>
                        <p style="margin: 0;">
                            Thao tác này sẽ gỡ bỏ vai trò <strong>{{ $selectedRole->name }}</strong> khỏi toàn bộ <strong>{{ $selectedRole->users_count }}</strong> thành viên. Toàn bộ các quyền thuộc vai trò này sẽ bị thu hồi khỏi người dùng (trừ trường hợp họ vẫn được cấp quyền đó thông qua một vai trò khác).
                        </p>
                    </div>
                </header>

                <footer style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.25rem;">
                    <button type="button" class="button button-secondary" autofocus onclick="document.getElementById('delete-role-dialog').close()" style="font-size: 0.875rem;">
                        Giữ vai trò
                    </button>
                    <button type="submit" class="button button-danger" style="font-size: 0.875rem; background: #dc2626; color: #fff; border: 1px solid #b91c1c;">
                        Xóa vai trò và gỡ {{ $selectedRole->users_count }} người dùng
                    </button>
                </footer>
            </form>
        </dialog>
    @endif
</div>

<script>
    function toggleGroupCheckboxes(groupCode, checked) {
        document.querySelectorAll('.perm-checkbox-' + groupCode).forEach(function(el) {
            el.checked = checked;
        });
    }

    // Responsive mobile view check
    if (window.innerWidth <= 768) {
        const switcher = document.querySelector('.role-mobile-select');
        const sidebar = document.querySelector('.role-sidebar');
        if (switcher && sidebar) {
            switcher.style.display = 'block';
            sidebar.style.display = 'none';
            document.querySelector('.role-two-columns').style.gridTemplateColumns = '1fr';
        }
    }
</script>
