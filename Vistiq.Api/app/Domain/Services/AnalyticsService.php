<?php

namespace VistiqCore\Services;

use Illuminate\Support\Facades\DB;
use VistiqCore\Models\ExamAttempt;
use VistiqCore\Models\ExamTemplate;
use VistiqCore\Models\QuestionBankItem;
use VistiqCore\Models\User;

class AnalyticsService
{
    public function dashboard(): array
    {
        $active = fn (int $days): int => DB::table('user_activities')->where('occurred_at', '>=', now()->subDays($days))->distinct()->count('user_id');
        $attempts = ExamAttempt::whereNotNull('snapshot')->count();
        $completed = ExamAttempt::whereNotNull('snapshot')->whereNotNull('submitted_at')->count();
        $chart = [];
        for ($days = 13; $days >= 0; $days--) {
            $date = now()->subDays($days)->toDateString();
            $chart[] = ['date' => $date, 'attempts' => ExamAttempt::whereNotNull('snapshot')->whereDate('started_at', $date)->count(), 'active' => DB::table('user_activities')->whereDate('occurred_at', $date)->distinct()->count('user_id')];
        }
        $cohort = DB::table('user_activities')->whereBetween('occurred_at', [now()->subDays(14), now()->subDays(7)])->distinct()->pluck('user_id');
        $retained = $cohort->isEmpty() ? 0 : DB::table('user_activities')->whereIn('user_id', $cohort)->where('occurred_at', '>=', now()->subDays(7))->distinct()->count('user_id');
        $difficulty = DB::table('attempt_answers')->whereNotNull('is_correct')->select('item_key')->selectRaw('COUNT(*) AS responses, AVG(CASE WHEN is_correct = 1 THEN 1.0 ELSE 0 END) AS accuracy, AVG(spent_seconds) AS seconds')->groupBy('item_key')->orderBy('accuracy')->limit(20)->get();

        return ['users' => User::count(), 'questions' => QuestionBankItem::count(), 'exams' => ExamTemplate::where('status', 'published')->count(), 'attempts' => $attempts, 'completed' => $completed, 'dau' => $active(1), 'wau' => $active(7), 'mau' => $active(30), 'retention' => $cohort->count() ? round($retained / $cohort->count() * 100, 1) : 0, 'drop_off' => $attempts ? round(($attempts - $completed) / $attempts * 100, 1) : 0, 'chart' => $chart, 'difficulty' => $difficulty];
    }
}
