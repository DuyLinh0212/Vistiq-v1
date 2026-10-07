<?php

namespace Tests\Admin\Feature;

use Database\Admin\Seeders\AdminCatalogSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\Admin\TestCase;
use VistiqAdmin\Models\User;
use VistiqCore\Models\QuestionBankItem;
use VistiqCore\Models\Role;

class AdminQuestionBankTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AdminCatalogSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('code', 'content-admin')->value('id'));
        $this->actingAs($admin);
    }

    public function test_question_bank_page_renders_catalog_filters(): void
    {
        $this->get('/admin/questions')->assertOk()
            ->assertSee('Ngân hàng câu hỏi')
            ->assertSee('TOEIC')
            ->assertSee('IELTS')
            ->assertSee('Import câu hỏi CSV')
            ->assertSee('Tạo câu hỏi');

        $this->get('/admin/questions/create')->assertOk()->assertSee('name="prompt"', false);
    }

    public function test_admin_can_create_and_update_a_multiple_choice_question(): void
    {
        $partId = DB::table('exam_parts')->where('code', 'part-5')->value('id');
        $typeId = DB::table('question_types')->where('code', 'multiple_choice')->value('id');
        $difficultyId = DB::table('question_difficulties')->where('code', 'medium')->value('id');

        $response = $this->post('/admin/questions', [
            'exam_part_id' => $partId,
            'question_type_id' => $typeId,
            'question_difficulty_id' => $difficultyId,
            'prompt' => 'The company is considering ______ its operations.',
            'options' => ['A' => 'expand', 'B' => 'to expand', 'C' => 'expanding', 'D' => 'expanded'],
            'correct_option' => 'C',
            'explanation' => 'Consider is followed by a gerund.',
            'tags' => 'Grammar, Verb form',
            'status' => 'draft',
        ]);

        $question = QuestionBankItem::query()->with(['learningItem', 'options'])->firstOrFail();
        $response->assertRedirect(route('admin.questions.edit', $question));
        $this->assertSame('draft', $question->learningItem->status);
        $this->assertSame('C', $question->options->firstWhere('is_correct', true)->option_key);
        $this->assertDatabaseHas('tags', ['slug' => 'grammar']);
        $this->get(route('admin.questions.edit', $question))->assertOk()->assertSee('The company is considering');

        $this->put(route('admin.questions.update', $question), [
            'exam_part_id' => $partId,
            'question_type_id' => $typeId,
            'question_difficulty_id' => $difficultyId,
            'prompt' => 'The company is considering ______ its operations next year.',
            'options' => ['A' => 'expand', 'B' => 'to expand', 'C' => 'expanding', 'D' => 'expanded'],
            'correct_option' => 'C',
            'status' => 'draft',
            'revision_counter' => 1,
        ])->assertRedirect(route('admin.questions.edit', $question));

        $this->assertDatabaseHas('question_bank_items', ['id' => $question->id, 'prompt' => 'The company is considering ______ its operations next year.']);
        $this->assertDatabaseHas('learning_items', ['id' => $question->learning_item_id, 'status' => 'draft', 'published_at' => null]);
    }

    public function test_admin_can_create_a_reading_and_link_a_question_to_it(): void
    {
        $readingResponse = $this->post('/admin/readings', [
            'stimulus_type' => 'reading',
            'title' => 'The impact of renewable energy',
            'body' => 'Renewable energy is changing the way communities plan for the future.',
            'status' => 'draft',
        ]);
        $stimulusId = DB::table('learning_stimuli')->value('id');
        $readingResponse->assertRedirect(route('admin.readings.index', ['stimulus' => $stimulusId]));

        $this->get(route('admin.readings.index', ['stimulus' => $stimulusId]))
            ->assertOk()->assertSee('The impact of renewable energy')->assertSee('Chưa có câu hỏi liên kết');
    }

    public function test_admin_can_import_multiple_question_types_from_csv(): void
    {
        $multipleChoiceId = (int) DB::table('question_types')->where('code', 'multiple_choice')->value('id');
        $shortAnswerId = (int) DB::table('question_types')->where('code', 'short_answer')->value('id');
        $mediumId = (int) DB::table('question_difficulties')->where('code', 'medium')->value('id');

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['exam_part', 'question_type', 'difficulty', 'prompt', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option', 'accepted_answers', 'explanation', 'tags', 'stimulus', 'status']);
        fputcsv($handle, ['part-5', 'multiple_choice', 'hard', 'The company is ______ its operations.', 'expand', 'to expand', 'expanding', 'expanded', 'C', '', 'Consider is followed by a gerund.', 'Grammar|Verb form', '', 'published']);
        fputcsv($handle, ['reading-part-1', 'short_answer', 'easy', 'The meeting starts ______ 9 a.m.', '', '', '', '', '', 'at|around', 'Both answers are acceptable.', 'Preposition', '', 'draft']);
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $response = $this->post(route('admin.questions.import'), [
            'file' => UploadedFile::fake()->createWithContent('questions.csv', $csv),
            'default_question_type' => $multipleChoiceId,
            'default_difficulty' => $mediumId,
            'default_status' => 'draft',
            'offset' => 0,
        ]);

        $response->assertRedirect(route('admin.questions.index'))
            ->assertSessionHas('import_summary', ['total' => 2, 'imported' => 2, 'failed' => 0]);

        $multipleChoice = QuestionBankItem::query()->with(['learningItem', 'options'])->where('prompt', 'The company is ______ its operations.')->firstOrFail();
        $shortAnswer = QuestionBankItem::query()->where('prompt', 'The meeting starts ______ 9 a.m.')->firstOrFail();

        $this->assertSame('draft', $multipleChoice->learningItem->status);
        $this->assertSame('C', $multipleChoice->options->firstWhere('is_correct', true)->option_key);
        $this->assertDatabaseHas('question_bank_items', ['id' => $shortAnswer->id, 'question_type_id' => $shortAnswerId]);
        $this->assertDatabaseHas('question_accepted_answers', ['question_bank_item_id' => $shortAnswer->id, 'answer_text' => 'at']);
        $this->assertDatabaseHas('question_accepted_answers', ['question_bank_item_id' => $shortAnswer->id, 'answer_text' => 'around']);
    }

    public function test_csv_import_reports_invalid_rows_without_discarding_valid_rows(): void
    {
        $multipleChoiceId = (int) DB::table('question_types')->where('code', 'multiple_choice')->value('id');
        $mediumId = (int) DB::table('question_difficulties')->where('code', 'medium')->value('id');

        $csv = implode("\n", [
            'exam_part,question_type,prompt,option_a,option_b,correct_option',
            'part-5,multiple_choice,"A valid question?",Yes,No,A',
            'part-unknown,multiple_choice,"This row has an invalid Part.",Yes,No,A',
        ]);

        $response = $this->post(route('admin.questions.import'), [
            'file' => UploadedFile::fake()->createWithContent('questions.csv', $csv),
            'default_question_type' => $multipleChoiceId,
            'default_difficulty' => $mediumId,
            'default_status' => 'draft',
            'offset' => 0,
        ]);

        $response->assertRedirect(route('admin.questions.index'))
            ->assertSessionHas('import_summary', ['total' => 2, 'imported' => 1, 'failed' => 1])
            ->assertSessionHas('import_errors', fn (array $errors): bool => str_contains($errors[0], 'Dòng 3'));
        $this->assertDatabaseHas('question_bank_items', ['prompt' => 'A valid question?']);
        $this->assertDatabaseMissing('question_bank_items', ['prompt' => 'This row has an invalid Part.']);
    }

    public function test_question_csv_template_can_be_downloaded(): void
    {
        $this->get(route('admin.questions.import-template'))
            ->assertDownload('vistiq-question-bank-template.csv');
    }
}
