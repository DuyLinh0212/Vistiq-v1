<?php

namespace VistiqAdmin\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use VistiqAdmin\Http\Requests\Admin\AdminActionRequest;
use VistiqCore\Models\ContentRevision;
use VistiqCore\Models\ExamPart;
use VistiqCore\Models\LearningStimulus;
use VistiqCore\Models\QuestionBankItem;
use VistiqCore\Services\AuditService;
use VistiqCore\Services\ContentRevisionService;

class ContentWorkflowController extends AdminPageController
{
    public function transition(AdminActionRequest $request, string $kind, int $id, string $action, ContentRevisionService $revisions): mixed
    {
        $revision = $revisions->transition($kind, $revisions->root($kind, $id), $action, $request->validated('note'));

        return $request->expectsJson() ? response()->json(['status' => $revision->status]) : back()->with('status', 'Đã cập nhật trạng thái nội dung.');
    }

    public function cloneDraft(AdminActionRequest $request, string $kind, int $id, ContentRevisionService $revisions): mixed
    {
        Gate::authorize('content.edit');
        $root = $revisions->root($kind, $id);
        $revisions->save($kind, $root, $revisions->payload($kind, $root), $root->revision_counter);

        return back()->with('status', 'Đã tạo bản nháp mới từ phiên bản hiện tại.');
    }

    public function destroy(AdminActionRequest $request, string $kind, int $id, ContentRevisionService $revisions): mixed
    {
        Gate::authorize('content.edit');
        $revisions->delete($kind, $revisions->root($kind, $id));

        return back()->with('status', 'Đã xóa bản nháp chưa sử dụng.');
    }

    public function bulk(AdminActionRequest $request, ContentRevisionService $revisions): mixed
    {
        $data = $request->validated();
        $ids = array_values(array_unique(array_merge($data['ids'] ?? [], $data['matching_ids'] ?? [])));
        abort_unless($ids !== [] && ! empty($data['action']), 422);

        if ($data['action'] === 'delete_unused') {
            Gate::authorize('content.edit');
            $deleted = 0;
            $skipped = 0;

            $operationKey = $data['operation_key'] ?? null;
            $deleteOffset = (int) ($data['delete_offset'] ?? 0);
            $selectionTotal = (int) ($data['selection_total'] ?? count($ids));
            $isFinalBatch = (bool) ($data['final_batch'] ?? false);

            if ($operationKey !== null) {
                $cacheKey = 'question-delete:'.$request->user()->getAuthIdentifier().':'.$operationKey;
                $state = Cache::get($cacheKey);

                if (($state['status'] ?? null) === 'completed') {
                    return response()->json($state);
                }

                if ($deleteOffset === 0 && $state === null) {
                    $state = ['status' => 'processing', 'processed' => 0, 'total' => $selectionTotal, 'deleted' => 0, 'skipped' => 0];
                    Cache::add($cacheKey, $state, now()->addMinutes(20));
                    $state = Cache::get($cacheKey, $state);
                }

                abort_unless(
                    is_array($state)
                        && ($state['status'] ?? null) === 'processing'
                        && ($state['processed'] ?? -1) === $deleteOffset
                        && ($state['total'] ?? -1) === $selectionTotal,
                    409,
                    'Lô xóa không đúng tiến trình hiện tại.'
                );
                abort_unless($deleteOffset + count($ids) <= $selectionTotal, 422, 'Số lượng câu hỏi vượt quá tiến trình đã chọn.');
                abort_unless(! $isFinalBatch || $deleteOffset + count($ids) === $selectionTotal, 422, 'Lô cuối chưa khớp tổng số câu hỏi.');
            }

            $questionIdsUsedInDraftExams = $revisions->questionsUsedInDraftExams(array_map('intval', $ids));

            foreach ($ids as $id) {
                try {
                    $revisions->delete('question', $revisions->root('question', (int) $id), $questionIdsUsedInDraftExams);
                    $deleted++;
                } catch (HttpExceptionInterface $exception) {
                    if ($exception->getStatusCode() !== 409) {
                        throw $exception;
                    }

                    $skipped++;
                }
            }

            if ($operationKey !== null) {
                $state['processed'] += count($ids);
                $state['deleted'] += $deleted;
                $state['skipped'] += $skipped;
                $state['status'] = $isFinalBatch ? 'completed' : 'processing';
                $state['completed'] = $isFinalBatch;

                Cache::put($cacheKey, $state, now()->addMinutes(20));

                if ($isFinalBatch) {
                    $request->session()->flash('status', "Đã xóa {$state['deleted']} câu hỏi; bỏ qua {$state['skipped']} câu đã xuất bản hoặc đang được dùng trong đề.");
                }

                return response()->json($state);
            }

            return back()->with('status', "Đã xóa {$deleted} câu hỏi; bỏ qua {$skipped} câu đã xuất bản hoặc đang được dùng trong đề.");
        }

        DB::transaction(function () use ($data, $ids, $revisions): void {
            foreach ($ids as $id) {
                $revisions->transition('question', $revisions->root('question', $id), $data['action'], $data['note'] ?? null);
            }
        });

        return back()->with('status', 'Đã xử lý các câu hỏi đã chọn.');
    }

