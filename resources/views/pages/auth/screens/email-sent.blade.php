<div class="auth-state-icon">@include('pages.dictionary.icon', ['name' => 'check'])</div>
<span class="eyebrow">KIỂM TRA HỘP THƯ</span>
<h2>{{ $page['title'] }}</h2>
<p>{{ $page['description'] }}</p>
<div class="notice-box">Nếu email này có tài khoản Vistiq, hướng dẫn khôi phục sẽ được gửi đến hộp thư của bạn.</div>
<a class="button button-primary button-full" href="{{ route('login') }}">Quay lại đăng nhập</a>
<a class="text-link auth-resend" href="{{ route('password.request') }}">Gửi lại yêu cầu khôi phục</a>
