@php
    $sidebarNavigationGroups = [
        [
            'label' => 'Nội dung',
            'icon' => 'book-open',
            'items' => [
                ['route' => 'admin.questions.index', 'permission' => 'content.view', 'label' => 'Ngân hàng Câu Hỏi', 'icon' => 'grammar', 'active' => ['admin.questions.index'], 'children' => [
                    ['route' => 'admin.questions.listening', 'permission' => 'content.view', 'label' => 'Câu hỏi Listening', 'active' => ['admin.questions.listening']],
                    ['route' => 'admin.readings.index', 'permission' => 'content.view', 'label' => 'Câu hỏi Reading', 'active' => ['admin.readings.*']],
                    ['route' => 'admin.questions.thpt', 'permission' => 'content.view', 'label' => 'Câu hỏi Tiếng Anh THPT', 'active' => ['admin.questions.thpt']],
                ]],
                ['route' => 'admin.resources.index', 'permission' => 'content.view', 'label' => 'Tài nguyên', 'icon' => 'resources', 'active' => ['admin.resources.*', 'admin.audio.*', 'admin.media.*']],
                ['route' => 'admin.knowledge.index', 'permission' => 'content.view', 'label' => 'Kho kiến thức', 'icon' => 'grammar', 'active' => ['admin.knowledge.*']],
            ],
        ],
        [
            'label' => 'Thi & đánh giá',
            'icon' => 'exam',
            'items' => [
                ['route' => 'admin.exams.index', 'permission' => 'content.view', 'label' => 'Đề thi', 'icon' => 'clock', 'active' => ['admin.exams.*']],
                ['route' => 'admin.attempts.index', 'permission' => 'results.view', 'label' => 'Lượt thi & báo cáo', 'icon' => 'activity', 'active' => ['admin.attempts.*']],
                ['route' => 'admin.grading.index', 'permission' => 'results.grade', 'label' => 'Chấm Writing / Speaking', 'icon' => 'check', 'active' => ['admin.grading.*']],
                ['route' => 'admin.leaderboard.index', 'permission' => 'results.view', 'label' => 'Leaderboard', 'icon' => 'saved', 'active' => ['admin.leaderboard.*']],
                ['route' => 'admin.analytics', 'permission' => 'results.view', 'label' => 'Analytics', 'icon' => 'dashboard', 'active' => ['admin.analytics']],
            ],
        ],
        [
            'label' => 'Quản trị',
            'icon' => 'settings',
            'items' => [
                ['route' => 'admin.users.index', 'permission' => 'users.manage', 'label' => 'Người dùng', 'icon' => 'activity', 'active' => ['admin.users.*']],
                ['route' => 'admin.roles.index', 'permission' => 'roles.manage', 'label' => 'Phân quyền', 'icon' => 'check', 'active' => ['admin.roles.*']],
                ['route' => 'admin.settings.index', 'permission' => 'settings.manage', 'label' => 'Settings', 'icon' => 'collection', 'active' => ['admin.settings.*']],
                ['route' => 'admin.audit.index', 'permission' => 'audit.view', 'label' => 'Audit log', 'icon' => 'activity', 'active' => ['admin.audit.*']],
            ],
        ],
    ];
    $canSeeSidebarItem = static fn (array $item): bool => auth()->user()?->hasPermission($item['permission']) ?? false;
    $isSidebarItemActive = static fn (array $item): bool => request()->routeIs(...$item['active']);
    $sidebarNavigationGroups = collect($sidebarNavigationGroups)->map(function (array $group) use ($canSeeSidebarItem, $isSidebarItemActive): array {
        $group['items'] = collect($group['items'])->map(function (array $item) use ($canSeeSidebarItem, $isSidebarItemActive): ?array {
            if (isset($item['children'])) {
                $item['children'] = collect($item['children'])->filter($canSeeSidebarItem)->values();
                $item['is_active'] = $item['children']->contains(fn (array $child): bool => $isSidebarItemActive($child));
                return $canSeeSidebarItem($item) && $item['children']->isNotEmpty() ? $item : null;
            }

            return $canSeeSidebarItem($item) ? $item : null;
        })->filter()->values();
        $group['is_active'] = $group['items']->contains(fn (array $item): bool => $isSidebarItemActive($item) || ($item['is_active'] ?? false));

        return $group;
    })->filter(fn (array $group): bool => $group['items']->isNotEmpty())->values();
