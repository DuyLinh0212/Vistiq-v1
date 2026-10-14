<?php

namespace Tests\Admin\Feature\ContentApproval;

use Database\Admin\Factories\QuestionBankItemFactory;
use Database\Admin\Seeders\AdminCatalogSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Admin\TestCase;
use VistiqCore\Services\Assessment\ContentApproval\ContentReviewService;
use VistiqCore\Services\Assessment\ContentApproval\ContentRevisionService;
use VistiqCore\Services\Assessment\Exams\ExamPublishingService;
use VistiqCore\Services\System\AuditService;

class ReviewQueueTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminCatalogSeeder::class);
    }

    private function question(): mixed
    {
        $this->actingAs($this->admin('content-admin'));
        $question = QuestionBankItemFactory::new()->create();
        $revision = app(ContentRevisionService::class)->save('question', $question, $this->item(['exam_part_id' => $question->exam_part_id]), 0);
        $revision->update(['status' => 'review']);

        return $question->fresh();
    }

    private function decision(mixed $question): array
    {
        $review = app(ContentReviewService::class)->preview('question', $question->id);

        return ['revision_counter' => $review['number'], 'review_token' => $review['token']];
    }

    public function test_queue_combines_types_filters_and_preview_is_read_only(): void
    {
        $question = $this->question();
        $exam = $this->publishedExam();
        $exam->update(['draft_status' => 'review', 'revision_counter' => 2]);
        $this->actingAs($this->admin('reviewer'));
        $this->get('/admin/content-approval')->assertOk()->assertSee($exam->title)->assertSee('Select the answer.');
        $this->get('/admin/content-approval?kind=question&revision=1')->assertOk()->assertDontSee($exam->title);
        $this->get('/admin/content-approval?to=2099-01-01')->assertOk();
        $count = DB::table('audit_logs')->count();
        $this->get('/admin/content-approval/question/'.$question->id)->assertOk()->assertSee('Revision 1')->assertSee('Secret explanation.');
        $this->assertSame($count, DB::table('audit_logs')->count());
        $this->assertDatabaseCount('exam_attempts', 0);
    }

    public function test_approve_requires_token_approves_once_and_does_not_publish(): void
    {
        $question = $this->question();
        $this->actingAs($this->admin('reviewer'));
        $url = '/admin/content-approval/question/'.$question->id.'/approve';
        $this->postJson($url)->assertUnprocessable();
        $data = $this->decision($question);
        $this->postJson($url, $data)->assertOk()->assertJsonPath('status', 'approved');
        $this->postJson($url, $data)->assertConflict();
        $this->assertNull($question->fresh()->published_revision_id);
        $audit = DB::table('audit_logs')->where('action', 'content.approve')->first();
        $this->assertSame(1, json_decode($audit->after, true)['revision']);
        $this->assertDatabaseCount('exam_attempts', 0);
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'content.approve')->count());
    }

    public function test_author_including_super_admin_cannot_approve_on_new_or_legacy_endpoint(): void
    {
        $this->actingAs($author = $this->admin());
        $question = QuestionBankItemFactory::new()->create();
        $revision = app(ContentRevisionService::class)->save('question', $question, $this->item(['exam_part_id' => $question->exam_part_id]), 0);
        $revision->update(['status' => 'review']);
        $question->refresh();
        $this->postJson('/admin/content-approval/question/'.$question->id.'/approve', $this->decision($question))->assertForbidden();
        $this->postJson('/admin/content/question/'.$question->id.'/approve')->assertForbidden();
        $this->assertSame('review', $revision->fresh()->status);
    }

    public function test_reject_trims_reason_and_revision_or_payload_change_conflicts(): void
    {
        $question = $this->question();
        $this->actingAs($this->admin('reviewer'));
        $url = '/admin/content-approval/question/'.$question->id.'/reject';
        $data = $this->decision($question);
        $this->postJson($url, $data + ['note' => '   '])->assertUnprocessable();
        $this->postJson($url, $data + ['note' => str_repeat('a', 2001)])->assertUnprocessable();
        $revision = $question->draftRevision;
        $revision->update(['payload' => array_replace($revision->payload, ['prompt' => 'Changed after preview'])]);
        $this->postJson($url, $data + ['note' => 'Fix wording'])->assertConflict();
        $this->post($url, $data + ['note' => 'Fix wording'])->assertConflict()->assertSee('Changed after preview')->assertSee('Quyết định vừa gửi chưa được áp dụng.');
        $this->postJson($url, $this->decision($question) + ['note' => '  Fix wording  '])->assertOk()->assertJsonPath('status', 'draft');
        $this->assertSame('Fix wording', $revision->fresh()->review_note);
    }

    public function test_invalid_content_and_missing_permission_are_rejected(): void
    {
        $question = $this->question();
        $revision = $question->draftRevision;
        $revision->update(['payload' => array_replace($revision->payload, ['correct_options' => []])]);
        $this->actingAs($this->admin('reviewer'));
        $this->postJson('/admin/content-approval/question/'.$question->id.'/approve', $this->decision($question))->assertUnprocessable();
        $this->actingAs($this->admin('content-admin'))->get('/admin/content-approval')->assertForbidden();
        $this->postJson('/admin/content-approval/question/'.$question->id.'/approve', $this->decision($question))->assertForbidden();
    }

    public function test_audit_failure_rolls_back_decision(): void
    {
        $question = $this->question();
        $this->actingAs($this->admin('reviewer'));
        $data = $this->decision($question);
        $this->mock(AuditService::class)->shouldReceive('record')->once()->andThrow(new \RuntimeException('audit unavailable'));
        try {
            app(ContentReviewService::class)->decide('question', $question->id, 'approve', $data);
            $this->fail('Audit failure must abort the decision.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('audit unavailable', $exception->getMessage());
        }
        $this->assertSame('review', $question->draftRevision->fresh()->status);
        $this->assertSame(0, DB::table('audit_logs')->where('action', 'content.approve')->count());
    }

    public function test_exam_author_is_blocked_and_legacy_preview_does_not_pin_content(): void
    {
        $this->actingAs($author = $this->admin());
        $exam = $this->publishedExam();
        $exam->update(['created_by' => $author->id, 'draft_status' => 'review', 'revision_counter' => 1]);
        $review = app(ContentReviewService::class)->preview('exam', $exam->id);
        $this->postJson('/admin/content-approval/exam/'.$exam->id.'/approve', ['revision_counter' => 1, 'review_token' => $review['token']])->assertForbidden();
        $this->postJson('/admin/exams/'.$exam->id.'/approve')->assertForbidden();
        $this->assertDatabaseCount('content_revisions', 0);
        $this->assertSame('review', $exam->fresh()->draft_status);
    }

    public function test_valid_exam_uses_published_questions_and_stale_configuration_conflicts(): void
    {
        $question = $this->question();
        $this->actingAs($this->admin());
        $revisions = app(ContentRevisionService::class);
        $revisions->transition('question', $question, 'approve');
        $revisions->transition('question', $question, 'publish');
        $exam = $this->publishedExam();
        $exam->update(['draft_status' => 'review', 'revision_counter' => 3, 'draft_payload' => ['sections' => [['exam_part_id' => $question->exam_part_id, 'question_ids' => [$question->id], 'duration_minutes' => 0]], 'policies' => ExamPublishingService::POLICIES]]);
        $this->actingAs($this->admin('reviewer'));
        $service = app(ContentReviewService::class);
        $review = $service->preview('exam', $exam->id);
        $this->assertSame([], $review['blockers']);
        $data = ['revision_counter' => 3, 'review_token' => $review['token']];
        $draft = $exam->draft_payload;
        $draft['policies']['duration_minutes'] = 25;
        $exam->update(['draft_payload' => $draft]);
        $url = '/admin/content-approval/exam/'.$exam->id.'/approve';
        $this->postJson($url, $data)->assertConflict();
        $review = $service->preview('exam', $exam->id);
        $oldPublishedRevision = $exam->published_revision_id;
        $this->postJson($url, ['revision_counter' => 3, 'review_token' => $review['token']])->assertOk()->assertJsonPath('status', 'approved');
        $this->assertSame($oldPublishedRevision, $exam->fresh()->published_revision_id);
        $this->assertSame(1, DB::table('audit_logs')->where('entity_id', $exam->id)->where('entity_type', 'exam')->where('action', 'exam.approve')->count());
    }

    public function test_revision_writer_of_existing_exam_cannot_self_approve(): void
    {
        $exam = $this->publishedExam();
        $exam->update(['draft_status' => 'review', 'revision_counter' => 4]);
        $writer = $this->admin();
        $this->actingAs($writer);
        app(AuditService::class)->record('exam.save', 'exam', $exam->id, [], ['revision' => 4]);
        $this->assertSame($writer->id, app(ContentReviewService::class)->preview('exam', $exam->id)['author']);
        $this->postJson('/admin/exams/'.$exam->id.'/approve')->assertForbidden();
    }

    public function test_exam_queue_author_filter_matches_the_current_revision_writer(): void
    {
        $creator = $this->admin('content-admin');
        $writer = $this->admin('content-admin');
        $exam = $this->publishedExam();
        $exam->update(['created_by' => $creator->id, 'draft_status' => 'review', 'revision_counter' => 4]);
        $this->actingAs($writer);
        app(AuditService::class)->record('exam.save', 'exam', $exam->id, [], ['revision' => 4]);
        $this->actingAs($creator);
        app(AuditService::class)->record('exam.save', 'exam', $exam->id, [], ['revision' => 3]);
        $this->actingAs($this->admin('reviewer'));
        $this->get('/admin/content-approval?kind=exam&author='.$writer->id)->assertOk()->assertSee($exam->title)->assertSee($writer->name);
        $this->get('/admin/content-approval?kind=exam&author='.$creator->id)->assertOk()->assertDontSee($exam->title);
    }

    public function test_archived_questions_are_excluded_and_direct_decision_is_blocked(): void
    {
        $question = $this->question();
        $this->actingAs($this->admin('reviewer'));
        $data = $this->decision($question);
        $question->learningItem->update(['status' => 'archived']);
        $this->get('/admin/content-approval')->assertOk()->assertDontSee('Select the answer.');
        $this->postJson('/admin/content-approval/question/'.$question->id.'/approve', $data)->assertConflict();
        $this->postJson('/admin/content/question/'.$question->id.'/approve')->assertConflict();
    }
}
