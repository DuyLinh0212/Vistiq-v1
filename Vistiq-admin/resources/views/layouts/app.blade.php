@php
    $shell = $page['shell'] ?? 'admin';
@endphp
<!doctype html>
<html lang="vi" class="{{ $shell === 'auth' ? 'auth-document' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $shell === 'auth' ? '#eff3f8' : '#f6f9ff' }}">
    <link rel="icon" type="image/png" href="{{ asset('images/vistiq-logo.png') }}">
    <title>{{ $page['title'] ?? 'Vistiq' }} · Vistiq</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ $shell === 'admin' ? 'admin-mode' : 'auth-mode' }}">
    @yield('content')
    <div class="admin-confirm" data-admin-confirm-dialog hidden>
        <div class="admin-confirm-backdrop" data-admin-confirm-cancel></div>
        <section class="admin-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="admin-confirm-title" aria-describedby="admin-confirm-message" tabindex="-1">
            <h2 id="admin-confirm-title">Xác nhận thao tác</h2>
            <p id="admin-confirm-message"></p>
            <div class="admin-confirm-actions">
                <button class="button button-quiet" type="button" data-admin-confirm-cancel>Hủy</button>
                <button class="button button-danger" type="button" data-admin-confirm-submit>Xác nhận</button>
            </div>
        </section>
    </div>
</body>
</html>
