<?php

namespace VistiqCore\Services\Analytics;

use Illuminate\Support\Facades\DB;
use VistiqCore\Models\ExamTemplate;

class LeaderboardService
{
    public function rankings(?ExamTemplate $exam = null): mixed
    {
        $seconds = "(COALESCE(JSON_EXTRACT(r.details, '$.duration_seconds'), 0) + 0)";
        $eligible = DB::table('exam_attempts as a')->join('attempt_results as r', 'r.exam_attempt_id', '=', 'a.id')->join('users as u', 'u.id', '=', 'a.user_id')->join('exam_templates as e', 'e.id', '=', 'a.exam_template_id')
            ->whereNotNull('a.snapshot')->whereNotNull('a.submitted_at')->whereIn('a.status', ['submitted', 'completed'])
            ->where('a.snapshot->policies->leaderboard', true)
            ->where('a.leaderboard_status', 'approved')->where('r.grading_status', 'completed')->whereNotNull('r.scaled_score')->where('u.status', 'active')
            ->where(fn ($q) => $q->whereNull('e.leaderboard_reset_at')->orWhereColumn('a.submitted_at', '>', 'e.leaderboard_reset_at'))
            ->when($exam, fn ($q) => $q->where('a.exam_template_id', $exam->id))
            ->select('a.id', 'a.exam_template_id', 'a.user_id', 'a.snapshot', 'a.submitted_at', 'u.name', 'e.title', 'r.scaled_score')
            ->selectRaw("$seconds as seconds")
            ->selectRaw("ROW_NUMBER() OVER (PARTITION BY a.exam_template_id, a.user_id ORDER BY r.scaled_score DESC, $seconds ASC, a.submitted_at ASC, a.id ASC) as best_attempt");
        $best = DB::query()->fromSub($eligible, 'eligible')->where('best_attempt', 1);
        $ranked = DB::query()->fromSub($best, 'best')->select('best.*')
            ->selectRaw('RANK() OVER (PARTITION BY exam_template_id ORDER BY scaled_score DESC, seconds ASC) as rank_position');

        return DB::query()->fromSub($ranked, 'ranked')
            ->orderByDesc('scaled_score')->orderBy('seconds')->orderBy('submitted_at')->orderBy('id')->paginate(30)->withQueryString()->through(function ($row) {
                $snapshot = json_decode($row->snapshot, true);
                $row->name = ($snapshot['policies']['anonymous'] ?? true) ? 'Học viên ẩn danh' : $row->name;

                return $row;
            });
    }

    /** Public response excludes internal IDs, account fields and snapshots. */
    public function publicRankings(ExamTemplate $exam, int $viewerId): mixed
    {
        return $this->rankings($exam)->through(fn ($row) => [
            'rank' => (int) $row->rank_position, 'name' => $row->name,
            'score' => (float) $row->scaled_score, 'seconds' => (int) $row->seconds,
            'is_current_user' => (int) $row->user_id === $viewerId,
        ]);
    }
}
