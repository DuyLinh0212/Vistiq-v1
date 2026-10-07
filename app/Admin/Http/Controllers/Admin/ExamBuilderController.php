<?php

namespace VistiqAdmin\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use VistiqAdmin\Http\Requests\Admin\AdminActionRequest;
use VistiqAdmin\Http\Requests\Admin\ExamUpsertRequest;
use VistiqCore\Models\ExamPart;
use VistiqCore\Models\ExamTemplate;
use VistiqCore\Models\ExamType;
use VistiqCore\Models\QuestionBankItem;
use VistiqCore\Services\AttemptService;
use VistiqCore\Services\AuditService;
use VistiqCore\Services\ExamPublishingService;

class ExamBuilderController extends AdminPageController
{
    public function index(): mixed
    {
        return $this->page('Đề thi', 'pages.admin.workspace.exams', ['exams' => ExamTemplate::with('examType')->latest()->paginate(15)]);
    }

    public function create(): mixed
    {
        return $this->editor(new ExamTemplate(['mode' => 'mini', 'draft_payload' => ['sections' => [], 'policies' => ExamPublishingService::POLICIES]]));
    }

    public function edit(ExamTemplate $exam): mixed
    {
        return $this->editor($exam);
    }

    private function editor(ExamTemplate $exam): mixed
    {
        return $this->page('Soạn đề thi', 'pages.admin.workspace.exam-editor', ['exam' => $exam, 'defaultPolicies' => ExamPublishingService::POLICIES, 'types' => ExamType::all(), 'parts' => ExamPart::with(['examType', 'skill'])->get(), 'questions' => QuestionBankItem::with(['learningItem', 'examPart'])->orderBy('id')->get(), 'scales' => DB::table('score_scales')->get()]);
    }

    public function store(ExamUpsertRequest $request, ExamPublishingService $service): mixed
    {
        $exam = DB::transaction(function () use ($request, $service) {
            $modeId = DB::table('practice_modes')->orderBy('id')->value('id');
            $exam = ExamTemplate::create(['title' => $request->validated('title'), 'slug' => Str::slug($request->validated('title')).'-'.Str::random(8), 'exam_type_id' => $request->validated('exam_type_id'), 'practice_mode_id' => $modeId, 'created_by' => auth()->id(), 'status' => 'draft']);

            return $service->save($exam, $request->validated(), 0);
        });

        return redirect()->route('admin.exams.edit', $exam)->with('status', 'Đã tạo đề bản nháp.');
    }

    public function update(ExamUpsertRequest $request, ExamTemplate $exam, ExamPublishingService $service): mixed
    {
        $exam = $service->save($exam, $request->validated(), $request->validated('revision_counter'));

        return $request->expectsJson() ? response()->json(['revision_counter' => $exam->revision_counter]) : back()->with('status', 'Đã lưu đề.');
    }

    public function transition(AdminActionRequest $request, ExamTemplate $exam, string $action, ExamPublishingService $service, AuditService $audit): mixed
    {
        if ($action === 'publish') {
            $service->publish($exam);
        } else {
            DB::transaction(function () use ($request, $exam, $action, $service, $audit): void {
                $exam = ExamTemplate::lockForUpdate()->findOrFail($exam->id);
                $ability = match ($action) {
                    'review' => 'content.edit','approve','reject' => 'content.review','archive' => 'content.publish',default => abort(404)
                };
                Gate::authorize($ability);
                $states = ['review' => 'draft', 'approve' => 'review', 'reject' => 'review', 'archive' => 'published'];
                abort_unless(($action === 'archive' ? $exam->status : $exam->draft_status) === $states[$action], 409);
                if ($action === 'reject' && ! $request->filled('note')) {
                    throw ValidationException::withMessages(['note' => 'Cần lý do từ chối.']);
                }
                if ($action === 'review') {
                    $errors = $service->problems($service->assemble($exam));
                    if ($errors) {
                        throw ValidationException::withMessages(['exam' => $errors]);
                    }
                }
                $exam->update($action === 'archive' ? ['status' => 'archived'] : ['draft_status' => match ($action) {
                    'review' => 'review','approve' => 'approved','reject' => 'draft'
                }, 'review_note' => $request->validated('note')]);
                $audit->record('exam.'.$action, 'exam', $exam->id);
            });
        }

        return back()->with('status', 'Đã cập nhật trạng thái đề.');
    }

    public function validation(ExamTemplate $exam, ExamPublishingService $service): mixed
    {
        return response()->json(['errors' => $service->problems($service->assemble($exam, true))]);
    }

    public function preview(ExamTemplate $exam, ExamPublishingService $service): mixed
    {
        $snapshot = $service->assemble($exam, true);
        $warnings = $service->problems($snapshot);
        $snapshot['policies']['strict_audio'] = false;
        $snapshot = app(AttemptService::class)->publicSnapshot($snapshot);

        return view('exam.runner', ['preview' => true, 'attempt' => null, 'snapshot' => $snapshot, 'endpoints' => [], 'warnings' => $warnings]);
    }
}
