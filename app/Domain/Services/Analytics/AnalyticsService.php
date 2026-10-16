<?php

namespace VistiqCore\Services\Analytics;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use VistiqCore\Models\ExamAttempt;
use VistiqCore\Models\ExamTemplate;
use VistiqCore\Models\QuestionBankItem;
use VistiqCore\Models\User;

class AnalyticsService
{
    /**
     * Build dashboard analytics metrics with filtering support.
     *
     * @return array<string, mixed>
     */
    public function dashboard(?Request $request = null): array
    {
        $dateFrom = $request?->string('date_from')->toString() ?: null;
        $dateTo = $request?->string('date_to')->toString() ?: null;
        $examId = $request?->filled('exam_id') ? (int) $request->input('exam_id') : null;
        $mode = $request?->string('mode')->toString() ?: 'all';

        // Active user counters
        $active = fn (int $days): int => DB::table('user_activities')
            ->where('occurred_at', '>=', now()->subDays($days))
            ->distinct()
            ->count('user_id');

        // Base attempts query with filters and test/cancelled exclusions
        $attemptsQuery = ExamAttempt::query()
            ->whereNotNull('snapshot')
            ->where(function ($q): void {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            })
            ->when($examId, fn ($q) => $q->where('exam_template_id', $examId))
            ->when($dateFrom, fn ($q) => $q->whereDate('started_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('started_at', '<=', $dateTo));

        if ($mode === 'practice') {
            $attemptsQuery->where(function ($q): void {
                $q->whereRaw("JSON_EXTRACT(snapshot, '$.mode') = 'practice'")
                    ->orWhereRaw("JSON_EXTRACT(snapshot, '$.variant') = 'practice'");
            });
        } elseif ($mode === 'exam') {
            $attemptsQuery->where(function ($q): void {
                $q->where(function ($sub): void {
                    $sub->whereNull('snapshot->mode')
                        ->orWhereRaw("JSON_EXTRACT(snapshot, '$.mode') != 'practice'");
                })->where(function ($sub): void {
                    $sub->whereNull('snapshot->variant')
                        ->orWhereRaw("JSON_EXTRACT(snapshot, '$.variant') != 'practice'");
                });
            });
        }

        $attempts = (clone $attemptsQuery)->count();
        $completed = (clone $attemptsQuery)->whereNotNull('submitted_at')->count();
        $completionRate = $attempts > 0 ? round(($completed / $attempts) * 100, 1) : 0;
        $dropOff = $attempts > 0 ? round((($attempts - $completed) / $attempts) * 100, 1) : 0;

        // Average score from completed and graded attempts
        $avgScore = DB::table('exam_attempts as a')
            ->join('attempt_results as r', 'r.exam_attempt_id', '=', 'a.id')
            ->whereNotNull('a.snapshot')
            ->whereNotNull('a.submitted_at')
            ->where('r.grading_status', 'completed')
            ->whereNotNull('r.scaled_score')
            ->where(function ($q): void {
                $q->whereNull('a.status')->orWhere('a.status', '!=', 'cancelled');
            })
            ->when($examId, fn ($q) => $q->where('a.exam_template_id', $examId))
            ->when($dateFrom, fn ($q) => $q->whereDate('a.started_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('a.started_at', '<=', $dateTo))
            ->when($mode === 'practice', fn ($q) => $q->whereRaw("JSON_EXTRACT(a.snapshot, '$.mode') = 'practice'"))
            ->when($mode === 'exam', fn ($q) => $q->where(fn ($sub) => $sub->whereNull('a.snapshot->mode')->orWhereRaw("JSON_EXTRACT(a.snapshot, '$.mode') != 'practice'")))
            ->avg('r.scaled_score');

        // 14-day activity trend chart
        $daysCount = 14;
        $chart = [];
        $endDate = $dateTo ? Carbon::parse($dateTo) : now();
        for ($days = $daysCount - 1; $days >= 0; $days--) {
            $targetDate = (clone $endDate)->subDays($days)->toDateString();
            $dayAttempts = (clone $attemptsQuery)->whereDate('started_at', $targetDate)->count();
            $dayActive = DB::table('user_activities')->whereDate('occurred_at', $targetDate)->distinct()->count('user_id');
            $chart[] = [
                'date' => $targetDate,
                'attempts' => $dayAttempts,
                'active' => $dayActive,
            ];
        }

        // Cohort retention
        $cohort = DB::table('user_activities')
            ->whereBetween('occurred_at', [now()->subDays(14), now()->subDays(7)])
            ->distinct()
            ->pluck('user_id');

        $retained = $cohort->isEmpty()
            ? 0
            : DB::table('user_activities')
                ->whereIn('user_id', $cohort)
                ->where('occurred_at', '>=', now()->subDays(7))
                ->distinct()
                ->count('user_id');

        $retention = $cohort->count() ? round(($retained / $cohort->count()) * 100, 1) : 0;

        // Question difficulty breakdown
        $difficulty = DB::table('attempt_answers as ans')
            ->join('exam_attempts as a', 'a.id', '=', 'ans.exam_attempt_id')
            ->whereNotNull('ans.is_correct')
            ->when($examId, fn ($q) => $q->where('a.exam_template_id', $examId))
            ->when($dateFrom, fn ($q) => $q->whereDate('a.started_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('a.started_at', '<=', $dateTo))
            ->select('ans.item_key')
            ->selectRaw('COUNT(*) AS responses, AVG(CASE WHEN ans.is_correct = 1 THEN 1.0 ELSE 0 END) AS accuracy, AVG(ans.spent_seconds) AS seconds')
            ->groupBy('ans.item_key')
            ->orderBy('accuracy')
            ->limit(20)
            ->get();

        $exams = ExamTemplate::where('status', 'published')->get(['id', 'title']);

        return [
            'users' => User::count(),
            'questions' => QuestionBankItem::count(),
            'exams' => $exams,
            'attempts' => $attempts,
            'completed' => $completed,
            'completion_rate' => $completionRate,
            'avg_score' => $avgScore !== null ? round((float) $avgScore, 1) : null,
            'drop_off' => $dropOff,
            'dau' => $active(1),
            'wau' => $active(7),
            'mau' => $active(30),
            'retention' => $retention,
            'chart' => $chart,
            'difficulty' => $difficulty,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'exam_id' => $examId,
                'mode' => $mode,
            ],
            'updated_at' => now()->format('d/m/Y H:i'),
            'timezone' => config('app.timezone', 'UTC'),
        ];
    }
}
