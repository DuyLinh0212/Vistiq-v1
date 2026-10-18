<?php

namespace VistiqAdmin\Http\Controllers\Admin\Learning\Knowledge;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use VistiqAdmin\Http\Controllers\Admin\AdminPageController;
use VistiqAdmin\Http\Requests\Admin\Learning\Knowledge\KnowledgeImportRequest;
use VistiqAdmin\Http\Requests\Admin\Learning\Knowledge\KnowledgeUpsertRequest;
use VistiqCore\Models\LearningItem;
use VistiqCore\Services\Knowledge\KnowledgeImportService;
use VistiqCore\Services\Knowledge\KnowledgeItemService;

class KnowledgeAdminController extends AdminPageController
{
    public function index(Request $request, KnowledgeItemService $service): mixed
    {
        $listing = $service->listing($request);

        return $this->page('Kho kiến thức', 'pages.admin.learning.knowledge.workspace.index', $listing);
    }

    public function create(Request $request, KnowledgeItemService $service): mixed
    {
        $payload = $service->editorPayload(new LearningItem, $request->string('type')->toString());

        return $this->page('Biên soạn kiến thức', 'pages.admin.learning.knowledge.workspace.editor', $payload);
    }

    public function edit(LearningItem $knowledge, KnowledgeItemService $service): mixed
    {
        $payload = $service->editorPayload($knowledge);

        return $this->page('Biên soạn kiến thức', 'pages.admin.learning.knowledge.workspace.editor', $payload);
    }

    public function preview(LearningItem $knowledge, KnowledgeItemService $service): mixed
    {
        return $this->page('Xem kiến thức', 'pages.admin.learning.knowledge.workspace.read', $service->editorPayload($knowledge));
    }

    public function store(KnowledgeUpsertRequest $request, KnowledgeItemService $service): mixed
    {
        $item = $service->store($request->validated());

        return redirect()->route('admin.knowledge.edit', $item)->with('status', 'Đã tạo kiến thức bản nháp.');
    }

    public function update(KnowledgeUpsertRequest $request, LearningItem $knowledge, KnowledgeItemService $service): mixed
    {
        $service->update($knowledge, $request->validated());

        return $request->expectsJson()
            ? response()->json(['revision_counter' => $knowledge->fresh()->revision_counter])
            : back()->with('status', 'Đã lưu bản nháp cần duyệt.');
    }

    public function destroy(Request $request, LearningItem $knowledge, KnowledgeItemService $service): mixed
    {
        abort_unless($request->user()?->hasPermission('content.edit'), 403, 'Bạn không có quyền xóa nội dung.');

        $service->destroy($knowledge);

        return redirect()->route('admin.knowledge.index')->with('status', 'Đã xóa bản nháp kiến thức.');
    }

    public function importForm(Request $request): mixed
    {
        $type = $request->string('type', 'vocabulary')->toString();

        return $this->page('Import kiến thức', 'pages.admin.learning.knowledge.workspace.import', [
            'type' => $type,
        ]);
    }

    public function importPreview(KnowledgeImportRequest $request, KnowledgeImportService $importer): mixed
    {
        $preview = $importer->preview($request->file('file'), $request->validated('type'));
        $previous = $request->session()->get('knowledge_import');
        if ($previous && isset($previous['source'])) {
            Storage::disk('local')->delete($previous['source']);
        }
        $state = [
            'preview' => $preview, 'type' => $request->validated('type'),
            'duplicatePolicy' => $request->input('duplicate_policy', 'skip'),
            'filename' => $request->file('file')->getClientOriginalName(),
            'previewToken' => Str::random(48), 'actor' => $request->user()->id,
            'expires' => now()->addMinutes(20)->timestamp,
            'source' => $request->file('file')->store('knowledge-imports', 'local'),
        ];
        $request->session()->put('knowledge_import', $state);

        return $this->page('Xem trước import', 'pages.admin.learning.knowledge.workspace.import-preview', $state);
    }

    public function importPreviewShow(Request $request, string $token): mixed
    {
        $state = $request->session()->get('knowledge_import');
        abort_unless($state && hash_equals($state['previewToken'], $token) && $state['actor'] === $request->user()->id && $state['expires'] >= now()->timestamp, 404);

        return $this->page('Xem trước import', 'pages.admin.learning.knowledge.workspace.import-preview', $state);
    }

    public function importConfirm(Request $request, KnowledgeImportService $importer): mixed
    {
        abort_unless($request->user()?->hasPermission('content.edit'), 403);
        $request->validate(['preview_token' => 'required|string|size:48']);
        $state = $request->session()->get('knowledge_import');
        if (! $state || ! hash_equals($state['previewToken'], $request->input('preview_token')) || $state['actor'] !== $request->user()->id || $state['expires'] < now()->timestamp) {
            throw ValidationException::withMessages(['file' => 'Bản xem trước không hợp lệ hoặc đã hết hạn. Hãy tải lại tệp.']);
        }
        if ($state['preview']['errors']) {
            throw ValidationException::withMessages(['file' => 'Tệp còn dòng lỗi. Chưa import mục nào; hãy sửa tệp và xem trước lại.']);
        }
        $lock = Cache::store('file')->lock('knowledge-import-'.$state['previewToken'], 120);
        abort_unless($lock->get(), 409, 'Tệp đang được import. Hãy chờ kết quả.');
        try {
            abort_if(Cache::store('file')->has('knowledge-import-done-'.$state['previewToken']), 409, 'Tệp này đã được import.');
            try {
                $result = $importer->import($state['preview']['rows'], $state['type'], $state['duplicatePolicy']);
            } catch (ValidationException $exception) {
                if ($request->expectsJson()) {
                    throw $exception;
                }

                return redirect()->route('admin.knowledge.import.preview.show', $state['previewToken'])->withErrors($exception->errors());
            }
            Cache::store('file')->put('knowledge-import-done-'.$state['previewToken'], true, now()->addDay());
            Storage::disk('local')->delete($state['source']);
            $request->session()->forget('knowledge_import');
        } finally {
            $lock->release();
        }

        return redirect()->route('admin.knowledge.index', ['type' => $state['type']])
            ->with('status', "Đã import {$result['imported']} mục; bỏ qua {$result['skipped']} mục trùng theo lựa chọn. Không có dòng lỗi bị bỏ qua.");
    }

    public function export(Request $request, KnowledgeItemService $service, KnowledgeImportService $importer): mixed
    {
        $query = $service->query($request);
        $csv = $importer->exportCsv($query);
        $filename = 'knowledge-export-'.now()->format('YmdHis').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
