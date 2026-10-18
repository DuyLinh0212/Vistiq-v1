<div class="auth-field">
    <div class="auth-label-line">
        <label for="{{ $fieldId }}">{{ $fieldLabel }}</label>
    </div>
    <div class="auth-input-wrap auth-password-wrap" data-spotlight-password>
        <svg aria-hidden="true" viewBox="0 0 20 20" focusable="false"><path d="M6 8V6a4 4 0 1 1 8 0v2m-9.5 0h11A1.5 1.5 0 0 1 17 9.5v6A1.5 1.5 0 0 1 15.5 17h-11A1.5 1.5 0 0 1 3 15.5v-6A1.5 1.5 0 0 1 4.5 8Zm5.5 3v2"/></svg>
        <span class="spotlight-password-entry">
            <input id="{{ $fieldId }}" name="{{ $fieldId }}" type="password" autocomplete="{{ $fieldAutocomplete }}" placeholder="{{ $fieldPlaceholder }}" required aria-invalid="{{ $errors->has($fieldId) ? 'true' : 'false' }}" aria-describedby="spotlight-password-help{{ $errors->has($fieldId) ? ' '.$fieldId.'-error' : '' }}" @if($minlength ?? false) data-minlength="{{ $minlength }}" @endif>
            <span class="spotlight-password-reveal" aria-hidden="true"><span></span></span>
        </span>
        <button class="spotlight-toggle" type="button" data-spotlight-toggle aria-label="Bật đèn soi {{ mb_strtolower($fieldLabel) }}" aria-controls="{{ $fieldId }}" aria-pressed="false">
            <svg class="spotlight-eye-closed" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 9s3 6 8 6 8-6 8-6M5 12l-2 2m5 0-1 3m5-2v3m4-4 1 3m2-5 2 2"/></svg>
            <svg class="spotlight-lamp-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><g data-spotlight-lamp><path d="M9 9h9v6H9zM4 7h5v10H4zM18 11h3v2h-3"/><path d="M1 9H0M1 12H0M1 15H0"/></g></svg>
        </button>
    </div>
    @if ($showForgotLink ?? false)
        <a class="auth-forgot-link" href="{{ route('password.request') }}">Quên mật khẩu?</a>
    @endif
    @if ($hasPasswordRules ?? false)
        <div class="password-rule-tracker is-empty" id="{{ $fieldId }}-rules" data-password-rules data-min="{{ $minlength ?? 8 }}" aria-live="polite">
            <div class="password-rule-bar" aria-hidden="true">
                <div class="password-rule-progress" style="width: 0%"></div>
            </div>
            <div class="password-rule-status">
                <span class="password-rule-count" data-rule-count>0/{{ $minlength ?? 8 }} ký tự</span>
                <span class="password-rule-message" data-rule-message>Tối thiểu {{ $minlength ?? 8 }} ký tự, gồm chữ và số</span>
            </div>
        </div>
    @endif
    @error($fieldId)
        <span class="auth-field-error" id="{{ $fieldId }}-error" role="alert">{{ $message }}</span>
    @enderror
</div>
