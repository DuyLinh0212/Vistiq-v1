<?php

namespace VistiqAdmin\Http\Controllers\Admin;

use VistiqAdmin\Http\Requests\Admin\AdminActionRequest;
use VistiqCore\Models\ExamTemplate;
use VistiqCore\Services\AuditService;
use VistiqCore\Services\LeaderboardService;

class LeaderboardController extends AdminPageController
{
    public function index(LeaderboardService $leaderboard): mixed
    {
        return $this->page('Bảng xếp hạng', 'pages.admin.workspace.leaderboard', ['rankings' => $leaderboard->rankings(), 'exams' => ExamTemplate::where('status', 'published')->get()]);
    }

    public function reset(AdminActionRequest $request, ExamTemplate $exam, AuditService $audit): mixed
    {
        $exam->update(['leaderboard_reset_at' => now()]);
        $audit->record('leaderboard.reset', 'exam', $exam->id);

        return back()->with('status', 'Đã mở kỳ xếp hạng mới; lịch sử bài thi được giữ.');
    }
}
