<?php

namespace VistiqAdmin\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use VistiqAdmin\Http\Requests\Admin\AdminActionRequest;
use VistiqAdmin\Http\Requests\Admin\SettingsUpdateRequest;
use VistiqCore\Services\AiService;
use VistiqCore\Services\SettingsService;

class SettingsController extends AdminPageController
{
    public function index(SettingsService $settings): mixed
    {
        $groups = [];
        foreach (array_keys(SettingsService::DEFAULTS) as $group) {
            $value = $settings->get($group);
            foreach (['password', 'api_key', 'key', 'secret'] as $field) {
                if (array_key_exists($field, $value)) {
                    $value[$field] = '';
                }
            } $groups[$group] = $value;
        }
        $groups['scoring']['scales'] = DB::table('score_scales')->get()->map(fn ($scale) => ['id' => $scale->id, 'name' => $scale->name, 'version' => $scale->version, 'configuration' => json_decode($scale->configuration ?? '{}', true)])->all();
        foreach ($groups['scoring']['scales'] as &$scale) {
            unset($scale['version']);
        } unset($scale);
        $groups['subscription']['plans'] = DB::table('subscription_plans')->get()->map(fn ($plan) => ['code' => $plan->code, 'name' => $plan->name, 'benefits' => json_decode($plan->benefits ?? '[]', true), 'is_active' => (bool) $plan->is_active])->all();

        return $this->page('Cài đặt hệ thống', 'pages.admin.workspace.settings', compact('groups'));
    }

    public function update(SettingsUpdateRequest $request, string $group, SettingsService $settings): mixed
    {
        $value = $request->validated('value');
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

    public function audit(): mixed
    {
        return $this->page('Audit log', 'pages.admin.workspace.audit', ['logs' => DB::table('audit_logs')->latest('id')->paginate(30)]);
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
