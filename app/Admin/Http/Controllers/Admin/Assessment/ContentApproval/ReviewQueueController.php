<?php

namespace VistiqAdmin\Http\Controllers\Admin\Assessment\ContentApproval;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use VistiqAdmin\Http\Controllers\Admin\AdminPageController;
use VistiqAdmin\Http\Requests\Admin\Assessment\ContentApproval\ReviewDecisionRequest;
use VistiqCore\Services\Assessment\ContentApproval\ContentReviewService;

class ReviewQueueController extends AdminPageController
{
    public function index(Request $request): mixed
    {
        $filters = $request->validate(['kind' => 'nullable|in:question,exam', 'q' => 'nullable|string|max:255', 'author' => 'nullable|integer|exists:users,id', 'from' => 'nullable|date_format:Y-m-d', 'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')], 'revision' => 'nullable|integer|min:1']);
        $questions = DB::table('content_revisions as r')->join('question_bank_items as q', 'q.draft_revision_id', '=', 'r.id')->join('learning_items as item', 'item.id', '=', 'q.learning_item_id')->where('item.status', '!=', 'archived')->where('r.kind', 'question')->whereColumn('r.entity_id', 'q.id')->where('r.status', 'review')->select('q.id')->selectRaw("'question' as kind")->addSelect('r.payload->prompt as title', 'r.number as revision', 'r.created_by as author_id', 'r.updated_at as submitted_at');
        $exams = DB::table('exam_templates as e')->where('e.draft_status', 'review')->where('e.status', '!=', 'archived')->selectRaw("e.id, 'exam' as kind, e.title, e.revision_counter as revision, e.created_by as author_id, e.updated_at as submitted_at");
        $query = DB::query()->fromSub($questions->unionAll($exams), 'queue')->leftJoin('users as u', 'u.id', '=', 'queue.author_id')->select('queue.*', 'u.name as author_name');
        if (! empty($filters['kind'])) {
            $query->where('queue.kind', $filters['kind']);
        }
        if (! empty($filters['q'])) {
            $query->where('queue.title', 'like', '%'.$filters['q'].'%');
        }
        if (! empty($filters['author'])) {
            $query->where('queue.author_id', $filters['author']);
        }
        if (! empty($filters['revision'])) {
            $query->where('queue.revision', $filters['revision']);
        }
        if (! empty($filters['from'])) {
            $query->where('queue.submitted_at', '>=', $filters['from'].' 00:00:00');
        }
        if (! empty($filters['to'])) {
            $query->where('queue.submitted_at', '<', Carbon::parse($filters['to'])->addDay()->toDateString());
        }

        return $this->page('Duyệt nội dung', 'pages.admin.assessment.content-approval.index', ['entries' => $query->orderBy('queue.submitted_at')->orderBy('queue.kind')->orderBy('queue.id')->paginate(20)->withQueryString(), 'filters' => $filters]);
    }

    public function show(string $kind, int $id, ContentReviewService $service): mixed
    {
        return $this->page('Duyệt revision', 'pages.admin.assessment.content-approval.show', $service->preview($kind, $id));
    }

    public function decide(ReviewDecisionRequest $request, string $kind, int $id, string $action, ContentReviewService $service): mixed
    {
        try {
            $result = $service->decide($kind, $id, $action, $request->validated());
        } catch (HttpExceptionInterface $exception) {
            if ($exception->getStatusCode() !== 409 || $request->expectsJson()) {
                throw $exception;
            }

            return response($this->page('Revision đã thay đổi', 'pages.admin.assessment.content-approval.show', $service->preview($kind, $id) + ['conflict' => 'Revision đã thay đổi hoặc đã được xử lý. Quyết định vừa gửi chưa được áp dụng. Hãy đọc lại nội dung mới trước khi quyết định.']), 409);
        }

        return $request->expectsJson() ? response()->json($result) : redirect()->route('admin.content-approval.show', [$kind, $id])->with('status', $action === 'approve' ? 'Đã duyệt revision. Chờ xuất bản tại module nguồn.' : 'Đã trả chỉnh sửa kèm lý do.');
    }
}
