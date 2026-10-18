<?php

namespace VistiqLearner\Http\Controllers\Assessment\ExamTakingAndSubmission;

use Illuminate\Http\Request;
use VistiqCore\Models\ExamTemplate;
use VistiqCore\Services\Analytics\LeaderboardService;
use VistiqLearner\Http\Controllers\Controller;

class ExamLeaderboardController extends Controller
{
    public function index(Request $request, ExamTemplate $exam, LeaderboardService $leaderboard): mixed
    {
        abort_unless($exam->status === 'published' && ($exam->publishedRevision?->payload['policies']['leaderboard'] ?? false), 404);
        $rankings = $leaderboard->publicRankings($exam, $request->user()->id);

        return $request->expectsJson() ? response()->json($rankings)
            : view('pages.learner.assessment.exam-taking-and-submission.runtime.leaderboard', compact('exam', 'rankings'));
    }
}
