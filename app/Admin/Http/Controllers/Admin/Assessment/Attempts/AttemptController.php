<?php

namespace VistiqAdmin\Http\Controllers\Admin\Assessment\Attempts;

use Illuminate\Support\Facades\Gate;
use VistiqAdmin\Http\Controllers\Admin\AdminPageController;
use VistiqAdmin\Http\Requests\Admin\Assessment\Attempts\AttemptFilterRequest;
use VistiqAdmin\Http\Requests\Admin\Assessment\Attempts\ModerationRequest;
use VistiqCore\Models\ExamAttempt;
use VistiqCore\Services\Assessment\Attempts\AttemptModerationService;
use VistiqCore\Services\Assessment\Attempts\AttemptQueryService;

class AttemptController extends AdminPageController
{
    public function index(AttemptFilterRequest $request, AttemptQueryService $query): mixed
    {
        $filters = $request->validated();

        return $this->page('Lượt làm bài', 'pages.admin.assessment.attempts.workspace.index', [
            'attempts' => $query->adminListing($filters),
            'summary' => $query->adminSummary($filters),
            'filters' => $filters,
        ]);
    }

    public function detail(ExamAttempt $attempt, AttemptQueryService $query): mixed
    {
        Gate::authorize('results.view');

        return $this->page('Lượt thi #'.$attempt->id, 'pages.admin.assessment.attempts.workspace.detail', $query->detail($attempt));
    }

    public function moderate(ModerationRequest $request, ExamAttempt $attempt, AttemptModerationService $service): mixed
    {
        $service->moderate($attempt, $request->validated());

        return back()->with('status', 'Đã lưu quyết định kiểm duyệt.');
    }
}
