<?php

namespace VistiqAdmin\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use VistiqAdmin\Http\Requests\Admin\StimulusStoreRequest;
use VistiqCore\Models\ContentRevision;
use VistiqCore\Models\LearningStimulus;
use VistiqCore\Models\MediaAsset;
use VistiqCore\Services\ContentRevisionService;
use VistiqCore\Services\MediaService;
use VistiqCore\Services\QuestionRegistry;

class StimulusController extends AdminPageController
{
    public function reading(Request $request): mixed
    {
        return $this->index($request, 'reading');
    }

    public function audio(Request $request): mixed
    {
        return $this->index($request, 'audio');
    }

    private function index(Request $request, string $type): mixed
    {
        $search = $request->string('q')->toString();
        $rows = LearningStimulus::withCount('questions')->where('stimulus_type', $type)->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.$search.'%'))->latest()->paginate(15)->withQueryString();
        $selected = $request->integer('stimulus') ? LearningStimulus::with('questions')->findOrFail($request->integer('stimulus')) : null;

        return $this->page($type === 'reading' ? 'Passage' : 'Audio', 'pages.admin.workspace.stimulus-editor', [
            'stimulusType' => $type,
            'stimuli' => $rows,
            'selectedStimulus' => $selected,
            'workflowRevision' => $selected ? ContentRevision::find($selected->draft_revision_id) : null,
            'search' => $search,
            'assetUrl' => $selected ? null : null,
            'payload' => $selected ? app(ContentRevisionService::class)->payload('stimulus', $selected) : [],
            'assets' => MediaAsset::latest()->limit(200)->get(),
        ]);
    }

    public function storeReading(StimulusStoreRequest $request): mixed
    {
        return $this->store($request, 'reading');
    }

    public function storeAudio(StimulusStoreRequest $request): mixed
    {
        return $this->store($request, 'audio');
    }

    private function store(StimulusStoreRequest $request, string $type): mixed
    {
        $stimulus = DB::transaction(function () use ($request, $type) {
            $data = $this->payload($request);
            $data['stimulus_type'] = $type;
            $root = LearningStimulus::create(['created_by' => auth()->id(), 'title' => $data['title'], 'stimulus_type' => $type, 'body' => app(QuestionRegistry::class)->sanitize($data['body'] ?? ''), 'status' => 'draft']);
            app(ContentRevisionService::class)->save('stimulus', $root, $data, 0);

            return $root;
        });

        return redirect()->route($type === 'reading' ? 'admin.readings.index' : 'admin.audio.index', ['stimulus' => $stimulus->id])->with('status', 'Đã lưu tài nguyên bản nháp.');
    }

    public function update(StimulusStoreRequest $request, LearningStimulus $stimulus, ContentRevisionService $revisions): mixed
    {
        $revision = $revisions->save('stimulus', $stimulus, $this->payload($request), $request->validated('revision_counter'));

        return $request->expectsJson() ? response()->json(['revision_counter' => $revision->number]) : back()->with('status', 'Đã lưu bản nháp tài nguyên.');
    }

    private function payload(StimulusStoreRequest $request): array
    {
        $data = $request->safe()->except(['media', 'status', 'revision_counter']);
        if ($request->hasFile('media')) {
            $asset = app(MediaService::class)->store($request->file('media'), auth()->id());
            $data['metadata']['asset_id'] = $asset->id;
        }

        return $data;
    }
}
