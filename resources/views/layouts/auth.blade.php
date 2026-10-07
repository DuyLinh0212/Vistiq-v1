@if (($page['slug'] ?? '') === 'welcome-demo')
    <main class="auth-shell auth-intro">@include($page['view'])</main>
@elseif ($hasAuthSpotlight)
    <main class="auth-shell auth-spotlight" data-auth-spotlight>@include('pages.auth.spotlight-shell')</main>
@else
    <main class="auth-shell">@include('pages.auth.shell')</main>
@endif