@endphp
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="brand brand-light" href="{{ route('admin.dashboard') }}"><img class="brand-mark" src="{{ asset('images/vistiq-logo.png') }}" alt="Vistiq"></a>
        <div class="sidebar-caption">ADMIN STUDIO</div>
        <nav class="admin-nav" aria-label="Điều hướng quản trị">
            @can('admin.access')
                <a class="admin-nav-overview {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}" aria-label="Tổng quan" title="Tổng quan" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                    <span class="admin-nav-link-icon">@include('pages.dictionary.icon', ['name' => 'dashboard'])</span><span class="admin-nav-link-label">Tổng quan</span>
                </a>
            @endcan
            @foreach($sidebarNavigationGroups as $group)
                <details class="admin-nav-group {{ $group['is_active'] ? 'is-active' : '' }}" data-sidebar-nav-group @if($group['is_active']) open @endif>
                    <summary title="{{ $group['label'] }}" aria-label="{{ $group['label'] }}">
                        <span class="admin-nav-group-icon">@include('pages.dictionary.icon', ['name' => $group['icon']])</span>
                        <span class="admin-nav-group-label">{{ $group['label'] }}</span>
                    </summary>
                    <div class="admin-nav-group-items">
                        @foreach($group['items'] as $item)
                            @php($itemIsActive = $isSidebarItemActive($item))
                            @if(isset($item['children']))
                                <details class="admin-nav-subgroup {{ $item['is_active'] ? 'is-active' : '' }}" @if($item['is_active']) open @endif>
                                    <summary title="{{ $item['label'] }}" aria-label="{{ $item['label'] }}"><span class="admin-nav-link-icon">@include('pages.dictionary.icon', ['name' => $item['icon']])</span><span class="admin-nav-link-label">{{ $item['label'] }}</span></summary>
                                    <div class="admin-nav-subgroup-items">
                                        @foreach($item['children'] as $child)
                                            @php($childIsActive = $isSidebarItemActive($child))
                                            <a class="{{ $childIsActive ? 'is-active' : '' }}" href="{{ route($child['route']) }}" aria-label="{{ $child['label'] }}" title="{{ $child['label'] }}" @if($childIsActive) aria-current="page" @endif><span class="admin-nav-link-label">{{ $child['label'] }}</span></a>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                                <a class="{{ $itemIsActive ? 'is-active' : '' }}" href="{{ route($item['route']) }}" aria-label="{{ $item['label'] }}" title="{{ $item['label'] }}" @if($itemIsActive) aria-current="page" @endif>
                                    <span class="admin-nav-link-icon">@if($item['icon'] === 'resources')<i data-resource-icon="folder-open"></i>@else @include('pages.dictionary.icon', ['name' => $item['icon']]) @endif</span>
                                    <span class="admin-nav-link-label">{{ $item['label'] }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </details>
            @endforeach
        </nav>
        <div class="sidebar-bottom"><p>{{ auth()->user()?->name }}</p><form method="post" action="{{ route('admin.logout') }}">@csrf<button class="button button-quiet">Đăng xuất</button></form></div>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar"><div class="breadcrumbs"><a href="{{ route('admin.dashboard') }}">Admin Studio</a></div><form method="get" action="{{ route('admin.search') }}"><input name="q" placeholder="Tìm câu hỏi, đề, kiến thức" aria-label="Tìm toàn hệ thống"></form></header>
        <main class="page-wrap admin-page">@if(session('status'))<div class="admin-toast" role="status" aria-live="polite" data-admin-toast><span>{{ session('status') }}</span><button type="button" data-dismiss-admin-toast aria-label="Đóng thông báo">×</button></div>@endif @if($errors->any())<div class="admin-alert admin-alert-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif @include($page['view'])</main>
    </div>
</div>
