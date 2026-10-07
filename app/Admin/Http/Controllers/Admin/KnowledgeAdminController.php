<?php

namespace VistiqAdmin\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use VistiqAdmin\Http\Requests\Admin\KnowledgeUpsertRequest;
use VistiqCore\Models\ContentRevision;
use VistiqCore\Models\LearningItem;
use VistiqCore\Services\ContentRevisionService;

class KnowledgeAdminController extends AdminPageController
{
    public function index(Request $request): mixed
    {
        $types = DB::table('learning_item_types')->where('code', '!=', 'question')->orderBy('sort_order')->get();
        $selectedType = $request->string('type')->toString();
        $typeIds = $types->pluck('id', 'code');
        if (! $typeIds->has($selectedType)) {
            $selectedType = '';
        }

        $query = LearningItem::query()
            ->whereIn('learning_item_type_id', $types->pluck('id'))
            ->when($request->filled('q'), fn ($items) => $items->where('canonical_text', 'like', '%'.$request->string('q')->toString().'%'))
            ->when($selectedType !== '', fn ($items) => $items->where('learning_item_type_id', $typeIds[$selectedType]));
        $typeCounts = LearningItem::query()
            ->selectRaw('learning_item_type_id, COUNT(*) as item_count')
            ->whereIn('learning_item_type_id', $types->pluck('id'))
            ->groupBy('learning_item_type_id')
            ->pluck('item_count', 'learning_item_type_id');

        return $this->page('Kho kiến thức', 'pages.admin.workspace.knowledge', [
            'items' => $query->latest()->paginate(20)->withQueryString(),
            'types' => $types,
            'typeCounts' => $typeCounts,
            'typeIds' => $typeIds,
            'levelsById' => DB::table('proficiency_levels')->pluck('label', 'id'),
            'selectedType' => $selectedType,
            'search' => $request->string('q')->toString(),
        ]);
    }

    public function create(Request $request, ContentRevisionService $revisions): mixed
    {
        return $this->editor(new LearningItem, $revisions, $request->string('type')->toString());
    }

    public function edit(LearningItem $knowledge, ContentRevisionService $revisions): mixed
    {
        return $this->editor($knowledge, $revisions);
    }

    private function editor(LearningItem $item, ContentRevisionService $revisions, string $selectedType = 'vocabulary'): mixed
    {
        $types = DB::table('learning_item_types')->where('code', '!=', 'question')->orderBy('sort_order')->get();
        if (! $types->contains('code', $selectedType)) {
            $selectedType = 'vocabulary';
        }

        $payload = $item->exists ? $revisions->payload('knowledge', $item) : ['type' => $selectedType];
        $selectedType = $payload['type'] ?? $selectedType;
        $dictionaryEntries = DB::table('dictionary_entries')
            ->join('learning_items', 'learning_items.id', '=', 'dictionary_entries.learning_item_id')
            ->leftJoin('learning_item_meanings', function ($join): void {
                $join->on('learning_item_meanings.learning_item_id', '=', 'learning_items.id')->where('learning_item_meanings.sense_number', 1);
            })
            ->orderBy('learning_items.canonical_text')
            ->get(['dictionary_entries.id', 'learning_items.canonical_text', 'learning_item_meanings.translation']);
        $relatedItems = LearningItem::query()
            ->join('learning_item_types', 'learning_item_types.id', '=', 'learning_items.learning_item_type_id')
            ->where('learning_item_types.code', '!=', 'question')
            ->when($item->exists, fn ($items) => $items->where('learning_items.id', '!=', $item->id))
            ->orderBy('learning_items.canonical_text')
            ->limit(500)
            ->get(['learning_items.id', 'learning_items.canonical_text']);

        return $this->page('Biên soạn kiến thức', 'pages.admin.workspace.knowledge-editor', [
            'item' => $item,
            'workflowRevision' => $item->exists ? ContentRevision::find($item->draft_revision_id) : null,
            'payload' => $payload,
            'types' => $types,
            'selectedType' => $selectedType,
            'levels' => DB::table('proficiency_levels')->orderBy('sort_order')->get(),
            'partsOfSpeech' => DB::table('parts_of_speech')->orderBy('sort_order')->get(),
            'categories' => DB::table('grammar_categories')->orderBy('sort_order')->get(),
            'dictionaryEntries' => $dictionaryEntries,
            'relatedItems' => $relatedItems,
        ]);
    }

    public function store(KnowledgeUpsertRequest $request, ContentRevisionService $revisions): mixed
    {
        $item = DB::transaction(function () use ($request, $revisions) {
            $data = $request->validated();
            $item = LearningItem::create(['learning_item_type_id' => DB::table('learning_item_types')->where('code', $data['type'])->value('id'), 'canonical_text' => $data['english'] ?? 'Bản nháp', 'slug' => Str::slug($data['english'] ?? 'draft').'-'.Str::random(12), 'created_by' => auth()->id(), 'status' => 'draft']);
            $revisions->save('knowledge', $item, $data, 0);

            return $item;
        });

        return redirect()->route('admin.knowledge.edit', $item)->with('status', 'Đã tạo kiến thức bản nháp.');
    }

    public function update(KnowledgeUpsertRequest $request, LearningItem $knowledge, ContentRevisionService $revisions): mixed
    {
        $revisions->save('knowledge', $knowledge, $request->validated(), $request->validated('revision_counter'));

        return $request->expectsJson() ? response()->json(['revision_counter' => $knowledge->fresh()->revision_counter]) : back()->with('status', 'Đã lưu bản nháp cần duyệt.');
    }
}
