<?php

namespace Tests\Admin\Feature\ExamAttempts;

use Database\Admin\Seeders\AdminCatalogSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Admin\TestCase;
use VistiqAdmin\Models\User;
use VistiqCore\Models\AttemptAnswer;
use VistiqCore\Services\Assessment\Attempts\AttemptService;
use VistiqCore\Services\Assessment\Attempts\GradingService;
use VistiqCore\Services\Assessment\Attempts\ScoringService;
use VistiqCore\Services\Assessment\QuestionBank\QuestionRegistry;

class AttemptAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminCatalogSeeder::class);
    }

    private function attempt(bool $manual = false): mixed
    {
        $item = $manual ? $this->item(['type' => 'essay', 'skill' => 'writing']) : $this->item();
        $attempt = app(AttemptService::class)->start($this->publishedExam([$item]), User::factory()->create());
        app(AttemptService::class)->submit($attempt);

        return $attempt->fresh();
    }

    private function grade(float $score = 6): array
    {
        return ['criteria' => array_fill_keys(QuestionRegistry::WRITING_CRITERIA, $score), 'feedback' => 'Reviewed against pinned rubric'];
    }

    public function test_filters_detail_and_permissions(): void
    {
        $first = $this->attempt();
        $second = $this->attempt(true);
        $this->actingAs($this->admin('reviewer'));
        $this->get('/admin/attempts?status=completed&user='.$first->user_id)->assertOk()->assertSee($first->snapshot['title'])->assertDontSee($second->snapshot['title']);
        $this->get('/admin/attempts/'.$first->id)->assertOk()->assertSee('Select the answer.')->assertSee('Dòng thời gian');
        $this->getJson('/admin/attempts?from=2026-10-14&to=2026-10-01')->assertUnprocessable();
        $this->get('/admin/attempts?max_score=100')->assertOk();
        $this->get('/admin/attempts?to=2099-01-01')->assertOk();
        $this->actingAs(User::factory()->create())->get('/admin/attempts')->assertForbidden();
    }

    public function test_grading_paginates_only_manual_questions(): void
    {
        $manual = $this->attempt(true);
        $objective = $this->attempt();
        for ($i = 0; $i < 25; $i++) {
            AttemptAnswer::create(['exam_attempt_id' => $objective->id, 'item_key' => 'objective-'.$i, 'response' => []]);
        }
        $this->actingAs($this->admin('reviewer'));
        $response = $this->get('/admin/grading')->assertOk()->assertSee($manual->user->name);
        $answers = $response->viewData('answers');
        $this->assertSame(1, $answers->total());
        $this->assertSame($manual->id, $answers->first()->exam_attempt_id);
    }

    public function test_grade_boundaries_regrade_reason_and_conflict(): void
    {
        $attempt = $this->attempt(true);
        $answer = $attempt->answers()->first();
        $this->actingAs($this->admin('reviewer'));
        $url = '/admin/grading/'.$answer->id;
        $this->postJson($url, $this->grade(9.5))->assertUnprocessable();
        $this->postJson($url, $this->grade(6.25))->assertUnprocessable();
        $this->post($url, $this->grade(0))->assertRedirect();
        $oldToken = hash('sha256', json_encode([], JSON_THROW_ON_ERROR));
        $this->postJson($url, $this->grade(9))->assertUnprocessable();
        $this->postJson($url, $this->grade(9) + ['reason' => 'Correct score', 'grade_token' => $oldToken])->assertConflict();
        $this->post($url, $this->grade(9) + ['reason' => 'Correct score'])->assertRedirect();
        $this->assertSame(2, DB::table('audit_logs')->where('action', 'answer.grade')->count());
        $this->assertSame('completed', $attempt->fresh()->status);
    }

    public function test_moderation_retries_do_not_duplicate_audit_and_snapshot_stays_fixed(): void
    {
        $attempt = $this->attempt();
        $snapshot = $attempt->snapshot;
        $this->actingAs($this->admin());
        $url = '/admin/attempts/'.$attempt->id.'/moderate';
        $this->postJson($url, ['leaderboard_status' => 'private', 'note' => '   '])->assertUnprocessable();
        $this->postJson($url, ['leaderboard_status' => 'active', 'note' => 'Invalid'])->assertUnprocessable();
        $data = ['leaderboard_status' => 'private', 'note' => '  Privacy request  '];
        $this->post($url, $data)->assertRedirect();
        $this->post($url, $data)->assertRedirect();
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'attempt.moderate')->count());
        $this->assertSame($snapshot, $attempt->fresh()->snapshot);
        $this->assertSame('completed', $attempt->fresh()->status);
    }

    public function test_scoring_failure_rolls_back_grade_and_audit(): void
    {
        $attempt = $this->attempt(true);
        $answer = $attempt->answers()->first();
        $this->actingAs($this->admin('reviewer'));
        $this->mock(ScoringService::class)->shouldReceive('calculate')->andThrow(new \RuntimeException('scoring unavailable'));
        try {
            app(GradingService::class)->grade($answer, $this->grade());
            $this->fail('Must roll back.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('scoring unavailable', $exception->getMessage());
        }
        $this->assertNull($answer->fresh()->grade);
        $this->assertSame(0, DB::table('audit_logs')->where('action', 'answer.grade')->count());
        $this->assertSame('pending_grading', $attempt->fresh()->status);
    }
}
