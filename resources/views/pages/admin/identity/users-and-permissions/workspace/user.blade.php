<div class="user-detail-page">
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

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0;">{{ $user->name }}</h1>
                @if($user->status === 'active')
                    <span style="font-size: 0.75rem; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 0.125rem 0.5rem; border-radius: 9999px; font-weight: 600;">
                        Đang hoạt động
                    </span>
                @else
                    <span style="font-size: 0.75rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 0.125rem 0.5rem; border-radius: 9999px; font-weight: 600;">
                        Đã khóa
                    </span>
                @endif
            </div>
            <p style="margin: 0.25rem 0 0 0; color: #52525b; font-size: 0.875rem;">
                {{ $user->email }} · Phiên bản phiên đăng nhập: <code>v{{ $user->session_version }}</code> · Đăng ký ngày: {{ $user->created_at ? $user->created_at->format('d/m/Y') : '—' }}
            </p>
        </div>

        <a href="{{ route('admin.users.index') }}" class="button button-secondary" style="font-size: 0.875rem; text-decoration: none;">
            ← Quay lại danh sách
        </a>
    </div>

    {{-- Account Actions Panel --}}
    <section class="panel" style="background: #fff; border: 1px solid #e4e4e7; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem;">
        <h2 style="font-size: 1rem; font-weight: 600; margin: 0 0 0.75rem 0; padding-bottom: 0.5rem; border-bottom: 1px solid #f4f4f5;">
            Thao tác tài khoản
        </h2>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            @if($user->status === 'active')
                <form method="POST" action="{{ route('admin.users.action', [$user, 'lock']) }}">
                    @csrf
                    <button type="submit" class="button button-danger" style="font-size: 0.8125rem; background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5;">
                        Khóa tài khoản
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.action', [$user, 'unlock']) }}">
                    @csrf
                    <button type="submit" class="button button-primary" style="font-size: 0.8125rem;">
                        Mở khóa tài khoản
                    </button>
                </form>
            @endif

            <form method="POST" action="{{ route('admin.users.action', [$user, 'revoke-sessions']) }}">
                @csrf
                <button type="submit" class="button button-secondary" style="font-size: 0.8125rem;">
                    Thu hồi mọi phiên đăng nhập
                </button>
            </form>

            <form method="POST" action="{{ route('admin.users.action', [$user, 'password-reset']) }}">
                @csrf
                <button type="submit" class="button button-secondary" style="font-size: 0.8125rem;">
                    Gửi email đặt lại mật khẩu
                </button>
            </form>
        </div>
    </section>

    {{-- Roles Assignment Panel (under can('roles.manage')) --}}
    @can('roles.manage')
        <section class="panel" style="background: #fff; border: 1px solid #e4e4e7; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1rem; font-weight: 600; margin: 0 0 0.75rem 0; padding-bottom: 0.5rem; border-bottom: 1px solid #f4f4f5;">
                Gán vai trò cho người dùng
            </h2>
            <form method="POST" action="{{ route('admin.users.action', [$user, 'roles']) }}">
                @csrf
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
                    @foreach($roles as $role)
                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.625rem; border: 1px solid #e4e4e7; border-radius: 6px; background: #fafafa; cursor: pointer;">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($user->roles->contains('id', $role->id))>
                            <div>
                                <strong style="display: block; font-size: 0.875rem; color: #18181b;">{{ $role->name }}</strong>
                                <code style="font-size: 0.75rem; color: #71717a;">{{ $role->code }}</code>
                            </div>
                        </label>
                    @endforeach
                </div>
                <button type="submit" class="button button-primary" style="font-size: 0.875rem;">
                    Lưu vai trò cho người dùng
                </button>
            </form>
        </section>
    @endcan

    {{-- Subscription Plan Panel --}}
    <section class="panel" style="background: #fff; border: 1px solid #e4e4e7; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem;">
        <h2 style="font-size: 1rem; font-weight: 600; margin: 0 0 0.75rem 0; padding-bottom: 0.5rem; border-bottom: 1px solid #f4f4f5;">
            Gói dịch vụ hội viên
        </h2>
        <form method="POST" action="{{ route('admin.users.action', [$user, 'subscription']) }}" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            @csrf
            <div>
                <label for="plan_id" style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.25rem;">Gói cước</label>
                <select id="plan_id" name="plan_id" class="input" style="padding: 0.4375rem 0.625rem; border: 1px solid #d4d4d8; border-radius: 4px; font-size: 0.875rem;">
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected($subscription?->subscription_plan_id === $plan->id)>
                            {{ $plan->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="expires_at" style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.25rem;">Ngày hết hạn (tùy chọn)</label>
                <input id="expires_at" name="expires_at" type="datetime-local" class="input" style="padding: 0.4375rem 0.625rem; border: 1px solid #d4d4d8; border-radius: 4px; font-size: 0.875rem;">
            </div>
            <button type="submit" class="button button-primary" style="font-size: 0.875rem; padding: 0.4375rem 0.875rem;">
                Cập nhật gói cước
            </button>
        </form>
    </section>

    {{-- History & Related Records --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        {{-- Exam Attempts --}}
        <section class="panel" style="background: #fff; border: 1px solid #e4e4e7; border-radius: 8px; padding: 1.25rem;">
            <h2 style="font-size: 1rem; font-weight: 600; margin: 0 0 0.75rem 0; padding-bottom: 0.5rem; border-bottom: 1px solid #f4f4f5;">
                Lịch sử thi ({{ $attempts->count() }})
            </h2>
            <div style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 300px; overflow-y: auto;">
                @forelse($attempts as $attempt)
                    <div style="padding: 0.5rem; border: 1px solid #f4f4f5; border-radius: 4px; font-size: 0.8125rem; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>#{{ $attempt->id }}</strong> · {{ $attempt->exam->title ?? 'Bài thi' }}
                        </div>
                        <span style="color: #71717a;">{{ $attempt->status }}</span>
                    </div>
                @empty
                    <p style="color: #71717a; font-size: 0.875rem; margin: 0;">Chưa có lượt làm bài nào.</p>
                @endforelse
            </div>
        </section>

        {{-- Reports --}}
        <section class="panel" style="background: #fff; border: 1px solid #e4e4e7; border-radius: 8px; padding: 1.25rem;">
            <h2 style="font-size: 1rem; font-weight: 600; margin: 0 0 0.75rem 0; padding-bottom: 0.5rem; border-bottom: 1px solid #f4f4f5;">
                Báo cáo liên quan ({{ $reports->count() }})
            </h2>
            <div style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 300px; overflow-y: auto;">
                @forelse($reports as $report)
                    <div style="padding: 0.5rem; border: 1px solid #f4f4f5; border-radius: 4px; font-size: 0.8125rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <strong>Lượt thi #{{ $report->exam_attempt_id }}</strong>
                            <span style="color: #71717a;">{{ $report->status }}</span>
                        </div>
                        <div style="color: #52525b; font-size: 0.75rem; margin-top: 0.25rem;">
                            Lý do: {{ $report->reason }}
                        </div>
                    </div>
                @empty
                    <p style="color: #71717a; font-size: 0.875rem; margin: 0;">Không có báo cáo nào liên quan.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
