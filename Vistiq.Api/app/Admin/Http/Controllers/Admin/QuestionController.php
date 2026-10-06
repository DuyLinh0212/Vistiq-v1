<?php

namespace VistiqAdmin\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use VistiqAdmin\Http\Controllers\StaticPageController;
use VistiqAdmin\Http\Requests\Admin\QuestionImportRequest;
use VistiqAdmin\Http\Requests\Admin\QuestionUpsertRequest;
use VistiqCore\Models\ContentRevision;
use VistiqCore\Models\ExamPart;
use VistiqCore\Models\LearningItem;
use VistiqCore\Models\LearningStimulus;
use VistiqCore\Models\MediaAsset;
use VistiqCore\Models\QuestionAcceptedAnswer;
use VistiqCore\Models\QuestionBankItem;
use VistiqCore\Models\QuestionDifficulty;
use VistiqCore\Models\QuestionType;
use VistiqCore\Services\ContentRevisionService;
use VistiqCore\Services\QuestionRegistry;

class QuestionController extends StaticPageController
{
    /** @var array<string, string> */
    private const CSV_HEADER_ALIASES = [
        'question' => 'prompt',
        'content' => 'prompt',
        'part' => 'exam_part',
        'type' => 'question_type',
        'level' => 'difficulty',
        'answer' => 'correct_option',
        'media' => 'stimulus',
        'audio_group_id' => 'audio_group',
    ];

    /** @var array<string, string> */
    private const CSV_TEMPLATES = [
        'all' => 'vistiq-question-bank-template.csv',
        'toeic' => 'vistiq-toeic-template.csv',
        'ielts' => 'vistiq-ielts-template.csv',
        'listening-groups' => 'vistiq-listening-groups-template.csv',
        'parts' => 'part-skill-guide.csv',
        'types' => 'question-type-guide.csv',
        'difficulty' => 'difficulty-guide.csv',
        'guide' => 'HUONG_DAN_CSV.md',
        'bundle' => 'vistiq-question-bank-templates.zip',
    ];

    /** @var array<string, array{title: string, frame: string, view: string, section: string, description: string}> */
    private const SCREENS = [
        'admin-questions' => ['title' => 'Ngân hàng câu hỏi', 'frame' => 'A02', 'view' => 'pages.admin.questions.index', 'section' => 'admin', 'description' => 'Quản lý toàn bộ câu hỏi TOEIC, IELTS và các dạng bài luyện tập.'],
        'admin-question-edit' => ['title' => 'Tạo / chỉnh sửa câu hỏi', 'frame' => 'A03', 'view' => 'pages.admin.questions.edit', 'section' => 'admin', 'description' => 'Tạo câu hỏi, đáp án, giải thích và liên kết media.'],
    ];

    public function show(string $screen): View
    {
        return match ($screen) {
            'admin-questions' => $this->index(request()),
            'admin-question-edit' => $this->create(),
            default => $this->renderKnownScreen($screen, self::SCREENS),
        };
    }

    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $exam = $request->string('exam')->toString();
        $part = $request->string('part')->toString();
        $type = $request->string('type')->toString();
        $difficulty = $request->string('difficulty')->toString();
        $status = $request->string('status')->toString();
        $missing = $request->boolean('missing');
        $questionId = preg_match('/^Q-(\d+)$/i', $search, $matches) === 1
            ? (int) $matches[1]
            : (ctype_digit($search) ? (int) $search : null);
        $examTypes = DB::table('exam_types')->where('is_active', true)->orderBy('id')->get();
        $parts = ExamPart::with(['examType', 'skill'])
            ->whereIn('exam_type_id', $examTypes->pluck('id'))
            ->orderBy('exam_type_id')->orderBy('sort_order')->get();
        $questionTypes = QuestionType::orderBy('id')->get();
        $registry = app(QuestionRegistry::class);

        if ($exam !== '' && ! $examTypes->contains('code', $exam)) {
            $exam = '';
        }

        $availableParts = $parts->filter(fn (ExamPart $examPart): bool => $exam === '' || $examPart->examType->code === $exam)->values();
        if ($part !== '' && ! $availableParts->contains('code', $part)) {
            $part = '';
        }

        $allowedTypeCodes = $availableParts
            ->filter(fn (ExamPart $examPart): bool => $part === '' || $examPart->code === $part)
            ->flatMap(fn (ExamPart $examPart): array => $registry->allowedTypes($examPart->examType->code, $examPart->skill->code))
            ->unique()->values()->all();
        $filterQuestionTypes = $questionTypes->whereIn('code', $allowedTypeCodes)->values();
        if ($type !== '' && ! $filterQuestionTypes->contains('code', $type)) {
            $type = '';
        }

        $filterCatalog = [
            'parts' => $parts->map(fn (ExamPart $examPart): array => [
                'code' => $examPart->code,
                'exam' => $examPart->examType->code,
                'name' => $examPart->examType->name.' · '.$examPart->name,
                'types' => $registry->allowedTypes($examPart->examType->code, $examPart->skill->code),
            ])->all(),
            'types' => $questionTypes->map(fn (QuestionType $questionType): array => [
                'code' => $questionType->code, 'name' => $questionType->name,
            ])->all(),
        ];

