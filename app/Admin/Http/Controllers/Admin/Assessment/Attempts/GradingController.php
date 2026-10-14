<?php

namespace VistiqAdmin\Http\Controllers\Admin\Assessment\Attempts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use VistiqAdmin\Http\Controllers\Admin\AdminPageController;
use VistiqAdmin\Http\Requests\Admin\Assessment\Attempts\ManualGradeRequest;
use VistiqAdmin\Http\Requests\Admin\Shared\AdminActionRequest;
use VistiqCore\Models\AttemptAnswer;
use VistiqCore\Models\ExamAttempt;
use VistiqCore\Services\Assessment\Attempts\GradingService;
use VistiqCore\Services\Assessment\QuestionBank\QuestionRegistry;
use VistiqCore\Services\Shared\AiService;

class GradingController extends AdminPageController
{
    public function index(): mixed
    {
        Gate::authorize('results.grade');
        $mysql = DB::connection()->getDriverName() === 'mysql';
        $items = $mysql
            ? "JSON_TABLE(exam_attempts.snapshot, '$.items[*]' COLUMNS (item_key VARCHAR(80) PATH '$.key', question_type VARCHAR(40) PATH '$.type')) as manual_item"
            : "json_each(exam_attempts.snapshot, '$.items') as manual_item";
        $predicate = $mysql
            ? 'manual_item.item_key = attempt_answers.item_key AND manual_item.question_type IN (?, ?)'
            : "json_extract(manual_item.value, '$.key') = attempt_answers.item_key AND json_extract(manual_item.value, '$.type') IN (?, ?)";
        $answers = AttemptAnswer::whereNotNull('item_key')->whereHas('attempt', fn ($query) => $query->whereNotNull('submitted_at')->whereExists(fn ($q) => $q->selectRaw('1')->fromRaw($items)->whereRaw($predicate, ['essay', 'speaking'])))->with('attempt.user')->latest()->paginate(20);
        $suggestions = DB::table('ai_tasks')->whereNotNull('attempt_answer_id')->latest()->limit(100)->get()->groupBy('attempt_answer_id');

        $gradingCriteria = ['essay' => QuestionRegistry::WRITING_CRITERIA, 'speaking' => QuestionRegistry::SPEAKING_CRITERIA];

        return $this->page('Chấm Writing / Speaking', 'pages.admin.assessment.attempts.workspace.grading', compact('answers', 'suggestions', 'gradingCriteria'));
    }

    public function store(ManualGradeRequest $request, AttemptAnswer $answer, GradingService $grading): mixed
    {
        $grading->grade($answer, $request->validated());

        return back()->with('status', 'Đã công bố điểm và cập nhật kết quả.');
    }

    public function ai(AdminActionRequest $request, AttemptAnswer $answer, AiService $ai): mixed
    {
        Gate::authorize('results.grade');
        $attempt = ExamAttempt::findOrFail($answer->exam_attempt_id);
        abort_unless($attempt->submitted_at, 409);
        $item = collect($attempt->snapshot['items'])->firstWhere('key', $answer->item_key);
        abort_unless($item && in_array($item['type'], ['essay', 'speaking'], true), 422);
        $id = $ai->enqueue($item['type'] === 'essay' ? 'writing_evaluation' : 'speaking_evaluation', ['item' => $item, 'response' => $answer->response], $request->user()->id, $answer->id);

        return back()->with('status', 'Đã đưa gợi ý AI #'.$id.' vào hàng đợi. Reviewer công bố điểm sau khi kiểm tra.');
    }
}
