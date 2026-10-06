<span class="eyebrow">ADMIN STUDIO</span>
<h2>Khôi phục mật khẩu quản trị</h2>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
@if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
<form class="auth-form" method="post" action="{{ route(isset($token)?'password.update':'password.email') }}">
@csrf
<label>Email<input type="email" name="email" value="{{ old('email',$email??'') }}" required autocomplete="username"></label>
@if(isset($token))
<input type="hidden" name="token" value="{{ $token }}">
<label>Mật khẩu mới<input name="password" type="password" minlength="10" required autocomplete="new-password"></label>
<label>Nhập lại mật khẩu<input name="password_confirmation" type="password" minlength="10" required autocomplete="new-password"></label>
@endif
<button class="button button-primary" type="submit">{{ isset($token)?'Đổi mật khẩu':'Gửi hướng dẫn khôi phục' }}</button>
</form>
<a href="{{ route('login') }}">Quay lại đăng nhập</a>