        $questionQuery = QuestionBankItem::query()
            ->with(['learningItem:id,status,canonical_text', 'examPart.examType', 'examPart.skill', 'questionType', 'difficulty', 'stimulus', 'draftRevision', 'publishedRevision'])
            ->when($search !== '', function ($query) use ($search, $questionId): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('prompt', 'like', '%'.$search.'%')
                        ->orWhereHas('learningItem', fn ($items) => $items->where('canonical_text', 'like', '%'.$search.'%'));
                })->when($questionId !== null, fn ($query) => $query->orWhere('question_bank_items.id', $questionId));
            })
            ->when($exam !== '', fn ($query) => $query->whereHas('examPart.examType', fn ($types) => $types->where('code', $exam)))
            ->when($part !== '', fn ($query) => $query->whereHas('examPart', fn ($parts) => $parts->where('code', $part)))
            ->when($type !== '', fn ($query) => $query->whereHas('questionType', fn ($types) => $types->where('code', $type)))
            ->when($difficulty !== '', fn ($query) => $query->whereHas('difficulty', fn ($levels) => $levels->where('code', $difficulty)))
            ->when($status !== '', fn ($query) => $query->where(fn ($query) => $query->whereHas('draftRevision', fn ($revision) => $revision->where('status', $status))->orWhere(fn ($query) => $query->whereNull('draft_revision_id')->whereHas('learningItem', fn ($items) => $items->where('status', $status)))))
            ->when($missing, fn ($query) => $query->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->whereHas('questionType', fn ($types) => $types->whereIn('code', ['diagram_label_completion', 'plan_map_labelling']))
                        ->whereDoesntHave('draftRevision', fn ($revision) => $revision->whereNotNull('payload->metadata->image_id'))
                        ->whereDoesntHave('publishedRevision', fn ($revision) => $revision->whereNotNull('payload->metadata->image_id'));
                })->orWhere(function ($query): void {
                    $query->whereHas('examPart', fn ($parts) => $parts->where('code', 'part-1')->whereHas('examType', fn ($examTypes) => $examTypes->where('code', 'toeic')))
                        ->whereDoesntHave('draftRevision', fn ($revision) => $revision->whereNotNull('payload->metadata->image_id'))
                        ->whereDoesntHave('publishedRevision', fn ($revision) => $revision->whereNotNull('payload->metadata->image_id'));
                })->orWhere(function ($query): void {
                    $query->whereHas('examPart.skill', fn ($skills) => $skills->where('code', 'listening'))
                        ->whereDoesntHave('draftRevision', fn ($revision) => $revision->whereNotNull('payload->metadata->audio_id'))
                        ->whereDoesntHave('publishedRevision', fn ($revision) => $revision->whereNotNull('payload->metadata->audio_id'))
                        ->whereDoesntHave('stimulus', fn ($stimuli) => $stimuli->where('stimulus_type', 'audio')->where(function ($stimuli): void {
                            $stimuli->whereNotNull('metadata->asset_id')->orWhereNotNull('asset_path');
                        }));
                });
            }))
            ->latest('question_bank_items.updated_at');
        $matchingQuestionIds = (clone $questionQuery)->pluck('question_bank_items.id')->map(fn ($id): int => (int) $id)->all();
        $questions = $questionQuery
            ->paginate(8)
            ->appends(array_filter(['q' => $search, 'exam' => $exam, 'part' => $part, 'type' => $type, 'difficulty' => $difficulty, 'status' => $status, 'missing' => $missing ? '1' : ''], fn (string $value): bool => $value !== ''));
        $missingResourcesByQuestion = $questions->getCollection()->mapWithKeys(fn (QuestionBankItem $question): array => [$question->id => $this->missingResources($question)]);

        return $this->renderScreen('admin-questions', self::SCREENS['admin-questions'])->with([
            'questions' => $questions,
            'matchingQuestionIds' => $matchingQuestionIds,
            'missingResourcesByQuestion' => $missingResourcesByQuestion,
            'filters' => compact('search', 'exam', 'part', 'type', 'difficulty', 'status', 'missing'),
            'examTypes' => $examTypes,
            'examCounts' => $examTypes->mapWithKeys(fn (object $examType): array => [
                $examType->code => QuestionBankItem::whereHas('examPart', fn ($parts) => $parts->where('exam_type_id', $examType->id))->count(),
            ]),
            'parts' => $availableParts,
            'questionTypes' => $questionTypes,
            'filterQuestionTypes' => $filterQuestionTypes,
            'filterCatalog' => $filterCatalog,
            'csvGuide' => json_decode(file_get_contents(resource_path('import-templates/questions/catalog-guide.json')), true, 512, JSON_THROW_ON_ERROR),
            'difficulties' => QuestionDifficulty::orderBy('sort_order')->get(),
            'stats' => [
                'total' => QuestionBankItem::count(),
                'published' => QuestionBankItem::whereHas('learningItem', fn ($items) => $items->where('status', 'published'))->count(),
                'readings' => LearningStimulus::where('stimulus_type', 'reading')->count(),
                'audio' => LearningStimulus::where('stimulus_type', 'audio')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return $this->renderScreen('admin-question-edit', self::SCREENS['admin-question-edit'])->with($this->editorData());
    }

    public function store(QuestionUpsertRequest $request): RedirectResponse
    {
        $question = DB::transaction(fn (): QuestionBankItem => $this->persistQuestion($request->validated()));

        return redirect()->route('admin.questions.edit', $question)->with('status', 'Đã tạo câu hỏi mới.');
    }

    public function import(QuestionImportRequest $request): mixed
    {
        set_time_limit(0);
        $importToken = $request->string('import_key')->toString();
        $progressKey = $this->importProgressKey($request->user()->id, $importToken);
        $offset = (int) $request->input('offset', 0);
        $state = Cache::get($progressKey);
        $fileHash = hash_file('sha256', $request->file('file')->getRealPath());

        if ($offset === 0) {
            $state = ['status' => 'processing', 'processed' => 0, 'total' => 0, 'imported' => 0, 'errors' => [], 'file_hash' => $fileHash, 'audio_groups' => []];
        } else {
            abort_unless(is_array($state)
                && $state['status'] === 'processing'
                && $state['processed'] === $offset
                && isset($state['file_hash'])
                && hash_equals($state['file_hash'], $fileHash), 409, 'Tiến trình import đã hết hạn hoặc file đã thay đổi. Hãy bắt đầu lại.');
        }

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            return redirect()->route('admin.questions.index')->withErrors(['file' => 'Không thể đọc file CSV.']);
        }

        $firstLine = fgets($handle);

        if ($firstLine === false) {
            fclose($handle);

            return redirect()->route('admin.questions.index')->withErrors(['file' => 'File CSV không có dữ liệu.']);
        }

        $delimiter = $this->detectCsvDelimiter($firstLine);
        $headers = array_map(fn ($header): string => $this->normalizeCsvHeader((string) $header), str_getcsv($firstLine, $delimiter, '"', ''));

        if ($this->hasInvalidCsvHeaders($headers)) {
            fclose($handle);

            return redirect()->route('admin.questions.index')->withErrors(['file' => 'CSV phải có hàng tiêu đề hợp lệ và không được trùng tên cột.']);
        }

        $missingHeaders = array_values(array_diff(['exam_part', 'prompt'], $headers));

        if ($missingHeaders !== []) {
            fclose($handle);

            return redirect()->route('admin.questions.index')->withErrors(['file' => 'CSV thiếu cột bắt buộc: '.implode(', ', $missingHeaders).'.']);
        }

        $parts = ExamPart::with(['examType', 'skill'])->get();
        $questionTypes = QuestionType::query()->get(['id', 'code']);
        $difficulties = QuestionDifficulty::query()->get(['id', 'code']);
        $stimuli = LearningStimulus::query()->get(['id', 'title', 'stimulus_type']);
        $partMap = $this->catalogMap($parts, 'code');
        $questionTypeMap = $this->catalogMap($questionTypes, 'code');
        $difficultyMap = $this->catalogMap($difficulties, 'code');
        $stimulusMap = $this->catalogMap($stimuli, 'title');
        $audioGroups = $state['audio_groups'] ?? [];
        $defaultQuestionType = (int) $request->validated('default_question_type');
        $defaultDifficulty = (int) $request->validated('default_difficulty');
        $total = $this->countCsvRows($handle, $delimiter);
        abort_unless($offset <= $total && ($offset === 0 || $state['total'] === $total), 409, 'Không thể tiếp tục import tại vị trí này.');
        $state['total'] = $total;
        Cache::put($progressKey, $state, now()->addMinutes(20));
        $imported = $state['imported'];
        $processed = $offset;
        $errors = $state['errors'];
        $rowNumber = 1;
        $skippedRows = 0;

        while ($skippedRows < $offset && ($skippedFields = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $rowNumber++;
            if (! $this->isBlankCsvRow($skippedFields)) {
                $skippedRows++;
            }
        }

        abort_unless($skippedRows === $offset, 409, 'Không thể tiếp tục import tại vị trí này.');
        $batchProcessed = 0;
        $batchSize = $request->expectsJson() ? 50 : 2000;

        while ($batchProcessed < $batchSize && ($fields = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $rowNumber++;

            if ($this->isBlankCsvRow($fields)) {
                continue;
            }

            $processed++;
            $batchProcessed++;

            if ($processed > 2000) {
                $errors[] = 'Đã dừng ở dòng '.$rowNumber.' vì mỗi lần import tối đa 2.000 câu hỏi.';
                break;
            }

            if (count($fields) > count($headers)) {
                $errors[] = 'Dòng '.$rowNumber.': Số ô vượt số cột; kiểm tra dấu phẩy và dấu nháy kép trong nội dung.';

                continue;
            }

            $values = array_pad(array_slice($fields, 0, count($headers)), count($headers), '');
            $row = array_combine($headers, $values);
            $attributes = $this->attributesFromCsvRow(
                $row,
                $partMap,
                $questionTypeMap,
                $difficultyMap,
                $stimulusMap,
                $defaultQuestionType,
                $defaultDifficulty,
            );
            if (filled($row['payload_json'] ?? null)) {
                $payload = json_decode($row['payload_json'], true);
                if (! is_array($payload) || ! str_starts_with(ltrim($row['payload_json']), '{')) {
                    $errors[] = 'Dòng '.$rowNumber.': payload_json phải là JSON object hợp lệ.';

                    continue;
                }
                $attributes = array_replace($attributes, array_intersect_key($payload, array_flip(['prompt', 'explanation', 'options', 'correct_options', 'accepted_answers', 'metadata'])));
                $attributes['learning_stimulus_id'] = $payload['stimulus_ids'][0] ?? $attributes['learning_stimulus_id'];
                $attributes['question_group_id'] = $payload['group_id'] ?? null;
            }
            $audioGroupName = trim((string) ($row['audio_group'] ?? ''));
            $audioPart = $parts->firstWhere('id', $attributes['exam_part_id']);

            if ($audioPart?->skill?->code === 'listening') {
                $attributes['learning_stimulus_id'] = null;
                if (is_array($attributes['metadata'] ?? null)) {
                    unset($attributes['metadata']['audio_id']);
                }
            }

            if ($audioGroupName === ''
                && ($attributes['skill'] === 'listening' || $audioPart?->skill?->code === 'listening')
                && preg_match('/Audio script ID:\s*([A-Za-z0-9][A-Za-z0-9_-]*)/i', (string) ($row['sample_note'] ?? ''), $audioGroupMatch) === 1) {
                $audioGroupName = $audioGroupMatch[1];
            }

            if ($audioGroupName !== '' && filled($attributes['question_group_id'] ?? null)) {
                $errors[] = 'Dòng '.$rowNumber.': Dùng audio_group hoặc payload_json.group_id, không điền cả hai.';

                continue;
            }

            $audioGroupKey = Str::lower(trim($audioGroupName));
            $attributes['audio_group'] = $audioGroupName;

            if ($audioGroupKey !== '' && isset($audioGroups[$audioGroupKey])) {
                $attributes['question_group_id'] = $audioGroups[$audioGroupKey]['id'];
                $attributes['group_order'] = $audioGroups[$audioGroupKey]['next_order'];
            }

            $validator = Validator::make($attributes, [
                'exam_type' => ['nullable', 'string', 'in:toeic,ielts'],
                'skill' => ['nullable', 'string', 'in:listening,reading,writing,speaking'],
                'exam_part_id' => ['required', 'integer', 'exists:exam_parts,id'],
                'question_type_id' => ['required', 'integer', 'exists:question_types,id'],
                'question_difficulty_id' => ['required', 'integer', 'exists:question_difficulties,id'],
                'learning_stimulus_id' => ['nullable', 'integer', 'exists:learning_stimuli,id'],
                'audio_group' => ['nullable', 'string', 'max:255'],
                'prompt' => ['required', 'string', 'max:10000'],
                'explanation' => ['nullable', 'string', 'max:10000'],
                'status' => ['required', 'string', 'in:draft'],
                'options' => ['nullable', 'array', 'max:30'],
                'options.*' => ['nullable', 'string', 'max:2000'],
                'metadata' => ['nullable', 'array'],
                'correct_options' => ['nullable', 'array', 'max:30'],
                'correct_options.*' => ['string', 'max:12'],
                'question_group_id' => ['nullable', 'integer', 'exists:question_groups,id'],
                'group_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
                'correct_option' => ['nullable', 'string', 'max:12'],
                'accepted_answers' => ['nullable', 'array', 'max:20'],
                'accepted_answers.*' => ['nullable', 'string', 'max:500'],
                'tags' => ['nullable', 'string', 'max:500'],
            ], [
                'exam_type.in' => 'exam_type chỉ dùng toeic hoặc ielts.',
                'skill.in' => 'skill chỉ dùng listening, reading, writing hoặc speaking.',
                'exam_part_id.required' => 'exam_part không hợp lệ; dùng đúng mã Part/Kỹ năng trong hướng dẫn.',
                'question_type_id.required' => 'question_type không hợp lệ; dùng đúng mã loại câu hỏi trong hướng dẫn.',
                'question_difficulty_id.required' => 'difficulty không hợp lệ; dùng easy, medium hoặc hard.',
            ]);

            if ($validator->fails()) {
                $errors[] = 'Dòng '.$rowNumber.': '.$validator->errors()->first();

                continue;
            }

            try {
                $this->validateCsvClassification($attributes, $row, $parts, $questionTypes);
                $persisted = $this->persistCsvQuestion($attributes, $audioGroupName, $audioGroups);

                if ($audioGroupKey !== '') {
                    $audioGroups[$audioGroupKey] = $persisted['audio_group'];
                }

                $imported++;
            } catch (ValidationException $exception) {
                $errors[] = 'Dòng '.$rowNumber.': '.collect($exception->errors())->flatten()->first();
            } catch (Throwable $exception) {
                report($exception);
                $errors[] = 'Dòng '.$rowNumber.': Không thể lưu câu hỏi này.';
            }
        }

        fclose($handle);

        if ($processed < $total && $processed < 2000) {
            $state = ['status' => 'processing', 'processed' => $processed, 'total' => $total, 'imported' => $imported, 'errors' => $errors, 'file_hash' => $fileHash, 'audio_groups' => $audioGroups];
            Cache::put($progressKey, $state, now()->addMinutes(20));

            if ($request->expectsJson()) {
                return response()->json(['status' => 'processing', 'processed' => $processed, 'total' => $total, 'next_offset' => $processed]);
            }
        }

        if ($processed === 2000 && $processed < $total) {
            $errors[] = 'Đã dừng vì mỗi lần import tối đa 2.000 câu hỏi.';
        }

        Cache::put($progressKey, ['status' => 'completed', 'processed' => $processed, 'total' => $total, 'imported' => $imported, 'failed' => count($errors)], now()->addMinutes(20));

        if ($processed === 0) {
            $errors[] = 'File CSV chưa có dòng dữ liệu nào.';
        }

        $summary = ['total' => $processed, 'imported' => $imported, 'failed' => count($errors)];
        if ($request->expectsJson()) {
            $request->session()->flash('import_summary', $summary);
            $request->session()->flash('import_errors', $errors);

            return response()->json(['summary' => $summary, 'errors' => $errors, 'redirect' => route('admin.questions.index')]);
        }

        return redirect()->route('admin.questions.index')->with('import_summary', $summary)->with('import_errors', $errors);
    }

    public function importProgress(Request $request, string $token): mixed
    {
        abort_unless(Str::isUuid($token), 404);
        $state = Cache::get($this->importProgressKey($request->user()->id, $token), []);

        return response()->json([
            'status' => $state['status'] ?? 'waiting',
            'processed' => $state['processed'] ?? 0,
            'total' => $state['total'] ?? 0,
            'imported' => $state['imported'] ?? 0,
            'failed' => $state['failed'] ?? count($state['errors'] ?? []),
        ]);
    }

    public function importTemplate(Request $request): BinaryFileResponse
    {
        $template = $request->string('template', 'all')->toString();
        if (str_starts_with($template, 'type-')) {
            $questionType = substr($template, 5);
            abort_unless(isset(QuestionRegistry::TYPES[$questionType]), 404);
            $filename = 'by-question-type/'.$questionType.'.csv';
        } else {
            abort_unless(isset(self::CSV_TEMPLATES[$template]), 404);
            $filename = self::CSV_TEMPLATES[$template];
        }
        $contentType = str_ends_with($filename, '.csv') ? 'text/csv; charset=UTF-8' : (str_ends_with($filename, '.md') ? 'text/markdown; charset=UTF-8' : 'application/zip');

        return response()->download(resource_path('import-templates/questions/'.$filename), basename($filename), ['Content-Type' => $contentType]);
    }

    public function edit(QuestionBankItem $question): View
    {
        $question->load(['learningItem', 'examPart.examType', 'questionType', 'difficulty', 'stimulus', 'options', 'acceptedAnswers']);

        return $this->renderScreen('admin-question-edit', self::SCREENS['admin-question-edit'])->with($this->editorData($question));
    }

    public function update(QuestionUpsertRequest $request, QuestionBankItem $question): mixed
    {
        $revision = DB::transaction(function () use ($request, $question) {
            $attributes = $request->validated();
            $revision = app(ContentRevisionService::class)->save('question', $question, $this->questionPayload($attributes), $attributes['revision_counter'] ?? null);
            $question->refresh();
            if (! $question->published_revision_id) {
                $question->forceFill(['exam_part_id' => $attributes['exam_part_id'], 'question_type_id' => $attributes['question_type_id'], 'question_difficulty_id' => $attributes['question_difficulty_id'], 'learning_stimulus_id' => $attributes['learning_stimulus_id'] ?? null, 'prompt' => $revision->payload['prompt'], 'explanation' => $revision->payload['explanation'] ?? null, 'question_group_id' => $attributes['question_group_id'] ?? null, 'group_order' => $attributes['group_order'] ?? 0])->save();
                $this->syncAnswers($question, $attributes, QuestionType::findOrFail($attributes['question_type_id']));
            }
            $this->syncTags($question->learningItem, $attributes['tags'] ?? '');

            return $revision;
        });
        if ($request->expectsJson()) {
            return response()->json(['revision_counter' => $revision->number, 'status' => $revision->status]);
        }

        return redirect()->route('admin.questions.edit', $question)->with('status', 'Đã lưu bản nháp.');
    }

    private function persistQuestion(array $attributes): QuestionBankItem
    {
        $learningItem = LearningItem::create(['learning_item_type_id' => $this->questionLearningItemTypeId(), 'canonical_text' => $this->canonicalQuestionText($attributes['prompt'] ?? ''), 'slug' => 'question-'.Str::uuid(), 'created_by' => auth()->id(), 'status' => 'draft']);
        $question = QuestionBankItem::create(['learning_item_id' => $learningItem->id, 'exam_part_id' => $attributes['exam_part_id'], 'question_type_id' => $attributes['question_type_id'], 'question_difficulty_id' => $attributes['question_difficulty_id'], 'learning_stimulus_id' => $attributes['learning_stimulus_id'] ?? null, 'prompt' => app(QuestionRegistry::class)->sanitize($attributes['prompt'] ?? ''), 'explanation' => $attributes['explanation'] ?? null]);
        $question->forceFill(['question_group_id' => $attributes['question_group_id'] ?? null, 'group_order' => $attributes['group_order'] ?? 0])->save();
        $this->syncAnswers($question, $attributes, QuestionType::findOrFail($attributes['question_type_id']));
        $this->syncTags($learningItem, $attributes['tags'] ?? '');
        app(ContentRevisionService::class)->save('question', $question, $this->questionPayload($attributes), 0);

        return $question;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array{id: int, exam_part_id: int, next_order: int}>  $audioGroups
     * @return array{question: QuestionBankItem, audio_group: array{id: int, exam_part_id: int, next_order: int}|null}
     */
    private function persistCsvQuestion(array $attributes, string $audioGroupName, array $audioGroups): array
    {
        return DB::transaction(function () use ($attributes, $audioGroupName, $audioGroups): array {
            if ($audioGroupName === '') {
                return ['question' => $this->persistQuestion($attributes), 'audio_group' => null];
            }

            $groupKey = Str::lower(trim($audioGroupName));
            $audioGroup = $audioGroups[$groupKey] ?? null;

            if ($audioGroup === null) {
                $part = ExamPart::with(['examType', 'skill'])->findOrFail($attributes['exam_part_id']);
                $kind = match ([$part->examType->code, $part->code]) {
                    ['toeic', 'part-3'] => 'conversation',
                    ['toeic', 'part-4'] => 'talk',
                    default => $part->examType->code === 'ielts' ? 'ielts_section' : 'question_group',
                };
                $groupId = DB::table('question_groups')->insertGetId([
                    'title' => trim($audioGroupName),
                    'kind' => $kind,
                    'exam_part_id' => $part->id,
                    'stimulus_ids' => json_encode([], JSON_THROW_ON_ERROR),
                    'metadata' => json_encode(['source' => 'csv_import', 'audio_group' => trim($audioGroupName)], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $audioGroup = ['id' => (int) $groupId, 'exam_part_id' => (int) $part->id, 'next_order' => 0];
            } elseif ($audioGroup['exam_part_id'] !== (int) $attributes['exam_part_id']) {
                throw ValidationException::withMessages(['audio_group' => 'Tất cả câu trong một audio_group phải thuộc cùng exam_part.']);
            }

            $attributes['question_group_id'] = $audioGroup['id'];
            $attributes['group_order'] = $audioGroup['next_order'];
            $question = $this->persistQuestion($attributes);
            $audioGroup['next_order']++;

            return ['question' => $question, 'audio_group' => $audioGroup];
        });
    }

    private function questionPayload(array $attributes): array
    {
        $part = ExamPart::with(['examType', 'skill'])->findOrFail($attributes['exam_part_id']);

        return ['exam_part_id' => $part->id, 'exam_type' => $part->examType->code, 'part' => $part->code, 'skill' => $part->skill->code, 'type' => QuestionType::findOrFail($attributes['question_type_id'])->code, 'prompt' => $attributes['prompt'] ?? '', 'explanation' => $attributes['explanation'] ?? '', 'options' => array_filter($attributes['options'] ?? [], fn ($v) => trim((string) $v) !== ''), 'correct_options' => $attributes['correct_options'] ?? array_values(array_filter([$attributes['correct_option'] ?? null])), 'accepted_answers' => array_values(array_filter($attributes['accepted_answers'] ?? [])), 'metadata' => $attributes['metadata'] ?? [], 'stimulus_ids' => array_values(array_filter([$attributes['learning_stimulus_id'] ?? null])), 'group_id' => $attributes['question_group_id'] ?? null];
    }

    /** @param Collection<int, object> $items */
    private function catalogMap(Collection $items, string $key): Collection
    {
        return $items->mapWithKeys(fn (object $item): array => [Str::lower(trim((string) $item->{$key})) => (int) $item->id]);
    }

    private function resolveCatalogId(Collection $map, ?string $value): ?int
    {
        $normalized = Str::lower(trim((string) $value));

        if ($normalized === '') {
            return null;
        }

        if ($map->has($normalized)) {
            return (int) $map->get($normalized);
        }

        if (ctype_digit($normalized) && $map->contains((int) $normalized)) {
            return (int) $normalized;
        }

        return null;
    }

    /** @param array<int, string> $fields */
    private function isBlankCsvRow(array $fields): bool
    {
        return collect($fields)->every(fn ($value): bool => trim((string) $value) === '');
    }

    private function detectCsvDelimiter(string $line): string
    {
        $delimiters = [',', ';', "\t"];
        usort($delimiters, fn (string $left, string $right): int => substr_count($line, $right) <=> substr_count($line, $left));

        return $delimiters[0];
    }

    private function countCsvRows(mixed $handle, string $delimiter): int
    {
        $total = 0;
        while (($fields = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            if (! $this->isBlankCsvRow($fields)) {
                $total++;
            }
        }
        rewind($handle);
        fgets($handle);

        return $total;
    }

    private function importProgressKey(int $userId, string $token): string
    {
        $safeToken = Str::isUuid($token) ? $token : 'no-token';

        return 'question-import:'.$userId.':'.$safeToken;
    }

    /** @return list<string> */
    private function missingResources(QuestionBankItem $question): array
    {
        $payload = $question->draftRevision?->payload ?? $question->publishedRevision?->payload ?? [];
        $metadata = $payload['metadata'] ?? [];
        $stimulus = $question->stimulus;
        $missing = [];

        if ($question->examPart?->skill?->code === 'listening' && empty($metadata['audio_id']) && ! ($stimulus?->stimulus_type === 'audio' && (! empty($stimulus->metadata['asset_id']) || filled($stimulus->asset_path)))) {
            $missing[] = 'audio';
        }
        if ((in_array($question->questionType?->code, ['diagram_label_completion', 'plan_map_labelling'], true) || ($question->examPart?->examType?->code === 'toeic' && $question->examPart?->code === 'part-1')) && empty($metadata['image_id'])) {
            $missing[] = 'ảnh';
        }

        return $missing;
    }

    /** @param array<int, string> $headers */
    private function hasInvalidCsvHeaders(array $headers): bool
    {
        return $headers === [] || collect($headers)->contains('') || count($headers) !== count(array_unique($headers));
    }

    private function normalizeCsvHeader(string $header): string
    {
        $header = trim(ltrim($header, "\xEF\xBB\xBF"));
        $header = (string) preg_replace('/[\s\-]+/', '_', Str::lower($header));

        return self::CSV_HEADER_ALIASES[$header] ?? $header;
    }

    /**
     * @param  array<string, string>  $row
     * @param  Collection<string, int>  $partMap
     * @param  Collection<string, int>  $questionTypeMap
     * @param  Collection<string, int>  $difficultyMap
     * @param  Collection<string, int>  $stimulusMap
     * @return array<string, mixed>
     */
    private function attributesFromCsvRow(
        array $row,
        Collection $partMap,
        Collection $questionTypeMap,
        Collection $difficultyMap,
        Collection $stimulusMap,
        int $defaultQuestionType,
        int $defaultDifficulty,
    ): array {
        $options = collect([
            'A' => $row['option_a'] ?? '',
            'B' => $row['option_b'] ?? '',
            'C' => $row['option_c'] ?? '',
            'D' => $row['option_d'] ?? '',
        ])->map(fn ($option): string => trim((string) $option))->filter()->all();
        $acceptedAnswers = preg_split('/\s*[|;]\s*/', trim((string) ($row['accepted_answers'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
        $questionType = trim((string) ($row['question_type'] ?? ''));
        $difficulty = trim((string) ($row['difficulty'] ?? ''));

        return [
            'exam_type' => Str::lower(trim((string) ($row['exam_type'] ?? ''))),
            'skill' => Str::lower(trim((string) ($row['skill'] ?? ''))),
            'exam_part_id' => $this->resolveCatalogId($partMap, $row['exam_part'] ?? null),
            'question_type_id' => $questionType === '' ? $defaultQuestionType : $this->resolveCatalogId($questionTypeMap, $questionType),
            'question_difficulty_id' => $difficulty === '' ? $defaultDifficulty : $this->resolveCatalogId($difficultyMap, $difficulty),
            'learning_stimulus_id' => $this->resolveCatalogId($stimulusMap, $row['stimulus'] ?? null),
            'prompt' => trim((string) ($row['prompt'] ?? '')),
            'options' => $options,
            'correct_option' => Str::upper(trim((string) ($row['correct_option'] ?? ''))),
            'accepted_answers' => $acceptedAnswers === false ? [] : array_map('trim', $acceptedAnswers),
            'explanation' => trim((string) ($row['explanation'] ?? '')),
            'tags' => trim((string) ($row['tags'] ?? '')),
            'status' => 'draft',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, string>  $row
     * @param  Collection<int, ExamPart>  $parts
     * @param  Collection<int, QuestionType>  $questionTypes
     */
    private function validateCsvClassification(array $attributes, array $row, Collection $parts, Collection $questionTypes): void
    {
        $part = $parts->firstWhere('id', $attributes['exam_part_id']);
        $questionType = $questionTypes->firstWhere('id', $attributes['question_type_id']);
        $errors = [];

        if ($attributes['exam_type'] !== '' && $attributes['exam_type'] !== $part->examType->code) {
            $errors['exam_type'] = 'exam_type không khớp exam_part; '.$part->code.' thuộc '.$part->examType->name.'.';
        }
        if ($attributes['skill'] !== '' && $attributes['skill'] !== $part->skill->code) {
            $errors['skill'] = 'skill không khớp exam_part; '.$part->code.' thuộc '.$part->skill->code.'.';
        }
        if (! in_array($questionType->code, app(QuestionRegistry::class)->allowedTypes($part->examType->code, $part->skill->code), true)) {
            $errors['question_type'] = 'Loại '.$questionType->code.' không phù hợp '.$part->examType->name.' / '.$part->skill->code.'.';
        }
        if ($part->skill->code !== 'listening' && filled($row['stimulus'] ?? null) && ! $attributes['learning_stimulus_id']) {
            $errors['stimulus'] = 'Không tìm thấy passage; dùng ID hoặc tiêu đề chính xác trong kho tài nguyên.';
        }
        if (filled($attributes['audio_group'] ?? null) && $part->skill->code !== 'listening') {
            $errors['audio_group'] = 'audio_group chỉ dùng để gom câu hỏi Listening.';
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /** @return array<string, mixed> */
    private function editorData(?QuestionBankItem $question = null): array
    {
        return [
            'question' => $question,
            'payload' => $question ? app(ContentRevisionService::class)->payload('question', $question) : [],
            'revision' => $question ? ContentRevision::find($question->draft_revision_id) : null,
            'groups' => DB::table('question_groups')->orderBy('title')->get(),
            'assets' => MediaAsset::latest()->limit(200)->get(),
            'selectedStimulusId' => request()->integer('stimulus') ?: null,
            'parts' => ExamPart::with('examType')->orderBy('exam_type_id')->orderBy('sort_order')->get(),
            'questionTypes' => QuestionType::orderBy('id')->get(),
            'difficulties' => QuestionDifficulty::orderBy('sort_order')->get(),
            'stimuli' => LearningStimulus::query()->whereIn('stimulus_type', ['reading', 'audio'])->orderBy('stimulus_type')->orderBy('title')->get(),
            'tags' => $question ? DB::table('learning_item_tags')->join('tags', 'tags.id', '=', 'learning_item_tags.tag_id')->where('learning_item_id', $question->learning_item_id)->orderBy('tags.name')->pluck('tags.name')->implode(', ') : '',
        ];
    }

    private function questionLearningItemTypeId(): int
    {
        DB::table('learning_item_types')->insertOrIgnore(['code' => 'question', 'label' => 'Câu hỏi', 'sort_order' => 10]);

        return (int) DB::table('learning_item_types')->where('code', 'question')->value('id');
    }

    private function canonicalQuestionText(string $prompt): string
    {
        $normalized = (string) preg_replace('/\s+/', ' ', $prompt);

        return Str::substr($normalized, 0, 217).' #'.Str::uuid();
    }

    /** @param array<string, mixed> $attributes */
    private function validateAnswers(array $attributes, QuestionType $questionType): void
    {
        $options = collect($attributes['options'] ?? [])->map(fn ($option): string => trim((string) $option))->filter();
        $acceptedAnswers = collect($attributes['accepted_answers'] ?? [])->map(fn ($answer): string => trim((string) $answer))->filter()->values();

        if ($questionType->supports_options && ($options->count() < 2 || ! $options->has((string) ($attributes['correct_option'] ?? '')))) {
            throw ValidationException::withMessages(['options' => 'Câu hỏi dạng chọn đáp án cần ít nhất 2 phương án và một đáp án đúng.']);
        }

        if (! $questionType->supports_options && $acceptedAnswers->isEmpty() && $questionType->code !== 'essay') {
            throw ValidationException::withMessages(['accepted_answers' => 'Hãy thêm ít nhất một đáp án được chấp nhận.']);
        }
    }

    /** @param array<string, mixed> $attributes */
    private function syncAnswers(QuestionBankItem $question, array $attributes, QuestionType $questionType): void
    {
        foreach ($question->options as $option) {
            if (! array_key_exists($option->option_key, $attributes['options'] ?? []) && ! DB::table('attempt_answer_options')->where('question_option_id', $option->id)->exists()) {
                $option->delete();
            }
        }
        $question->acceptedAnswers()->delete();

        if ($questionType->supports_options) {
            $correctOption = (string) ($attributes['correct_option'] ?? '');
            $options = collect($attributes['options'] ?? [])->map(fn ($option): string => trim((string) $option))->filter();
            $sortOrder = 0;
            foreach ($options as $key => $content) {
                $existing = $question->options()->where('option_key', (string) $key)->first();
                if ($existing && DB::table('attempt_answer_options')->where('question_option_id', $existing->id)->exists()) {
                    continue;
                }
                $question->options()->updateOrCreate(['option_key' => (string) $key], [
                    'question_bank_item_id' => $question->id,
                    'option_key' => (string) $key,
                    'content' => $content,
                    'sort_order' => $sortOrder++,
                    'is_correct' => in_array((string) $key, $attributes['correct_options'] ?? [$correctOption], true),
                ]);
            }

            return;
        }

        foreach (collect($attributes['accepted_answers'] ?? [])->map(fn ($answer): string => trim((string) $answer))->filter()->values() as $sortOrder => $answer) {
            QuestionAcceptedAnswer::create([
                'question_bank_item_id' => $question->id,
                'answer_text' => $answer,
                'normalized_answer' => Str::lower($answer),
                'sort_order' => $sortOrder,
            ]);
        }
    }

    private function syncTags(LearningItem $learningItem, string $input): void
    {
        DB::table('learning_item_tags')->where('learning_item_id', $learningItem->id)->delete();
        $tags = collect(explode(',', $input))->map(fn (string $tag): string => trim($tag))->filter()->unique();

        foreach ($tags as $tag) {
            $slug = Str::slug($tag);
            if ($slug === '') {
                continue;
            }

            DB::table('tags')->updateOrInsert(
                ['slug' => $slug],
                ['name' => $tag, 'created_at' => now(), 'updated_at' => now()],
            );
            $tagId = DB::table('tags')->where('slug', $slug)->value('id');
            DB::table('learning_item_tags')->insertOrIgnore(['learning_item_id' => $learningItem->id, 'tag_id' => $tagId]);
        }
    }
}
