<?php

namespace VistiqAdmin\Http\Controllers\Admin\Analytics;

use VistiqAdmin\Http\Controllers\Admin\AdminPageController;
use VistiqAdmin\Http\Requests\Admin\Shared\AdminActionRequest;
use VistiqCore\Models\ExamTemplate;
use VistiqCore\Services\System\AuditService;
use VistiqCore\Services\Analytics\LeaderboardService;

class LeaderboardController extends AdminPageController
{
    public function index(LeaderboardService $leaderboard): mixed
    {
        return $this->page('Bảng xếp hạng', 'pages.admin.analytics.leaderboard.workspace', ['rankings' => $leaderboard->rankings(), 'exams' => ExamTemplate::where('status', 'published')->get()]);
    }

    public function reset(AdminActionRequest $request, ExamTemplate $exam, AuditService $audit): mixed
    {
        $exam->update(['leaderboard_reset_at' => now()]);
        $audit->record('leaderboard.reset', 'exam', $exam->id);

        return back()->with('status', 'Đã mở kỳ xếp hạng mới; lịch sử bài thi được giữ.');
    }
}
