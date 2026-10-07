<?php

namespace VistiqLearner\Http\Controllers\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use VistiqLearner\Http\Controllers\StaticPageController;
use VistiqLearner\Models\User;
use VistiqCore\Models\User as DomainUser;

class AuthController extends StaticPageController
{
    private const LOGIN_MAX_ATTEMPTS = 5;

    private const LOGIN_DECAY_SECONDS = 60;

    private const SCREENS = [
        'welcome-demo' => ['title' => 'Mở demo Vistiq English', 'frame' => '00', 'view' => 'pages.auth.cover', 'section' => 'auth', 'description' => 'Một không gian học tiếng Anh rõ ràng, tập trung và vừa sức.'],
        'login' => ['title' => 'Đăng nhập', 'frame' => 'AUTH01', 'view' => 'pages.auth.screens.login', 'section' => 'auth', 'description' => 'Chào mừng bạn trở lại Vistiq English.', 'variant' => 'login'],
        'register' => ['title' => 'Tạo tài khoản', 'frame' => 'AUTH02', 'view' => 'pages.auth.screens.register', 'section' => 'auth', 'description' => 'Bắt đầu lưu lại hành trình học tiếng Anh của bạn.', 'variant' => 'register'],
        'forgot-password' => ['title' => 'Quên mật khẩu', 'frame' => 'AUTH03', 'view' => 'pages.auth.screens.forgot', 'section' => 'auth', 'description' => 'Nhập email để nhận hướng dẫn đặt lại mật khẩu.', 'variant' => 'forgot'],
        'email-sent' => ['title' => 'Kiểm tra email', 'frame' => 'AUTH04', 'view' => 'pages.auth.screens.email-sent', 'section' => 'auth', 'description' => 'Hướng dẫn đặt lại mật khẩu đã được gửi.', 'variant' => 'email-sent'],
        'reset-password' => ['title' => 'Đặt lại mật khẩu', 'frame' => 'AUTH07', 'view' => 'pages.auth.screens.reset-password', 'section' => 'auth', 'description' => 'Tạo mật khẩu mới và an toàn cho tài khoản.', 'variant' => 'reset'],
        'password-updated' => ['title' => 'Đổi mật khẩu thành công', 'frame' => 'AUTH08', 'view' => 'pages.auth.screens.updated', 'section' => 'auth', 'description' => 'Bạn có thể đăng nhập bằng mật khẩu mới.', 'variant' => 'updated'],
    ];

    public function show(string $screen): View
    {
        return $this->renderKnownScreen($screen, self::SCREENS);
    }

    public function loginForm(): View
    {
        return $this->renderKnownScreen('login', self::SCREENS);
    }

    public function registerForm(): View
    {
        return $this->renderKnownScreen('register', self::SCREENS);
    }

    public function forgotPasswordForm(): View
    {
        return $this->renderKnownScreen('forgot-password', self::SCREENS);
    }

    public function resetPasswordForm(Request $request, string $token): View
    {
        $page = [
            ...self::SCREENS['reset-password'],
            'slug' => 'reset-password',
            'shell' => 'auth',
            'token' => $token,
            'email' => $request->query('email', ''),
        ];

        return view('shared.demo-page', ['page' => $page]);
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Nhập địa chỉ email của bạn.',
            'email.email' => 'Email chưa đúng định dạng.',
            'password.required' => 'Nhập mật khẩu của bạn.',
        ]);

        $credentials['email'] = Str::lower(trim($credentials['email']));
        $throttleKey = $this->loginThrottleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::LOGIN_MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withErrors(['email' => "Bạn đã thử đăng nhập quá nhiều lần. Hãy thử lại sau {$seconds} giây."])
                ->onlyInput('email');
        }

        if (! Auth::attempt([...$credentials, 'status' => 'active'], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, self::LOGIN_DECAY_SECONDS);

            return back()
                ->withErrors(['email' => 'Email hoặc mật khẩu không chính xác.'])
                ->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $request->session()->put('user_session_version', $request->user()->session_version);

        return redirect()->intended(route('home'));
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', PasswordRule::min(10)->letters()->numbers(), 'confirmed'],
        ], [
            'name.required' => 'Nhập họ và tên của bạn.',
            'name.max' => 'Họ và tên không được dài quá :max ký tự.',
            'email.required' => 'Nhập địa chỉ email của bạn.',
            'email.email' => 'Email chưa đúng định dạng.',
            'email.max' => 'Email không được dài quá :max ký tự.',
            'email.unique' => 'Email này đã có tài khoản.',
            'password.required' => 'Tạo mật khẩu cho tài khoản.',
            'password.min' => 'Mật khẩu cần ít nhất :min ký tự.',
            'password.letters' => 'Mật khẩu cần có ít nhất một chữ cái.',
            'password.numbers' => 'Mật khẩu cần có ít nhất một chữ số.',
            'password.confirmed' => 'Hai mật khẩu chưa khớp.',
        ]);

        $attributes['name'] = trim($attributes['name']);
        $attributes['email'] = Str::lower(trim($attributes['email']));

        $user = User::create($attributes);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
        ], [
            'email.required' => 'Nhập địa chỉ email của bạn.',
            'email.email' => 'Email chưa đúng định dạng.',
        ]);

        $credentials['email'] = Str::lower(trim($credentials['email']));
        $status = Password::sendResetLink($credentials);

        if (in_array($status, [Password::RESET_LINK_SENT, Password::INVALID_USER, Password::RESET_THROTTLED], true)) {
            return back()->with('status', 'Nếu email này có tài khoản Vistiq, hướng dẫn đặt lại mật khẩu sẽ được gửi đến hộp thư của bạn.');
        }

        return back()
            ->with('status', 'Nếu email này có tài khoản Vistiq, hướng dẫn đặt lại mật khẩu sẽ được gửi đến hộp thư của bạn.')
            ->onlyInput('email');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', PasswordRule::min(10)->letters()->numbers(), 'confirmed'],
        ], [
            'email.required' => 'Nhập địa chỉ email của bạn.',
            'email.email' => 'Email chưa đúng định dạng.',
            'password.required' => 'Tạo mật khẩu mới.',
            'password.min' => 'Mật khẩu cần ít nhất :min ký tự.',
            'password.letters' => 'Mật khẩu cần có ít nhất một chữ cái.',
            'password.numbers' => 'Mật khẩu cần có ít nhất một chữ số.',
            'password.confirmed' => 'Hai mật khẩu chưa khớp.',
        ]);

        $credentials['email'] = Str::lower(trim($credentials['email']));
        $status = Password::reset(
            $credentials,
            function (DomainUser $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Mật khẩu đã được cập nhật. Bạn có thể đăng nhập bằng mật khẩu mới.');
        }

        return back()
            ->withErrors(['email' => 'Liên kết này không hợp lệ hoặc đã hết hạn. Hãy yêu cầu liên kết mới.'])
            ->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function loginThrottleKey(Request $request): string
    {
        return Str::transliterate(Str::lower(trim((string) $request->input('email'))).'|'.$request->ip());
    }
}
