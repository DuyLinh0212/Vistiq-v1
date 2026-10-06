<?php

namespace VistiqCore\Services;

use Illuminate\Support\Facades\DB;
use VistiqCore\Models\ExamTemplate;

class LeaderboardService
{
    public function rankings(?ExamTemplate $exam = null): mixed
    {
        return DB::table('exam_attempts as a')->join('attempt_results as r', 'r.exam_attempt_id', '=', 'a.id')->join('users as u', 'u.id', '=', 'a.user_id')->join('exam_templates as e', 'e.id', '=', 'a.exam_template_id')
            ->whereNotNull('a.snapshot')->whereNotNull('a.submitted_at')->where('a.leaderboard_status', 'approved')->where('r.grading_status', 'completed')->whereNotNull('r.scaled_score')->where('u.status', 'active')
            ->where(function ($query): void {
                $query->whereNull('e.leaderboard_reset_at')->orWhereColumn('a.submitted_at', '>', 'e.leaderboard_reset_at');
            })
            ->when($exam, fn ($query) => $query->where('a.exam_template_id', $exam->id))
            ->select('a.id', 'a.user_id', 'a.snapshot', 'a.submitted_at', 'u.name', 'e.title', 'r.scaled_score', 'r.details')
            ->orderByDesc('r.scaled_score')->orderByRaw('(COALESCE(JSON_EXTRACT(r.details, ?), 0) + 0) ASC', ['$.duration_seconds'])->orderBy('a.submitted_at')->orderBy('a.id')->paginate(30)->through(function ($row) {
                $snapshot = json_decode($row->snapshot, true);
                $row->name = ($snapshot['policies']['anonymous'] ?? true) ? 'Học viên #'.$row->user_id : $row->name;
                $row->seconds = json_decode($row->details ?? '{}', true)['duration_seconds'] ?? 0;

                return $row;
            });
    }
}
