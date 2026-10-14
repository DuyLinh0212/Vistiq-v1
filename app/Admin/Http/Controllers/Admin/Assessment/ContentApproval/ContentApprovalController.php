<?php

namespace VistiqAdmin\Http\Controllers\Admin\Assessment\ContentApproval;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use VistiqAdmin\Http\Controllers\Controller;
use VistiqAdmin\Http\Requests\Admin\Shared\AdminActionRequest;
use VistiqCore\Models\ExamTemplate;
use VistiqCore\Services\Assessment\ContentApproval\ContentReviewService;
use VistiqCore\Services\Assessment\ContentApproval\ContentRevisionService;
use VistiqCore\Services\Assessment\Exams\ExamPublishingService;
use VistiqCore\Services\System\AuditService;

class ContentApprovalController extends Controller
{
    public function cloneDraft(AdminActionRequest $request, string $kind, int $id, ContentRevisionService $revisions): mixed
    {
        Gate::authorize('content.edit');
        $root = $revisions->root($kind, $id);
        $revisions->save($kind, $root, $revisions->payload($kind, $root), $root->revision_counter);

        return back()->with('status', 'Đã tạo bản nháp mới từ phiên bản hiện tại.');
    }

    public function destroy(AdminActionRequest $request, string $kind, int $id, ContentRevisionService $revisions): mixed
    {
        Gate::authorize('content.edit');
        $revisions->delete($kind, $revisions->root($kind, $id));

        return back()->with('status', 'Đã xóa bản nháp chưa sử dụng.');
    }

    public function transition(AdminActionRequest $request, string $kind, int $id, string $action, ContentRevisionService $revisions): mixed
    {
        $revision = $revisions->transition($kind, $revisions->root($kind, $id), $action, $request->validated('note'));

        return $request->expectsJson()
            ? response()->json(['status' => $revision->status])
            : back()->with('status', 'Đã cập nhật trạng thái nội dung.');
    }

    public function transitionExam(AdminActionRequest $request, ExamTemplate $exam, string $action, ExamPublishingService $service, AuditService $audit): mixed
    {
        if ($action === 'publish') {
            $service->publish($exam);
        } else {
            DB::transaction(function () use ($request, $exam, $action, $service, $audit): void {
                $exam = ExamTemplate::lockForUpdate()->findOrFail($exam->id);
                $ability = match ($action) {
                    'review' => 'content.edit',
                    'approve', 'reject' => 'content.review',
                    'archive' => 'content.publish',
                    default => abort(404),
                };
                Gate::authorize($ability);
                $note = trim($request->validated('note') ?? '');
                abort_if($action === 'approve' && (int) app(ContentReviewService::class)->examAuthor($exam) === (int) auth()->id(), 403, 'Tác giả không được tự duyệt revision của mình.');
                abort_if($exam->status === 'archived' && $action !== 'archive', 409);
                $states = ['review' => 'draft', 'approve' => 'review', 'reject' => 'review', 'archive' => 'published'];
                abort_unless(($action === 'archive' ? $exam->status : $exam->draft_status) === $states[$action], 409);

                if ($action === 'reject' && $note === '') {
                    throw ValidationException::withMessages(['note' => 'Cần lý do từ chối.']);
                }

                if (in_array($action, ['review', 'approve'], true)) {
                    $errors = $service->problems($service->assemble($exam));

                    if ($errors) {
                        throw ValidationException::withMessages(['exam' => $errors]);
                    }
                }

                $before = ['status' => $action === 'archive' ? $exam->status : $exam->draft_status, 'revision' => $exam->revision_counter];
                $exam->update($action === 'archive' ? ['status' => 'archived'] : [
                    'draft_status' => match ($action) {
                        'review' => 'review',
                        'approve' => 'approved',
                        'reject' => 'draft',
                    },
                    'review_note' => $note ?: null,
                ]);
                $audit->record('exam.'.$action, 'exam', $exam->id, $before, ['status' => $action === 'archive' ? $exam->status : $exam->draft_status, 'revision' => $exam->revision_counter, 'note' => $note ?: null]);
            });
        }

        return back()->with('status', 'Đã cập nhật trạng thái đề.');
    }
}
