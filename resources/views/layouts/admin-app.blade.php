@php
    $shell = $page['shell'] ?? 'admin';
    $assets = ['resources/css/admin.css'];
    if (isset($preview)) {
        $assets[] = 'resources/css/exam-session.css';
    }
    $assets[] = 'resources/js/admin.js';
@endphp
<!doctype html>
<html lang="vi" class="{{ $shell === 'admin' ? 'admin-document' : 'auth-document' }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $shell === 'auth' ? '#eff3f8' : '#f6f9ff' }}">
    <link rel="icon" type="image/png" href="{{ asset('images/vistiq-logo.png') }}">
    <title>{{ $page['title'] ?? 'Vistiq' }} · Vistiq</title>
    @fonts
    @vite($assets)
</head>
<body class="{{ $shell === 'admin' ? 'admin-mode' : 'auth-mode' }}">
    @yield('content')
    @include('shared.confirmation-dialog')
</body>
</html>
