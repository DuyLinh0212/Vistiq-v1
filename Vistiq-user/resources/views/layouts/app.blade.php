@php
    $shell = $page['shell'] ?? 'user';
    $section = $page['section'] ?? '';
    $hasAuthSpotlight = $shell === 'auth' && in_array($page['slug'] ?? '', ['login', 'register'], true);
@endphp
<!doctype html>
<html lang="vi" class="{{ $shell === 'auth' ? 'auth-document' : '' }} {{ $hasAuthSpotlight ? 'auth-spotlight-document' : '' }}" @if ($hasAuthSpotlight) data-spotlight-sky="day" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $hasAuthSpotlight ? '#e7e8eb' : ($shell === 'auth' ? '#08151e' : '#f6f9ff') }}">
    <link rel="icon" type="image/png" href="{{ asset('images/vistiq-logo.png') }}">
    <title>{{ $page['title'] ?? 'Vistiq' }} · Vistiq</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ $shell === 'admin' ? 'admin-mode' : ($shell === 'auth' ? 'auth-mode' : 'user-mode') }} {{ $hasAuthSpotlight ? 'auth-spotlight-mode' : '' }} {{ $section === 'lookup' ? 'dictionary-mode' : '' }}" @if ($hasAuthSpotlight) data-spotlight-sky="day" @endif>
    @yield('content')
</body>
</html>
