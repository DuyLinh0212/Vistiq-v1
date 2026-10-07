<?php

namespace VistiqAdmin\Http\Controllers\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use VistiqAdmin\Http\Controllers\StaticPageController;
use VistiqAdmin\Http\Requests\Auth\PasswordRequest;
use VistiqCore\Models\User;
use VistiqCore\Services\SettingsService;

class AuthController extends StaticPageController
{
    public function loginForm(): View
    {
        return $this->renderScreen('admin-login', ['title' => 'Đăng nhập quản trị', 'frame' => 'AUTH05', 'view' => 'pages.auth.screens.admin-login', 'section' => 'auth', 'description' => 'Đăng nhập Admin Studio.', 'variant' => 'admin-login']);
    }

    public function authenticate(PasswordRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);
        $credentials['email'] = mb_strtolower(trim($credentials['email']));
        if (! Auth::attempt([...$credentials, 'status' => 'active'], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Thông tin đăng nhập chưa chính xác.'])->onlyInput('email');
        }
        if (! $request->user()->isAdmin()) {
            Auth::logout();

            return back()->withErrors(['email' => 'Tài khoản không có quyền quản trị.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        $request->session()->put('user_session_version', $request->user()->session_version);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function passwordForm(Request $request, ?string $token = null): View
    {
        return $this->renderScreen('admin-password', ['title' => 'Khôi phục mật khẩu quản trị', 'frame' => '', 'view' => 'pages.auth.screens.admin-password', 'section' => 'auth', 'description' => 'Khôi phục mật khẩu quản trị.', 'token' => $token, 'email' => $request->input('email')]);
    }

    public function sendReset(PasswordRequest $request): RedirectResponse
    {
        app(SettingsService::class)->configureMail();
        Password::sendResetLink($request->safe()->only('email'));

        return back()->with('status', 'Nếu tài khoản tồn tại, hướng dẫn khôi phục đã được gửi.');
    }

    public function resetPassword(PasswordRequest $request): RedirectResponse
    {
        $status = Password::reset($request->validated(), function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'session_version' => $user->session_version + 1])->setRememberToken(Str::random(60));
            $user->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            event(new PasswordReset($user));
        });

        return $status === Password::PasswordReset ? redirect()->route('admin.login')->with('status', 'Đã đổi mật khẩu.') : back()->withErrors(['email' => __($status)]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
