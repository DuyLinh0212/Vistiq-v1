<div class="user-management-page">
    @if(session('status'))
        <div class="alert alert-success" role="status" style="margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: 6px; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;">
            {{ session('status') }}
        </div>
    @endif

    <header class="page-header" style="margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 0.25rem 0;">Quản lý người dùng</h1>
        <p style="margin: 0; color: #52525b; font-size: 0.875rem;">Tra cứu tài khoản, theo dõi trạng thái hoạt động và quản lý vai trò thành viên.</p>
    </header>

    {{-- Filter Bar --}}
    <section class="panel filter-panel" style="background: #fff; border: 1px solid #e4e4e7; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
        <form method="GET" action="{{ route('admin.users.index') }}" class="user-filter-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap: 0.75rem; align-items: end;">
            <div>
                <label for="filter-q" style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.25rem; color: #3f3f46;">Từ khóa tìm kiếm</label>
                <input id="filter-q" name="q" type="text" class="input" value="{{ $filters['q'] ?? request('q') }}" placeholder="Tên hoặc email..." style="width: 100%; padding: 0.4375rem 0.625rem; border: 1px solid #d4d4d8; border-radius: 4px; font-size: 0.875rem;">
            </div>

            <div>
                <label for="filter-status" style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.25rem; color: #3f3f46;">Trạng thái</label>
                <select id="filter-status" name="status" class="input" style="width: 100%; padding: 0.4375rem 0.625rem; border: 1px solid #d4d4d8; border-radius: 4px; font-size: 0.875rem;">
                    <option value="all" @selected(($filters['status'] ?? request('status')) === 'all')>Tất cả trạng thái</option>
                    <option value="active" @selected(($filters['status'] ?? request('status')) === 'active')>Đang hoạt động</option>
                    <option value="locked" @selected(($filters['status'] ?? request('status')) === 'locked')>Đã khóa</option>
                </select>
            </div>

            <div>
                <label for="filter-role" style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.25rem; color: #3f3f46;">Vai trò</label>
                <select id="filter-role" name="role_id" class="input" style="width: 100%; padding: 0.4375rem 0.625rem; border: 1px solid #d4d4d8; border-radius: 4px; font-size: 0.875rem;">
                    <option value="">Tất cả vai trò</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected((int) ($filters['role_id'] ?? request('role_id')) === $role->id)>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-created-from" style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.25rem; color: #3f3f46;">Tạo từ ngày</label>
                <input id="filter-created-from" name="created_from" type="date" class="input" value="{{ $filters['created_from'] ?? request('created_from') }}" style="width: 100%; padding: 0.4375rem 0.625rem; border: 1px solid #d4d4d8; border-radius: 4px; font-size: 0.875rem;">
            </div>

            <div>
                <label for="filter-created-to" style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.25rem; color: #3f3f46;">Đến ngày</label>
                <input id="filter-created-to" name="created_to" type="date" class="input" value="{{ $filters['created_to'] ?? request('created_to') }}" style="width: 100%; padding: 0.4375rem 0.625rem; border: 1px solid #d4d4d8; border-radius: 4px; font-size: 0.875rem;">
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="button button-primary" style="font-size: 0.875rem; padding: 0.4375rem 0.875rem;">
                    Lọc tài khoản
                </button>
                <a href="{{ route('admin.users.index') }}" class="button button-secondary" style="font-size: 0.875rem; padding: 0.4375rem 0.75rem; text-decoration: none; display: inline-flex; align-items: center;">
                    Đặt lại
                </a>
            </div>
        </form>
    </section>

    {{-- Users Table --}}
    <section class="panel table-panel" style="background: #fff; border: 1px solid #e4e4e7; border-radius: 8px; overflow: hidden;">
        <table class="admin-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="background: #f4f4f5; border-bottom: 1px solid #e4e4e7;">
                    <th style="padding: 0.75rem 1rem; font-weight: 600; color: #3f3f46;">Họ và tên</th>
                    <th style="padding: 0.75rem 1rem; font-weight: 600; color: #3f3f46;">Email</th>
                    <th style="padding: 0.75rem 1rem; font-weight: 600; color: #3f3f46;">Trạng thái</th>
                    <th style="padding: 0.75rem 1rem; font-weight: 600; color: #3f3f46;">Vai trò</th>
                    <th style="padding: 0.75rem 1rem; font-weight: 600; color: #3f3f46;">Ngày tạo</th>
                    <th style="padding: 0.75rem 1rem; font-weight: 600; color: #3f3f46; text-align: right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr style="border-bottom: 1px solid #f4f4f5; transition: background 0.15s;">
                        <td style="padding: 0.75rem 1rem; font-weight: 600;">
                            <a href="{{ route('admin.users.show', $user) }}" style="color: #18181b; text-decoration: none;">
                                {{ $user->name }}
                            </a>
                        </td>
                        <td style="padding: 0.75rem 1rem; color: #52525b;">
                            {{ $user->email }}
                        </td>
                        <td style="padding: 0.75rem 1rem;">
                            @if($user->status === 'active')
                                <span style="font-size: 0.75rem; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 0.125rem 0.5rem; border-radius: 9999px; font-weight: 600;">
                                    Hoạt động
                                </span>
                            @else
                                <span style="font-size: 0.75rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 0.125rem 0.5rem; border-radius: 9999px; font-weight: 600;">
                                    Đã khóa
                                </span>
                            @endif
                        </td>
                        <td style="padding: 0.75rem 1rem;">
                            <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                @forelse($user->roles as $role)
                                    <span style="font-size: 0.75rem; background: #e4e4e7; color: #27272a; padding: 0.125rem 0.375rem; border-radius: 4px;">
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    <span style="font-size: 0.75rem; color: #a1a1aa; font-style: italic;">Learner</span>
                                @endforelse
                            </div>
                        </td>
                        <td style="padding: 0.75rem 1rem; color: #71717a; font-size: 0.8125rem;">
                            {{ $user->created_at ? $user->created_at->format('d/m/Y') : '—' }}
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: right;">
                            <a href="{{ route('admin.users.show', $user) }}" class="button button-quiet" style="font-size: 0.8125rem; padding: 0.25rem 0.625rem; text-decoration: none;">
                                Xem hồ sơ
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 2.5rem 1rem; text-align: center; color: #71717a;">
                            <p style="margin: 0 0 0.5rem 0; font-weight: 600; font-size: 1rem; color: #3f3f46;">Không tìm thấy người dùng phù hợp</p>
                            <p style="margin: 0; font-size: 0.875rem;">Hãy thử thay đổi từ khóa hoặc điều kiện lọc ở trên.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($users->hasPages())
            <div style="padding: 1rem; border-top: 1px solid #e4e4e7;">
                {{ $users->links() }}
            </div>
        @endif
    </section>
</div>
