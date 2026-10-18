<header class="user-topbar">
    <a class="brand" href="{{ route('home') }}" aria-label="Vistiq - Trang chủ"><img class="brand-mark" src="{{ asset('images/vistiq-logo.png') }}" alt=""></a>
    <nav class="user-nav" aria-label="Điều hướng chính">
        <a class="{{ $page['section'] === 'home' ? 'is-active' : '' }}" href="{{ route('home') }}">@include('pages.dictionary.icon', ['name' => 'home']) <span>Trang chủ</span></a>
        <a class="{{ $page['section'] === 'lookup' && ($type ?? 'all') !== 'grammar' ? 'is-active' : '' }}" href="{{ route('lookup.index') }}">@include('pages.dictionary.icon', ['name' => 'search']) <span>Tra cứu</span></a>
        <a class="{{ $page['section'] === 'exams' ? 'is-active' : '' }}" href="{{ route('demo.page', 'exams') }}">@include('pages.dictionary.icon', ['name' => 'exam']) <span>Luyện đề</span></a>
        <a class="{{ $page['section'] === 'skills' ? 'is-active' : '' }}" href="{{ route('demo.page', 'skills') }}">@include('pages.dictionary.icon', ['name' => 'activity']) <span>Luyện theo kỹ năng</span></a>
        <a class="{{ $page['section'] === 'grammar' || ($type ?? 'all') === 'grammar' ? 'is-active' : '' }}" href="{{ route('lookup.index', ['type' => 'grammar']) }}">@include('pages.dictionary.icon', ['name' => 'grammar']) <span>Ngữ pháp</span></a>
        <a class="{{ $page['section'] === 'knowledge' ? 'is-active' : '' }}" href="{{ route('demo.page', 'knowledge') }}">@include('pages.dictionary.icon', ['name' => 'collection']) <span>Kho kiến thức</span></a>
        <a class="{{ request()->routeIs('attempts.history') ? 'is-active' : '' }}" href="{{ route('attempts.history') }}">@include('pages.dictionary.icon', ['name' => 'profile']) <span>Lịch sử làm đề</span></a>
    </nav>
    <div class="topbar-actions">
        <a class="icon-button" href="{{ route('lookup.index') }}" aria-label="Tìm kiếm">@include('pages.dictionary.icon', ['name' => 'search'])</a>
        <button class="icon-button" type="button" aria-label="Thông báo">@include('pages.dictionary.icon', ['name' => 'saved'])</button>
        @guest
            <a class="nav-signin" href="{{ route('login') }}">Đăng nhập</a>
            <a class="button button-primary nav-register" href="{{ route('register') }}">Tạo tài khoản</a>
        @else
            <a class="avatar" href="{{ route('demo.page', 'profile') }}" aria-label="Hồ sơ {{ auth()->user()->name }}">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</a>
            <form class="nav-logout-form" method="post" action="{{ route('logout') }}">
                @csrf
                <button class="nav-logout" type="submit">Đăng xuất</button>
            </form>
        @endguest
    </div>
    <button class="mobile-menu" aria-label="Mở điều hướng">@include('pages.dictionary.icon', ['name' => 'menu'])</button>
</header>
<main class="page-wrap">
    @include($page['view'])
</main>
<footer class="site-footer"><span>© {{ date('Y') }} Vistiq English</span><span>Học có trọng tâm · Tiến bộ mỗi ngày</span><a href="{{ route('demo.page', 'welcome-demo') }}">Giới thiệu</a><a href="{{ route('login') }}">Đăng nhập</a></footer>
