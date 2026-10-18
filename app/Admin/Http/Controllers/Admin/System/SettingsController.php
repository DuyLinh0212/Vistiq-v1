<?php

namespace VistiqAdmin\Http\Controllers\Admin\System;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use VistiqAdmin\Http\Controllers\Admin\AdminPageController;
use VistiqAdmin\Http\Requests\Admin\Shared\AdminActionRequest;
use VistiqAdmin\Http\Requests\Admin\System\AuditFilterRequest;
use VistiqAdmin\Http\Requests\Admin\System\SettingsUpdateRequest;
use VistiqCore\Services\Shared\AiService;
use VistiqCore\Services\System\AuditService;
use VistiqCore\Services\System\SettingsService;

class SettingsController extends AdminPageController
{
    public function index(Request $request, SettingsService $settings): mixed
    {
        $selectedGroup = $request->query('group', 'general');
        abort_unless(array_key_exists($selectedGroup, SettingsService::DEFAULTS), 404);
        $configured = [];
        $groups = [];
        foreach (array_keys(SettingsService::DEFAULTS) as $group) {
            $value = $settings->get($group);
            foreach (['password', 'api_key', 'key', 'secret'] as $field) {
                if (array_key_exists($field, $value)) {
                    $configured[$group][$field] = filled($value[$field]);
                    $value[$field] = '';
                }
            } $groups[$group] = $value;
        }
        $groups['scoring']['scales'] = DB::table('score_scales')->get()->map(fn ($scale) => ['id' => $scale->id, 'name' => $scale->name, 'version' => $scale->version, 'configuration' => json_decode($scale->configuration ?? '{}', true)])->all();
        foreach ($groups['scoring']['scales'] as &$scale) {
            unset($scale['version']);
        } unset($scale);
        $groups['subscription']['plans'] = DB::table('subscription_plans')->get()->map(fn ($plan) => ['code' => $plan->code, 'name' => $plan->name, 'benefits' => json_decode($plan->benefits ?? '[]', true), 'is_active' => (bool) $plan->is_active])->all();

        return $this->page('Cài đặt hệ thống', 'pages.admin.system.workspace.settings', compact('groups', 'selectedGroup', 'configured'));
    }

    public function update(SettingsUpdateRequest $request, string $group, SettingsService $settings): mixed
    {
        $value = $request->validated('value');
        $effective = array_replace($settings->get($group), $value);
        foreach (['password', 'api_key', 'key', 'secret'] as $secret) {
            if (array_key_exists($secret, $value) && blank($value[$secret])) {
                $effective[$secret] = $settings->get($group)[$secret] ?? '';
            }
        }
        if ($group === 'security' && $value['auto_submit_threshold'] > 0 && $value['warning_threshold'] > $value['auto_submit_threshold']) {
            throw ValidationException::withMessages(['value.auto_submit_threshold' => 'Ngưỡng tự nộp phải lớn hơn hoặc bằng ngưỡng cảnh báo.']);
        }
        if ($group === 'email' && $value['mailer'] === 'smtp' && blank($value['host'] ?? null)) {
            throw ValidationException::withMessages(['value.host' => 'Nhập máy chủ SMTP trước khi chọn gửi bằng SMTP.']);
        }
        if ($group === 'email' && $settings->get('security')['admin_otp'] && $value['mailer'] !== 'smtp') {
            throw ValidationException::withMessages(['value.mailer' => 'Tắt OTP Admin trước khi tắt SMTP.']);
        }
        if ($group === 'ai' && $value['enabled']) {
            foreach (['model', 'api_key'] as $field) {
                if (blank($effective[$field])) {
                    throw ValidationException::withMessages(['value.'.$field => 'Cấu hình mô hình và API key trước khi bật AI.']);
                }
            }
            if ($value['speaking_evaluation'] && (! $value['audio_capable'] || blank($value['transcription_model']))) {
                throw ValidationException::withMessages(['value.transcription_model' => 'Chấm Speaking cần hỗ trợ âm thanh và mô hình chuyển giọng nói.']);
            }
        }
        if ($group === 'storage' && $value['disk'] === 's3') {
            foreach (['bucket', 'key', 'secret'] as $field) {
                if (blank($effective[$field])) {
                    throw ValidationException::withMessages(['value.'.$field => 'Cấu hình bucket, access key và secret key trước khi dùng S3.']);
                }
            }
        }
        if ($group === 'security' && $value['admin_otp']) {
            $email = $settings->get('email');
            if ($email['mailer'] !== 'smtp' || blank($email['host'])) {
                throw ValidationException::withMessages(['value.admin_otp' => 'Cấu hình email SMTP trước khi bật OTP.']);
            }
        }
        DB::transaction(function () use ($group, $value, $settings): void {
            foreach ($value['scales'] ?? [] as $scale) {
                $attributes = ['name' => $scale['name'], 'configuration' => json_encode($scale['configuration']), 'updated_at' => now()];
                if (isset($scale['id'])) {
                    $current = DB::table('score_scales')->where('id', $scale['id'])->lockForUpdate()->first();
                    DB::table('score_scales')->where('id', $scale['id'])->update([...$attributes, 'version' => $current->version + 1]);
                } else {
                    DB::table('score_scales')->insert([...$attributes, 'version' => 1, 'created_at' => now()]);
                }
            }
            foreach ($value['plans'] ?? [] as $plan) {
                DB::table('subscription_plans')->updateOrInsert(['code' => $plan['code']], ['name' => $plan['name'], 'benefits' => json_encode($plan['benefits']), 'is_active' => $plan['is_active'], 'created_at' => now(), 'updated_at' => now()]);
            }
            unset($value['scales'],$value['plans']);
            $settings->put($group, $value);
        });

        return back()->with('status', 'Đã lưu cấu hình '.$group.'.');
    }

