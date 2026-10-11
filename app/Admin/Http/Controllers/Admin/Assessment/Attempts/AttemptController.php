<?php

namespace VistiqAdmin\Http\Controllers\Admin\Assessment\Attempts;

use VistiqAdmin\Http\Controllers\Admin\AdminPageController;
use Illuminate\Support\Facades\DB;
use VistiqAdmin\Http\Requests\Admin\Shared\AdminActionRequest;
use VistiqCore\Models\ExamAttempt;
use VistiqCore\Services\System\AuditService;

class AttemptController extends AdminPageController
{
    public function index(): mixed
    {
        return $this->page('Lượt thi', 'pages.admin.assessment.attempts.workspace.index', ['attempts' => ExamAttempt::with(['user', 'exam'])->latest()->paginate(20)]);
    }

    public function detail(ExamAttempt $attempt): mixed
    {
        $attempt->load(['user', 'exam', 'answers']);
        $events = DB::table('attempt_events')->join('attempt_event_types', 'attempt_event_types.id', '=', 'attempt_events.attempt_event_type_id')->where('exam_attempt_id', $attempt->id)->orderBy('occurred_at')->get();
        $reports = DB::table('attempt_reports')->where('exam_attempt_id', $attempt->id)->get();
        $result = DB::table('attempt_results')->where('exam_attempt_id', $attempt->id)->first();

        return $this->page('Chi tiết lượt thi #'.$attempt->id, 'pages.admin.assessment.attempts.workspace.detail', compact('attempt', 'events', 'reports', 'result'));
    }

    public function moderate(AdminActionRequest $request, ExamAttempt $attempt, AuditService $audit): mixed
    {
        abort_unless($request->filled('leaderboard_status') && $request->filled('note'), 422, 'Nhập quyết định và lý do.');
        DB::transaction(function () use ($request, $attempt, $audit): void {
            $attempt = ExamAttempt::lockForUpdate()->findOrFail($attempt->id);
            $before = ['leaderboard_status' => $attempt->leaderboard_status];
            $attempt->update(['leaderboard_status' => $request->validated('leaderboard_status')]);
            DB::table('attempt_reports')->where('exam_attempt_id', $attempt->id)->where('status', 'pending')->update(['status' => 'reviewed', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'updated_at' => now()]);
            $audit->record('attempt.moderate', 'attempt', $attempt->id, $before, ['leaderboard_status' => $attempt->leaderboard_status, 'note' => $request->validated('note')]);
        });

        return back()->with('status', 'Đã lưu quyết định kiểm duyệt.');
    }
}
