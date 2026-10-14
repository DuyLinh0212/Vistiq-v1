<?php

namespace VistiqCore\Services\Assessment\Attempts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use VistiqCore\Models\ExamAttempt;

class AttemptQueryService
{
    public function listing(array $filters = [], ?int $owner = null): mixed
    {
        $query = ExamAttempt::with(['user', 'exam'])->select('exam_attempts.*')->leftJoin('attempt_results as result', 'result.exam_attempt_id', '=', 'exam_attempts.id')->addSelect('result.scaled_score', 'result.grading_status');
        if ($owner !== null) {
            $query->where('exam_attempts.user_id', $owner);
        }
        foreach (['user' => 'user_id', 'exam' => 'exam_template_id', 'status' => 'status'] as $filter => $column) {
            if (isset($filters[$filter])) {
                $query->where('exam_attempts.'.$column, $filters[$filter]);
            }
        }
        if (isset($filters['from'])) {
            $query->where('exam_attempts.started_at', '>=', $filters['from'].' 00:00:00');
        }
        if (isset($filters['to'])) {
            $query->where('exam_attempts.started_at', '<', Carbon::parse($filters['to'])->addDay()->toDateString());
        }
        if (isset($filters['min_score'])) {
            $query->where('result.scaled_score', '>=', $filters['min_score']);
        }
        if (isset($filters['max_score'])) {
            $query->where('result.scaled_score', '<=', $filters['max_score']);
        }
        if (isset($filters['reports'])) {
            $method = $filters['reports'] === 'none' ? 'whereNotExists' : 'whereExists';
            $query->$method(function ($report) use ($filters): void {
                $report->selectRaw('1')->from('attempt_reports')->whereColumn('attempt_reports.exam_attempt_id', 'exam_attempts.id');
                if ($filters['reports'] === 'pending') {
                    $report->where('attempt_reports.status', 'pending');
                }
            });
        }

        return $query->orderByDesc('exam_attempts.started_at')->orderByDesc('exam_attempts.id')->paginate(20)->withQueryString();
    }

    public function detail(ExamAttempt $attempt): array
    {
        $attempt->load(['user', 'exam', 'answers']);
        $events = DB::table('attempt_events')->join('attempt_event_types', 'attempt_event_types.id', '=', 'attempt_events.attempt_event_type_id')->where('exam_attempt_id', $attempt->id)->orderBy('occurred_at')->orderBy('attempt_events.id')->get();
        $reports = DB::table('attempt_reports')->where('exam_attempt_id', $attempt->id)->orderBy('id')->get();
        $result = DB::table('attempt_results')->where('exam_attempt_id', $attempt->id)->first();
        $answerIds = $attempt->answers->pluck('id');
        $history = DB::table('audit_logs')->where(function ($query) use ($attempt, $answerIds): void {
            $query->where(fn ($q) => $q->where('entity_type', 'attempt')->where('entity_id', $attempt->id)->where('action', 'attempt.moderate'))
                ->orWhere(fn ($q) => $q->where('entity_type', 'answer')->whereIn('entity_id', $answerIds)->where('action', 'answer.grade'));
        })->orderByDesc('id')->paginate(30, ['*'], 'audit_page')->withQueryString();

        return compact('attempt', 'events', 'reports', 'result', 'history');
    }
}
