<?php

namespace Tests\Admin\Feature\QuestionBank;

use Database\Admin\Factories\QuestionBankItemFactory;
use Database\Admin\Seeders\AdminCatalogSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Admin\TestCase;
use VistiqCore\Services\Assessment\ContentApproval\ContentRevisionService;

class DraftReviewHandoffTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_legacy_draft_and_current_revision_can_be_sent_to_review_without_publication(): void
    {
        $this->seed(AdminCatalogSeeder::class);
        $this->actingAs($this->admin('content-admin'));
        $legacy = QuestionBankItemFactory::new()->create();
        $legacy->learningItem->update(['status' => 'draft']);
        foreach (['A' => 'One', 'B' => 'Two', 'C' => 'Three', 'D' => 'Four'] as $key => $content) {
            $legacy->options()->create(['option_key' => $key, 'content' => $content, 'is_correct' => $key === 'B', 'sort_order' => ord($key) - 65]);
        }
        $current = QuestionBankItemFactory::new()->create();
        app(ContentRevisionService::class)->save('question', $current, $this->item(['exam_part_id' => $current->exam_part_id]), 0);
        $this->post('/admin/questions/bulk', ['ids' => [$legacy->id, $current->id], 'action' => 'review'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('review', $legacy->fresh()->draftRevision->status);
        $this->assertSame('review', $current->fresh()->draftRevision->status);
        $this->assertNull($legacy->fresh()->published_revision_id);
        $this->get('/admin/readings?status=review')->assertOk()->assertSee('Choose the correct answer.');
        $this->get('/admin/questions')->assertRedirect('/admin/questions/listening');
        $this->actingAs($this->admin('reviewer'))->get('/admin/content-approval')->assertOk()->assertSee('Select the answer.');
        $this->post('/admin/questions/bulk', ['ids' => [$legacy->id], 'action' => 'review'])->assertForbidden();
    }
}
