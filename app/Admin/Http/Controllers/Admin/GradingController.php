<?php

namespace VistiqAdmin\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use VistiqAdmin\Http\Requests\Admin\AdminActionRequest;
use VistiqAdmin\Http\Requests\Admin\ManualGradeRequest;
use VistiqCore\Models\AttemptAnswer;
use VistiqCore\Models\ExamAttempt;
use VistiqCore\Services\AiService;
use VistiqCore\Services\GradingService;
use VistiqCore\Services\QuestionRegistry;

class GradingController extends AdminPageController
{
    public function index(): mixed
    {
        $answers = AttemptAnswer::whereHas('attempt', fn ($query) => $query->whereNotNull('submitted_at'))->whereNotNull('item_key')->where(fn ($query) => $query->whereNotNull('grade')->orWhereNull('awarded_points'))->with('attempt.user')->latest()->paginate(20);
        $answers->setCollection($answers->getCollection()->filter(fn ($answer) => in_array(collect($answer->attempt->snapshot['items'])->firstWhere('key', $answer->item_key)['type'] ?? '', ['essay', 'speaking'], true)));
        $suggestions = DB::table('ai_tasks')->whereNotNull('attempt_answer_id')->latest()->limit(100)->get()->groupBy('attempt_answer_id');

        $gradingCriteria = ['essay' => QuestionRegistry::WRITING_CRITERIA, 'speaking' => QuestionRegistry::SPEAKING_CRITERIA];

        return $this->page('Chấm Writing / Speaking', 'pages.admin.workspace.grading', compact('answers', 'suggestions', 'gradingCriteria'));
    }

    public function store(ManualGradeRequest $request, AttemptAnswer $answer, GradingService $grading): mixed
    {
        $grading->grade($answer, $request->validated());

        return back()->with('status', 'Đã công bố điểm và cập nhật kết quả.');
    }

    public function ai(AdminActionRequest $request, AttemptAnswer $answer, AiService $ai): mixed
    {
        $attempt = ExamAttempt::findOrFail($answer->exam_attempt_id);
        abort_unless($attempt->submitted_at, 409);
        $item = collect($attempt->snapshot['items'])->firstWhere('key', $answer->item_key);
        abort_unless($item && in_array($item['type'], ['essay', 'speaking'], true), 422);
        $id = $ai->enqueue($item['type'] === 'essay' ? 'writing_evaluation' : 'speaking_evaluation', ['item' => $item, 'response' => $answer->response], $request->user()->id, $answer->id);

        return back()->with('status', 'Đã đưa gợi ý AI #'.$id.' vào hàng đợi. Reviewer công bố điểm sau khi kiểm tra.');
    }
}
