@php
    $permissionCount = collect($permissionGroups)->sum(fn (array $group): int => count($group['permissions']));
@endphp

<section class="role-permissions-page" aria-labelledby="role-permissions-title">
    <header class="role-permissions-intro">
        <div>
            <h1 id="role-permissions-title">Phân quyền theo vai trò</h1>
            <p>Gán các quyền có sẵn cho từng vai trò. Danh mục quyền do hệ thống quản lý.</p>
        </div>
        <span class="role-permissions-count">{{ $permissionCount }} quyền hệ thống</span>
    </header>

    @forelse($roles as $role)
        <section class="panel role-permission-card" aria-labelledby="role-title-{{ $role->id }}">
            <header class="role-permission-card__header">
                <div>
                    <div class="role-permission-card__meta">
                        <code>{{ $role->code }}</code>
                        <span>{{ $role->users_count }} người dùng</span>
                    </div>
                    <h2 id="role-title-{{ $role->id }}">{{ $role->name }}</h2>
                    @if($role->description)
                        <p>{{ $role->description }}</p>
                    @endif
                </div>

                @if($role->code === 'admin')
                    <span class="role-permission-default">Mặc định: Toàn quyền</span>
                @endif
            </header>

            @if($role->code === 'super-admin')
                <p class="role-permission-readonly" role="status">Super Admin luôn có toàn bộ quyền và không thể chỉnh sửa tại màn hình này.</p>
            @else
                <form method="post" action="{{ route('admin.roles.update', $role) }}" class="role-permission-form">
                    @csrf

                    <div class="role-permission-groups">
                        @foreach($permissionGroups as $group)
                            <fieldset class="role-permission-group">
                                <legend>{{ $group['label'] }}</legend>
                                <p>{{ $group['description'] }}</p>

                                <div class="role-permission-options">
                                    @foreach($group['permissions'] as $permission)
                                        @php($permissionInputId = 'role-'.$role->id.'-permission-'.$permission['id'])
                                        <label class="role-permission-option" for="{{ $permissionInputId }}">
                                            <input
                                                id="{{ $permissionInputId }}"
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission['id'] }}"
                                                @checked($role->permissions->contains('id', $permission['id']))
                                            >
                                            <span>
                                                <strong>{{ $permission['name'] }}</strong>
                                                <small>{{ $permission['description'] }}</small>
                                                <code>{{ $permission['code'] }}</code>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>

                    <footer class="role-permission-form__footer">
                        <span>Quyền được áp dụng ngay sau khi lưu.</span>
                        <button class="button button-primary" type="submit">Lưu quyền {{ $role->name }}</button>
                    </footer>
                </form>
            @endif
        </section>
    @empty
        <div class="panel role-permission-empty">Chưa có vai trò nào để phân quyền.</div>
    @endforelse
</section>
