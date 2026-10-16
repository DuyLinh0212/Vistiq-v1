<?php

namespace VistiqCore\Services\Identity\UsersAndPermissions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use VistiqCore\Models\Permission;
use VistiqCore\Models\Role;
use VistiqCore\Models\User;
use VistiqCore\Services\System\AuditService;
use VistiqCore\Support\PermissionCatalog;

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
                case 'lock':
                    $user->status = 'locked';
                    break;
                case 'unlock':
                    $user->status = 'active';
                    break;
                case 'revoke-sessions':
                    break;
                case 'roles':
                    $user->roles()->sync($data['roles'] ?? []);
                    break;
                case 'password-reset':
                    Password::sendResetLink(['email' => $user->email]);
                    break;
                case 'subscription':
                    abort_unless(isset($data['plan_id']) && DB::table('subscription_plans')->where('id', $data['plan_id'])->where('is_active', true)->exists(), 422);
                    DB::table('user_subscriptions')->updateOrInsert(['user_id' => $user->id], ['subscription_plan_id' => $data['plan_id'], 'expires_at' => $data['expires_at'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
                    break;
                default:
                    abort(404);
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

    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data): Role {
            $role = Role::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $permissionIds = array_map('intval', $data['permissions']);
                $catalogIds = Permission::query()
                    ->whereIn('code', PermissionCatalog::codes())
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all();

                abort_if(array_diff($permissionIds, $catalogIds) !== [], 422, 'Chỉ được gán quyền có trong danh mục hệ thống.');
                $role->permissions()->sync($permissionIds);
            }

            $this->audit->record(
                'role.create',
                'role',
                $role->id,
                [],
                [
                    'code' => $role->code,
                    'name' => $role->name,
                    'description' => $role->description,
                    'permissions' => $role->permissions()->pluck('code')->all(),
                ]
            );

            return $role;
        });
    }

    public function updateRole(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data): Role {
            $before = [
                'name' => $role->name,
                'description' => $role->description,
                'permissions' => $role->permissions()->pluck('code')->all(),
            ];

            if ($role->code === 'super-admin' && isset($data['permissions'])) {
                abort(409, 'Super Admin luôn có toàn bộ quyền và không thể chỉnh sửa.');
            }

            if (isset($data['name'])) {
                $role->name = $data['name'];
            }
            if (array_key_exists('description', $data)) {
                $role->description = $data['description'];
            }
            $role->save();

            if (isset($data['permissions']) && is_array($data['permissions']) && $role->code !== 'super-admin') {
                $permissionIds = array_map('intval', $data['permissions']);
                $catalogIds = Permission::query()
                    ->whereIn('code', PermissionCatalog::codes())
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all();

                abort_if(array_diff($permissionIds, $catalogIds) !== [], 422, 'Chỉ được gán quyền có trong danh mục hệ thống.');
                $role->permissions()->sync($permissionIds);
            }

            $after = [
                'name' => $role->name,
                'description' => $role->description,
                'permissions' => $role->permissions()->pluck('code')->all(),
            ];

            $this->audit->record('role.update', 'role', $role->id, $before, $after);

            return $role;
        });
    }

    public function deleteRole(Role $role): array
    {
        return DB::transaction(function () use ($role): array {
            abort_if(
                in_array($role->code, ['admin', 'super-admin'], true),
                409,
                'Không thể xóa vai trò mặc định của hệ thống.'
            );

            $affectedUsersCount = $role->users()->count();
            $permissionsRevoked = $role->permissions()->pluck('code')->all();

            $role->users()->detach();
            $role->permissions()->detach();

            $roleInfo = [
                'id' => $role->id,
                'code' => $role->code,
                'name' => $role->name,
                'affected_users_count' => $affectedUsersCount,
                'revoked_permissions' => $permissionsRevoked,
            ];

            $role->delete();

            $this->audit->record(
                'role.delete',
                'role',
                $roleInfo['id'],
                $roleInfo,
                ['deleted' => true]
            );

            return $roleInfo;
        });
    }
}
