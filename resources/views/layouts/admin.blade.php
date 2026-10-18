@php
    $sidebarNavigationGroups = [
        [
            'label' => 'Nội dung',
            'icon' => 'book-open',
            'items' => [
                ['route' => 'admin.content-approval.index', 'permission' => 'content.review', 'label' => 'Duyệt nội dung', 'icon' => 'check', 'active' => ['admin.content-approval.*']],
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
    $canSeeSidebarItem = static fn (array $navItem): bool => auth()->user()?->hasPermission($navItem['permission']) ?? false;
    $isSidebarItemActive = static fn (array $navItem): bool => request()->routeIs(...$navItem['active']);
    $sidebarNavigationGroups = collect($sidebarNavigationGroups)->map(function (array $navGroup) use ($canSeeSidebarItem, $isSidebarItemActive): array {
        $navGroup['items'] = collect($navGroup['items'])->map(function (array $navItem) use ($canSeeSidebarItem, $isSidebarItemActive): ?array {
            if (isset($navItem['children'])) {
                $navItem['children'] = collect($navItem['children'])->filter($canSeeSidebarItem)->values();
                $navItem['is_active'] = $navItem['children']->contains(fn (array $navChild): bool => $isSidebarItemActive($navChild));
                return $canSeeSidebarItem($navItem) && $navItem['children']->isNotEmpty() ? $navItem : null;
            }

            return $canSeeSidebarItem($navItem) ? $navItem : null;
        })->filter()->values();
        $navGroup['is_active'] = $navGroup['items']->contains(fn (array $navItem): bool => $isSidebarItemActive($navItem) || ($navItem['is_active'] ?? false));

        return $navGroup;
    })->filter(fn (array $navGroup): bool => $navGroup['items']->isNotEmpty())->values();
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
            @foreach($sidebarNavigationGroups as $navGroup)
                <details class="admin-nav-group {{ $navGroup['is_active'] ? 'is-active' : '' }}" data-sidebar-nav-group @if($navGroup['is_active']) open @endif>
                    <summary title="{{ $navGroup['label'] }}" aria-label="{{ $navGroup['label'] }}">
                        <span class="admin-nav-group-icon">@include('pages.dictionary.icon', ['name' => $navGroup['icon']])</span>
                        <span class="admin-nav-group-label">{{ $navGroup['label'] }}</span>
                    </summary>
                    <div class="admin-nav-group-items">
                        @foreach($navGroup['items'] as $navItem)
                            @php($itemIsActive = $isSidebarItemActive($navItem))
                            @if(isset($navItem['children']))
                                <details class="admin-nav-subgroup {{ $navItem['is_active'] ? 'is-active' : '' }}" @if($navItem['is_active']) open @endif>
                                    <summary title="{{ $navItem['label'] }}" aria-label="{{ $navItem['label'] }}"><span class="admin-nav-link-icon">@include('pages.dictionary.icon', ['name' => $navItem['icon']])</span><span class="admin-nav-link-label">{{ $navItem['label'] }}</span></summary>
                                    <div class="admin-nav-subgroup-items">
                                        @foreach($navItem['children'] as $navChild)
                                            @php($childIsActive = $isSidebarItemActive($navChild))
                                            <a class="{{ $childIsActive ? 'is-active' : '' }}" href="{{ route($navChild['route']) }}" aria-label="{{ $navChild['label'] }}" title="{{ $navChild['label'] }}" @if($childIsActive) aria-current="page" @endif><span class="admin-nav-link-label">{{ $navChild['label'] }}</span></a>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                                <a class="{{ $itemIsActive ? 'is-active' : '' }}" href="{{ route($navItem['route']) }}" aria-label="{{ $navItem['label'] }}" title="{{ $navItem['label'] }}" @if($itemIsActive) aria-current="page" @endif>
                                    <span class="admin-nav-link-icon">@if($navItem['icon'] === 'resources')<i data-resource-icon="folder-open"></i>@else @include('pages.dictionary.icon', ['name' => $navItem['icon']]) @endif</span>
                                    <span class="admin-nav-link-label">{{ $navItem['label'] }}</span>
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
        <main class="page-wrap admin-page">@include('shared.feedback') @include($page['view'])</main>
    </div>
</div>