    public function audit(AuditFilterRequest $request, AuditService $audit): mixed
    {
        $filters = $request->validated();
        $query = DB::table('audit_logs')->leftJoin('users', 'users.id', '=', 'audit_logs.actor_id')->select('audit_logs.*', 'users.name as actor_name');
        foreach (['actor' => 'actor_id', 'action' => 'action', 'entity' => 'entity_type', 'entity_id' => 'entity_id', 'result' => 'result'] as $filter => $column) {
            if (filled($filters[$filter] ?? null)) {
                $query->where('audit_logs.'.$column, $filters[$filter]);
            }
        }
        if (filled($filters['from'] ?? null)) {
            $query->where('audit_logs.created_at', '>=', $filters['from'].' 00:00:00');
        }
        if (filled($filters['to'] ?? null)) {
            $query->where('audit_logs.created_at', '<', Carbon::parse($filters['to'])->addDay()->toDateString());
        }
        $logs = $query->orderByDesc('audit_logs.id')->paginate(30)->withQueryString();
        foreach ($logs as $log) {
            foreach (['before', 'after'] as $field) {
                $log->$field = $audit->redact(json_decode($log->$field ?? '{}', true) ?: []);
            }
        }
        $actors = DB::table('users')->whereIn('id', DB::table('audit_logs')->select('actor_id'))->select('id', 'name')->orderBy('name')->get();

        return $this->page('Audit log', 'pages.admin.system.workspace.audit', compact('logs', 'filters', 'actors'));
    }

    public function jobs(SettingsService $settings): mixed
    {
        $jobs = DB::table('jobs')->select('id', 'queue', 'attempts', 'reserved_at', 'available_at')->latest('id')->paginate(20, ['*'], 'jobs_page')->withQueryString();
        $failed = DB::table('failed_jobs')->select('id', 'queue', 'failed_at')->latest('id')->paginate(20, ['*'], 'failed_page')->withQueryString();
        $aiEnabled = $settings->get('ai')['enabled'];
        $tasks = $aiEnabled ? DB::table('ai_tasks')->select('id', 'purpose', 'status', 'created_at')->latest('id')->paginate(20, ['*'], 'ai_page')->withQueryString() : null;

        return $this->page('Trạng thái tác vụ', 'pages.admin.system.workspace.jobs', compact('jobs', 'failed', 'tasks', 'aiEnabled'));
    }

    public function ai(AdminActionRequest $request, AiService $ai): mixed
    {
        $data = $request->validated();
        abort_unless(! empty($data['purpose']) && ! empty($data['input']), 422);

        return response()->json(['id' => $ai->enqueue($data['purpose'], $data['input'], $request->user()->id)], 202);
    }

    public function aiStatus(int $task): mixed
    {
        $task = DB::table('ai_tasks')->find($task);
        abort_unless($task, 404);
        abort_unless((int) $task->actor_id === auth()->id() || auth()->user()->hasPermission('results.grade'), 404);

        return response()->json(['id' => $task->id, 'status' => $task->status, 'result' => json_decode($task->result ?? 'null', true), 'error' => $task->error]);
    }
}
