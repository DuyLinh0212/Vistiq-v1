<?php

namespace VistiqCore\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use VistiqCore\Models\AttemptAnswer;
use VistiqCore\Models\ExamAttempt;

class GradingService
{
    public function __construct(private ScoringService $scoring, private AuditService $audit) {}

    public function grade(AttemptAnswer $answer, array $data): void
    {
        Gate::authorize('results.grade');
        DB::transaction(function () use ($answer, $data): void {
            $attempt = ExamAttempt::lockForUpdate()->findOrFail($answer->exam_attempt_id);
            $answer = AttemptAnswer::lockForUpdate()->findOrFail($answer->id);
            abort_unless($attempt->submitted_at, 409, 'Bài chưa nộp.');
            $item = collect($attempt->snapshot['items'])->firstWhere('key', $answer->item_key);
            abort_unless($item && in_array($item['type'], ['essay', 'speaking'], true), 422);
            $criteria = $item['type'] === 'essay' ? QuestionRegistry::WRITING_CRITERIA : QuestionRegistry::SPEAKING_CRITERIA;
            if (array_diff($criteria, array_keys($data['criteria'])) || array_diff(array_keys($data['criteria']), $criteria)) {
                throw ValidationException::withMessages(['criteria' => 'Cần đủ bốn tiêu chí của rubric.']);
            }
            foreach ($data['criteria'] as $score) {
                if ($score < 0 || $score > 9 || fmod((float) $score * 2, 1) !== 0.0) {
                    throw ValidationException::withMessages(['criteria' => 'Điểm từ 0 đến 9, bước 0.5.']);
                }
            }
            $before = $answer->grade ?? [];
            $answer->update(['grade' => ['criteria' => $data['criteria'], 'feedback' => $data['feedback'], 'reviewer_id' => auth()->id(), 'estimated' => true, 'graded_at' => now()->toIso8601String()]]);
            $this->audit->record('answer.grade', 'answer', $answer->id, $before, $answer->grade);
            $this->scoring->calculate($attempt);
        });
    }
}
