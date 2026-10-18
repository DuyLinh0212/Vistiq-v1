<?php

namespace VistiqCore\Services\Assessment\Attempts;

use Illuminate\Support\Carbon;
use VistiqCore\Services\Shared\CompactDatabase as DB;
use VistiqCore\Models\ExamAttempt;

class AttemptQueryService
{
    /** Owner-only summary; objective accuracy avoids averaging incompatible TOEIC/IELTS scales. */
    public function profileSummary(int $owner): array
    {
        $attempts = ExamAttempt::where('user_id', $owner)->whereNotNull('snapshot')->whereNotNull('submitted_at')->where('status', '!=', 'cancelled');
        $results = (clone $attempts)->join(DB::source('attempt_results as result'), 'result.exam_attempt_id', '=', 'exam_attempts.id');
        $totals = (clone $results)->where('result.grading_status', 'completed')->selectRaw("SUM(COALESCE(JSON_EXTRACT(result.details, '$.correct'),0) + 0) as correct_count, SUM((COALESCE(JSON_EXTRACT(result.details, '$.correct'),0) + 0) + (COALESCE(JSON_EXTRACT(result.details, '$.wrong'),0) + 0) + (COALESCE(JSON_EXTRACT(result.details, '$.unanswered'),0) + 0)) as unit_count")->first();

        return ['submitted' => $attempts->count(), 'accuracy' => ($totals->unit_count ?? 0) > 0 ? round($totals->correct_count / $totals->unit_count * 100, 1) : null,
            'recent' => $results->select('exam_attempts.*', 'result.scaled_score', 'result.grading_status')->orderByDesc('exam_attempts.submitted_at')->orderByDesc('exam_attempts.id')->limit(5)->get()];
    }

    public function listing(array $filters = [], ?int $owner = null, ?string $timezone = null): mixed
    {
        $query = ExamAttempt::with(['user', 'exam'])->select('exam_attempts.*')->leftJoin(DB::source('attempt_results as result'), 'result.exam_attempt_id', '=', 'exam_attempts.id')->addSelect('result.scaled_score', 'result.grading_status');
        if ($owner !== null) {
            $query->where('exam_attempts.user_id', $owner);
        }
        foreach (['user' => 'user_id', 'exam' => 'exam_template_id', 'status' => 'status'] as $filter => $column) {
            if (isset($filters[$filter])) {
                $query->where('exam_attempts.'.$column, $filters[$filter]);
            }
        }
        if (isset($filters['from'])) {
            $query->where('exam_attempts.started_at', '>=', Carbon::parse($filters['from'], $timezone ?? config('app.timezone'))->startOfDay()->setTimezone(config('app.timezone'))->toDateTimeString());
        }
        if (isset($filters['to'])) {
            $query->where('exam_attempts.started_at', '<', Carbon::parse($filters['to'], $timezone ?? config('app.timezone'))->addDay()->startOfDay()->setTimezone(config('app.timezone'))->toDateTimeString());
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
        $events = DB::table('attempt_events')->join(DB::source('attempt_event_types'), 'attempt_event_types.id', '=', 'attempt_events.attempt_event_type_id')->where('exam_attempt_id', $attempt->id)->orderBy('occurred_at')->orderBy('attempt_events.id')->get();
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
