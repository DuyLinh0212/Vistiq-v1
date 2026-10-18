<?php

namespace VistiqAdmin\Http\Controllers\Admin\System;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use VistiqAdmin\Http\Controllers\Admin\AdminPageController;
use VistiqAdmin\Http\Requests\Admin\System\BackupRequest;
use VistiqAdmin\Services\Admin\System\DatabaseOperationsService;
use VistiqCore\Services\System\AuditService;

class DatabaseBackupController extends AdminPageController
{
    public function show(Request $request, DatabaseOperationsService $operations): mixed
    {
        $pending = null;
        if ($request->filled('operation')) {
            $pending = $operations->operation($request->query('operation'));
            abort_unless($pending['kind'] === 'restore' && $pending['actor_id'] === $request->user()->id, 404);
        }

        return $this->page('Sao lưu / phục hồi', 'pages.admin.system.workspace.database', ['operations' => $operations->listing((int) $request->query('page', 1)), 'pending' => $pending]);
    }

    public function download(BackupRequest $request, DatabaseOperationsService $operations, AuditService $audit): mixed
    {
        $request->validate(['include_schema' => 'required|boolean', 'include_data' => 'required|boolean']);
        $artifact = $operations->createBackup($request->user()->id, $request->boolean('include_schema'), $request->boolean('include_data'));
        $audit->record('database.backup.download', 'database', null, [], ['artifact_id' => $artifact['id']]);

        return response()->download($operations->verifiedPath($artifact['id']), $artifact['filename'], ['Content-Type' => 'application/sql; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    public function downloadArtifact(BackupRequest $request, string $artifact, DatabaseOperationsService $operations, AuditService $audit): mixed
    {
        $path = $operations->verifiedPath($artifact);
        $audit->record('database.backup.download', 'database', null, [], ['artifact_id' => $artifact]);

        return response()->download($path, $operations->operation($artifact)['filename'], ['Content-Type' => 'application/sql; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    public function preflight(Request $request, DatabaseOperationsService $operations): mixed
    {
        $data = $request->validate(['file' => 'required|file|mimes:txt,sql|extensions:sql|max:102400']);
        $operation = $operations->preflight($data['file'], $request->user()->id);

        return redirect()->route('admin.settings.database', ['operation' => $operation['id']])->with('status', 'Đã kiểm tra file. Chưa thay đổi database; hãy kiểm tra nguồn và xác nhận bước phục hồi.');
    }

    public function restore(BackupRequest $request, DatabaseOperationsService $operations): mixed
    {
        $data = $request->validate(['preflight_token' => 'required|uuid', 'confirm_restore' => 'accepted']);
        $operation = $operations->restore($data['preflight_token'], $request->user()->id);
        if ($operation['state'] === 'succeeded') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->with('status', $operation['message']);
        }

        return redirect()->route('admin.settings.database', ['operation' => $operation['id']])->withErrors(['restore' => $operation['message']]);
    }

    public function status(Request $request, string $operation, DatabaseOperationsService $operations): mixed
    {
        $item = $operations->operation($operation);
        abort_unless($item['kind'] !== 'restore' || $item['actor_id'] === $request->user()->id, 404);

        return response()->json(collect($item)->only(['id', 'kind', 'state', 'created_at', 'started_at', 'finished_at', 'message', 'maintenance_retained', 'recovered']), 200, ['Cache-Control' => 'private, no-store']);
    }
}
