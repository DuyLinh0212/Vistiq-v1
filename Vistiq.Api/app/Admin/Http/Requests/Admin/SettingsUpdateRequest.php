<?php

namespace VistiqAdmin\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class SettingsUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('settings.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $schemas = [
            'general' => ['site_name' => 'required|string|max:100', 'timezone' => 'required|timezone', 'learner_url' => 'required|url', 'admin_url' => 'required|url'],
            'exam' => ['duration_minutes' => 'required|integer|min:1|max:480', 'allow_mobile' => 'required|boolean', 'autosave_seconds' => 'required|integer|min:1|max:60'],
            'security' => ['admin_otp' => 'required|boolean', 'log_ip' => 'required|boolean', 'warning_threshold' => 'required|integer|min:0|max:100', 'auto_submit_threshold' => 'required|integer|min:0|max:100'],
            'email' => ['mailer' => 'required|in:log,smtp', 'host' => 'nullable|string|max:255', 'port' => 'required|integer|min:1|max:65535', 'username' => 'nullable|string|max:255', 'password' => 'nullable|string|max:2000', 'from_address' => 'required|email', 'from_name' => 'required|string|max:100'],
            'ai' => ['enabled' => 'required|boolean', 'endpoint' => 'required|url:http,https', 'model' => 'nullable|string|max:100', 'api_key' => 'nullable|string|max:2000', 'daily_limit' => 'required|integer|min:1|max:10000', 'audio_capable' => 'required|boolean', 'transcription_model' => 'nullable|string|max:100', 'writing_evaluation' => 'required|boolean', 'speaking_evaluation' => 'required|boolean', 'translation' => 'required|boolean', 'explanation_generation' => 'required|boolean'],
            'media' => ['max_megabytes' => 'required|integer|min:1|max:100', 'cdn_url' => 'nullable|url:https'],
            'storage' => ['disk' => 'required|in:public,s3', 'key' => 'nullable|string|max:2000', 'secret' => 'nullable|string|max:2000', 'region' => 'required|string|max:100', 'bucket' => 'nullable|string|max:100', 'endpoint' => 'nullable|url', 'url' => 'nullable|url'],
            'scoring' => ['estimated' => 'required|boolean', 'scales' => 'sometimes|array|max:20', 'scales.*.name' => 'required|string|max:140', 'scales.*.id' => 'sometimes|integer|exists:score_scales,id', 'scales.*.configuration' => 'required|array', 'scales.*.configuration.tables' => 'required|array', 'scales.*.configuration.tables.*' => 'required|array', 'scales.*.configuration.tables.*.*' => 'required|numeric|min:0|max:990'],
            'subscription' => ['enabled' => 'required|boolean', 'plans' => 'sometimes|array|max:30', 'plans.*.code' => 'required|alpha_dash|max:40', 'plans.*.name' => 'required|string|max:100', 'plans.*.benefits' => 'required|array', 'plans.*.is_active' => 'required|boolean'],
            'feature_flags' => ['leaderboard' => 'required|boolean', 'ai' => 'required|boolean'],
        ];
        $schema = $schemas[$this->route('group')] ?? [];
        abort_unless($schema, 404);
        $rules = ['value' => 'required|array:'.implode(',', array_keys(array_filter($schema, fn ($key) => ! str_contains($key, '.'), ARRAY_FILTER_USE_KEY)))];
        foreach ($schema as $key => $rule) {
            $rules['value.'.$key] = $rule;
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('value_json')) {
            $value = json_decode($this->input('value_json'), true);
            if (! is_array($value)) {
                throw ValidationException::withMessages(['value' => 'Cấu hình phải là JSON object.']);
            } $this->merge(['value' => $value]);
        }
    }
}
