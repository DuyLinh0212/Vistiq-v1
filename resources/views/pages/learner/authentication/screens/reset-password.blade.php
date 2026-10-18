@include('pages.learner.authentication.screens.form-feedback')
<a class="auth-back" href="{{ route('password.request') }}"><span aria-hidden="true">@include('pages.dictionary.icon', ['name' => 'arrow-left'])</span> Yêu cầu liên kết mới</a>
<span class="eyebrow auth-eyebrow">CẬP NHẬT BẢO MẬT</span>
<h2>Tạo mật khẩu mới</h2>
<p>Chọn mật khẩu mới có ít nhất 8 ký tự, gồm cả chữ và số.</p>
@error('token')<div class="auth-alert" role="alert">Liên kết không hợp lệ. <a href="{{ route('password.request') }}">Yêu cầu liên kết mới</a>.</div>@enderror

<form data-auth-feedback class="auth-form" action="{{ route('password.update') }}" method="post">
    @csrf
    <input type="hidden" name="token" value="{{ old('token', $page['token'] ?? '') }}">

    <div class="auth-field">
        <label for="email">Địa chỉ email</label>
        <div class="auth-input-wrap">
            <svg aria-hidden="true" viewBox="0 0 20 20" focusable="false"><path d="M3.25 5.5A1.75 1.75 0 0 1 5 3.75h10A1.75 1.75 0 0 1 16.75 5.5v9A1.75 1.75 0 0 1 15 16.25H5a1.75 1.75 0 0 1-1.75-1.75zM4 5l6 4.75L16 5" /></svg>
            <input id="email" name="email" type="email" value="{{ old('email', $page['email'] ?? '') }}" autocomplete="email" inputmode="email" placeholder="ban@email.com" required autofocus aria-invalid="@error('email') true @else false @enderror" @error('email') aria-describedby="email-error" @enderror>
        </div>
        @error('email')
            <span class="auth-field-error" id="email-error" role="alert">{{ $message }}</span>
        @enderror
    </div>

    <div class="auth-field">
        <label for="password">Mật khẩu mới</label>
        <div class="auth-input-wrap auth-password-wrap">
            <svg aria-hidden="true" viewBox="0 0 20 20" focusable="false"><path d="M6 8V6a4 4 0 1 1 8 0v2m-9.5 0h11A1.5 1.5 0 0 1 17 9.5v6A1.5 1.5 0 0 1 15.5 17h-11A1.5 1.5 0 0 1 3 15.5v-6A1.5 1.5 0 0 1 4.5 8Zm5.5 3v2" /></svg>
            <input id="password" name="password" type="password" autocomplete="new-password" placeholder="Ít nhất 8 ký tự, gồm chữ và số" required aria-invalid="@error('password') true @else false @enderror" @error('password') aria-describedby="password-error" @enderror>
            <button class="password-toggle" type="button" data-password-toggle aria-label="Hiện mật khẩu" aria-controls="password">
                <svg aria-hidden="true" viewBox="0 0 20 20" focusable="false"><path d="M2.5 10s2.7-4.5 7.5-4.5 7.5 4.5 7.5 4.5-2.7 4.5-7.5 4.5S2.5 10 2.5 10Z"/><circle cx="10" cy="10" r="2"/></svg>
            </button>
        </div>
        @error('password')
            <span class="auth-field-error" id="password-error" role="alert">{{ $message }}</span>
        @enderror
    </div>

    <div class="auth-field">
        <label for="password_confirmation">Nhập lại mật khẩu mới</label>
        <div class="auth-input-wrap auth-password-wrap">
            <svg aria-hidden="true" viewBox="0 0 20 20" focusable="false"><path d="M6 8V6a4 4 0 1 1 8 0v2m-9.5 0h11A1.5 1.5 0 0 1 17 9.5v6A1.5 1.5 0 0 1 15.5 17h-11A1.5 1.5 0 0 1 3 15.5v-6A1.5 1.5 0 0 1 4.5 8Zm5.5 3v2" /></svg>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Nhập lại mật khẩu" required>
            <button class="password-toggle" type="button" data-password-toggle aria-label="Hiện mật khẩu" aria-controls="password_confirmation">
                <svg aria-hidden="true" viewBox="0 0 20 20" focusable="false"><path d="M2.5 10s2.7-4.5 7.5-4.5 7.5 4.5 7.5 4.5-2.7 4.5-7.5 4.5S2.5 10 2.5 10Z"/><circle cx="10" cy="10" r="2"/></svg>
            </button>
        </div>
    </div>

    <button class="button button-primary button-full auth-submit" type="submit">
        Đổi mật khẩu <span aria-hidden="true">@include('pages.dictionary.icon', ['name' => 'arrow-right'])</span>
    </button>
</form>
