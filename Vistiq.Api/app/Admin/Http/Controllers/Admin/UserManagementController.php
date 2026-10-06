<?php

namespace VistiqAdmin\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use VistiqAdmin\Http\Requests\Admin\AdminActionRequest;
use VistiqAdmin\Http\Requests\Admin\UserActionRequest;
use VistiqCore\Models\ExamAttempt;
use VistiqCore\Models\Permission;
use VistiqCore\Models\Role;
use VistiqCore\Models\User;
use VistiqCore\Services\AuditService;
use VistiqCore\Services\UserManagementService;

class UserManagementController extends AdminPageController
{
    public function index(Request $request): mixed
    {
        $q = trim($request->string('q')->toString());

        return $this->page('Người dùng', 'pages.admin.workspace.users', ['users' => User::with('roles')->when($q, fn ($query) => $query->where(fn ($where) => $where->where('email', 'like', '%'.$q.'%')->orWhere('name', 'like', '%'.$q.'%')))->latest()->paginate(20)->withQueryString()]);
    }

    public function show(User $user): mixed
    {
        $user->load('roles');
        $reports = DB::table('attempt_reports')->join('exam_attempts', 'exam_attempts.id', '=', 'attempt_reports.exam_attempt_id')->join('attempt_report_reasons', 'attempt_report_reasons.id', '=', 'attempt_reports.attempt_report_reason_id')->where(fn ($query) => $query->where('exam_attempts.user_id', $user->id)->orWhere('attempt_reports.reported_by', $user->id))->select('attempt_reports.*', 'attempt_report_reasons.name as reason')->latest('attempt_reports.created_at')->limit(50)->get();

        return $this->page('Hồ sơ '.$user->name, 'pages.admin.workspace.user', ['user' => $user, 'reports' => $reports, 'roles' => Role::all(), 'attempts' => ExamAttempt::with('exam')->where('user_id', $user->id)->latest()->limit(30)->get(), 'saved' => DB::table('user_saved_items')->join('learning_items', 'learning_items.id', '=', 'user_saved_items.learning_item_id')->where('user_id', $user->id)->limit(50)->get(), 'activities' => DB::table('user_activities')->where('user_id', $user->id)->latest('occurred_at')->limit(50)->get(), 'sessions' => DB::table('sessions')->where('user_id', $user->id)->get(), 'plans' => DB::table('subscription_plans')->where('is_active', true)->get(), 'subscription' => DB::table('user_subscriptions')->where('user_id', $user->id)->first()]);
    }

    public function action(UserActionRequest $request, User $user, string $action, UserManagementService $service): mixed
    {
        $service->action($user, $action, $request->validated());

        return back()->with('status', 'Đã cập nhật tài khoản.');
    }

    public function roles(): mixed
    {
        return $this->page('Vai trò và quyền', 'pages.admin.workspace.roles', ['roles' => Role::with('permissions')->get(), 'permissions' => Permission::all()]);
    }

    public function updateRole(AdminActionRequest $request, Role $role, AuditService $audit): mixed
    {
        Gate::authorize('roles.manage');
        abort_if($role->code === 'super-admin', 409, 'Super Admin luôn có toàn bộ quyền.');
        DB::transaction(function () use ($request, $role, $audit): void {
            $before = $role->permissions()->pluck('code')->all();
            $role->permissions()->sync($request->validated('permissions', []));
            $audit->record('role.update', 'role', $role->id, ['permissions' => $before], ['permissions' => $role->permissions()->pluck('code')->all()]);
        });

        return back()->with('status', 'Đã cập nhật quyền vai trò.');
    }
}
