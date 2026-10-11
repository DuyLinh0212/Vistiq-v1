<?php

namespace VistiqAdmin\Http\Controllers\Admin\System;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use VistiqAdmin\Http\Controllers\StaticPageController;
use VistiqAdmin\Http\Requests\Admin\System\BackupRequest;
use VistiqAdmin\Services\Admin\System\DatabaseBackupService;
use VistiqCore\Services\System\AuditService;

class DatabaseBackupController extends StaticPageController
{
    private const SCREEN = [
        'title' => 'Sao lưu & phục hồi',
        'frame' => 'A15',
        'view' => 'pages.admin.system.legacy.database',
        'section' => 'admin',
        'description' => 'Xuất bản sao cơ sở dữ liệu dạng SQL hoặc phục hồi từ file Vistiq.',
    ];

    public function show(): View
    {
        return $this->renderScreen('admin-settings', self::SCREEN);
    }

    public function download(BackupRequest $request, DatabaseBackupService $backup): StreamedResponse|RedirectResponse
    {
        $validated = $request->validate([
            'include_schema' => ['required', 'boolean'],
            'include_data' => ['required', 'boolean'],
        ]);

        $includeSchema = $request->boolean('include_schema');
        $includeData = $request->boolean('include_data');

        if (! $includeSchema && ! $includeData) {
            return back()->withErrors(['backup' => 'Chọn cấu trúc, dữ liệu hoặc cả hai trước khi xuất file.']);
        }

        app(AuditService::class)->record('database.backup', 'database', null, [], ['include_schema' => $includeSchema, 'include_data' => $includeData]);
        $filename = 'vistiq-backup-'.now()->format('Y-m-d-His').'.sql';

        return response()->streamDownload(
            function () use ($backup, $includeSchema, $includeData): void {
                $backup->export($includeSchema, $includeData);
            },
            $filename,
            ['Content-Type' => 'application/sql; charset=UTF-8'],
        );
    }

    public function restore(BackupRequest $request, DatabaseBackupService $backup): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:txt,sql', 'extensions:sql', 'max:102400'],
        ]);

        $sql = file_get_contents($validated['file']->getRealPath());

        if ($sql === false) {
            return back()->withErrors(['file' => 'Không thể đọc file SQL đã tải lên.']);
        }

        ob_start();
        try {
            $backup->export(true, true);
            $recovery = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $recoveryPath = 'backups/recovery-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sql';
        abort_unless(Storage::disk('local')->put($recoveryPath, $recovery), 503, 'Không thể tạo bản sao phục hồi.');
        app(AuditService::class)->record('database.restore.request', 'database', null, [], ['recovery_path' => $recoveryPath]);
        $maintenanceRequested = false;

        try {
            $maintenanceRequested = true;
            Artisan::call('down', ['--render' => 'errors::503', '--retry' => 60]);

            $statementCount = $backup->restore($sql);

            return back()->with('status', "Đã phục hồi dữ liệu từ file SQL ({$statementCount} câu lệnh). Vistiq user đã hoạt động trở lại.");
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['file' => 'Phục hồi thất bại. Hãy kiểm tra file SQL hoặc khôi phục từ một bản sao lưu hợp lệ.']);
        } finally {
            if ($maintenanceRequested) {
                Artisan::call('up');
            }
        }
    }
}