    public function export(Request $request, ContentRevisionService $revisions): mixed
    {
        return response()->streamDownload(function () use ($revisions): void {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['exam_part', 'question_type', 'difficulty', 'prompt', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option', 'accepted_answers', 'explanation', 'tags', 'stimulus', 'status', 'payload_json'], escape: '');
            foreach (QuestionBankItem::with(['questionType', 'difficulty'])->cursor() as $question) {
                $p = $revisions->payload('question', $question);
                $safe = fn ($v) => preg_match('/^[=+@\-\t\r]/', (string) $v) ? "'".$v : $v;
                $row = [$p['exam_part_id'], $p['type'], $question->difficulty->code, strip_tags($p['prompt'])];
                foreach (['A', 'B', 'C', 'D'] as $key) {
                    $row[] = $p['options'][$key] ?? '';
                }
                $row[] = implode('|', $p['correct_options'] ?? []);
                $row[] = implode('|', $p['accepted_answers'] ?? []);
                $row[] = strip_tags($p['explanation'] ?? '');
                $row[] = '';
                $row[] = $p['stimulus_ids'][0] ?? '';
                $row[] = 'draft';
                $row[] = json_encode($p, JSON_UNESCAPED_UNICODE);
                fputcsv($file, array_map($safe, $row), escape: '');
            } fclose($file);
        }, 'questions.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function groups(): mixed
    {
        return $this->page('Nhóm câu hỏi', 'pages.admin.workspace.groups', ['groups' => DB::table('question_groups')->paginate(20), 'parts' => ExamPart::with(['examType', 'skill'])->get(), 'stimuli' => LearningStimulus::orderBy('title')->get()]);
    }

    public function updateGroup(AdminActionRequest $request, int $group, AuditService $audit): mixed
    {
        Gate::authorize('content.edit');
        $data = $request->validated();
        DB::transaction(function () use ($group, $data, $audit): void {
            $row = DB::table('question_groups')->where('id', $group)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_if(QuestionBankItem::where('question_group_id', $group)->whereNotNull('published_revision_id')->exists(), 409, 'Nhóm đã dùng trong nội dung xuất bản. Tạo nhóm mới cho phiên bản mới.');
            abort_unless(! empty($data['title']) && ! empty($data['kind']) && ! empty($data['exam_part_id']), 422);
            DB::table('question_groups')->where('id', $group)->update(['title' => $data['title'], 'kind' => $data['kind'], 'exam_part_id' => $data['exam_part_id'], 'stimulus_ids' => json_encode($data['stimulus_ids'] ?? []), 'updated_at' => now()]);
            $audit->record('group.update', 'group', $group, (array) $row, $data);
        });

        return back()->with('status', 'Đã cập nhật nhóm.');
    }

    public function destroyGroup(AdminActionRequest $request, int $group, AuditService $audit): mixed
    {
        Gate::authorize('content.edit');
        DB::transaction(function () use ($group, $audit): void {
            abort_unless(DB::table('question_groups')->where('id', $group)->lockForUpdate()->first(), 404);
            abort_if(QuestionBankItem::where('question_group_id', $group)->exists() || ContentRevision::where('kind', 'question')->where('payload->group_id', $group)->exists(), 409, 'Nhóm đang được sử dụng.');
            DB::table('question_groups')->where('id', $group)->delete();
            $audit->record('group.delete', 'group', $group);
        });

        return back()->with('status', 'Đã xóa nhóm chưa sử dụng.');
    }

    public function storeGroup(AdminActionRequest $request, AuditService $audit): mixed
    {
        Gate::authorize('content.edit');
        $data = $request->validated();
        abort_unless(! empty($data['title']) && ! empty($data['kind']) && ! empty($data['exam_part_id']), 422);
        $id = DB::table('question_groups')->insertGetId(['title' => $data['title'], 'kind' => $data['kind'], 'exam_part_id' => $data['exam_part_id'], 'stimulus_ids' => json_encode($data['stimulus_ids'] ?? []), 'metadata' => json_encode($data['metadata'] ?? []), 'created_at' => now(), 'updated_at' => now()]);
        $audit->record('group.create', 'group', $id);

        return back()->with('status', 'Đã tạo nhóm.');
    }
}
