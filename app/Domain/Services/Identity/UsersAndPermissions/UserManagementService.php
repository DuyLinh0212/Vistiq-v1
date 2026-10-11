<?php

namespace VistiqCore\Services\Identity\UsersAndPermissions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use VistiqCore\Models\Role;
use VistiqCore\Models\User;
use VistiqCore\Services\System\AuditService;

class UserManagementService
{
    public function __construct(private AuditService $audit) {}

    public function action(User $user, string $action, array $data): void
    {
        DB::transaction(function () use ($user, $action, $data): void {
            $superIds = DB::table('role_user')->join('roles', 'roles.id', '=', 'role_user.role_id')->where('roles.code', 'super-admin')->pluck('user_id');
            $supers = User::whereIn('id', $superIds)->orderBy('id')->lockForUpdate()->get();
            $user = User::lockForUpdate()->findOrFail($user->id);
            $removing = $action === 'lock' || ($action === 'roles' && ! in_array((int) Role::where('code', 'super-admin')->value('id'), array_map('intval', $data['roles'] ?? []), true));
            abort_if($removing && $user->hasRole('super-admin') && $user->status === 'active' && $supers->where('status', 'active')->count() <= 1, 409, 'Không thể khóa hoặc hạ quyền Super Admin cuối cùng.');
            $before = ['status' => $user->status, 'roles' => $user->roles()->pluck('code')->all()];
            switch ($action) {
                case 'lock': $user->status = 'locked';
                    break;
                case 'unlock': $user->status = 'active';
                    break;
                case 'revoke-sessions': break;
                case 'roles': $user->roles()->sync($data['roles'] ?? []);
                    break;
                case 'password-reset': Password::sendResetLink(['email' => $user->email]);
                    break;
                case 'subscription':
                    abort_unless(isset($data['plan_id']) && DB::table('subscription_plans')->where('id', $data['plan_id'])->where('is_active', true)->exists(), 422);
                    DB::table('user_subscriptions')->updateOrInsert(['user_id' => $user->id], ['subscription_plan_id' => $data['plan_id'], 'expires_at' => $data['expires_at'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
                    break;
                default: abort(404);
            }
            if (in_array($action, ['lock', 'roles', 'revoke-sessions'], true)) {
                $user->session_version++;
                $user->setRememberToken(Str::random(60));
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            $user->save();
            $this->audit->record('user.'.$action, 'user', $user->id, $before, ['status' => $user->status, 'roles' => $user->roles()->pluck('code')->all()]);
        });
    }
}
