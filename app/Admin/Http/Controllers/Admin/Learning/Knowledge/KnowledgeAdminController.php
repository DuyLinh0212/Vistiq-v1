<?php

namespace VistiqAdmin\Http\Controllers\Admin\Learning\Knowledge;

use Illuminate\Http\Request;
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
        $duplicatePolicy = $request->input('duplicate_policy', 'skip');

        return $this->page('Xem trước import', 'pages.admin.learning.knowledge.workspace.import-preview', [
            'preview' => $preview,
            'type' => $request->validated('type'),
            'duplicatePolicy' => $duplicatePolicy,
            'filename' => $request->file('file')->getClientOriginalName(),
        ]);
    }

    public function importConfirm(Request $request, KnowledgeImportService $importer): mixed
    {
        abort_unless($request->user()?->hasPermission('content.edit'), 403, 'Bạn không có quyền import nội dung.');

        $request->validate([
            'type' => 'required|in:vocabulary,collocation,phrasal_verb,idiom,grammar',
            'duplicate_policy' => 'required|in:skip,overwrite',
            'rows_json' => 'required|string',
        ]);

        $rows = json_decode($request->input('rows_json'), true);
        if (! is_array($rows)) {
            return redirect()->route('admin.knowledge.import')->withErrors(['file' => 'Dữ liệu import không hợp lệ.']);
        }

        $result = $importer->import(
            $rows,
            $request->string('type')->toString(),
            $request->string('duplicate_policy')->toString()
        );

        return redirect()->route('admin.knowledge.index', ['type' => $request->input('type')])
            ->with('status', "Đã import thành công {$result['imported']} mục (bỏ qua {$result['skipped']}).");
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
