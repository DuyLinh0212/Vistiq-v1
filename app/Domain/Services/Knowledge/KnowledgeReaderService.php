<?php

namespace VistiqCore\Services\Knowledge;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use VistiqCore\Models\LearningItem;
use VistiqCore\Services\Assessment\ContentApproval\ContentRevisionService;

class KnowledgeReaderService
{
    public function publishedQuery(array $filters = []): Builder
    {
        return DB::table('learning_items as item')
            ->join('learning_item_types as type', 'type.id', '=', 'item.learning_item_type_id')
            ->leftJoin('proficiency_levels as level', 'level.id', '=', 'item.proficiency_level_id')
            ->where('item.status', 'published')->where('type.code', '!=', 'question')
            ->where(fn ($q) => $q->whereNull('item.published_at')->orWhere('item.published_at', '<=', now()))
            ->when(! empty($filters['q']), fn ($q) => $q->where('item.canonical_text', 'like', '%'.$filters['q'].'%'))
            ->when(! empty($filters['type']) && $filters['type'] !== 'all', fn ($q) => $q->where('type.code', $filters['type']))
            ->when(! empty($filters['level']), fn ($q) => $q->where('level.code', $filters['level']))
            ->select('item.id', 'item.canonical_text', 'item.slug', 'type.code as type_code', 'type.label as type_label', 'level.code as level_code', 'level.label as level_label');
    }

    /** Public read contract for Knowledge and Profile; owner is supplied by the authenticated caller. */
    public function savedItems(int $owner, array $filters = []): mixed
    {
        return $this->publishedQuery($filters)->join('user_saved_items as saved', 'saved.learning_item_id', '=', 'item.id')
            ->where('saved.user_id', $owner)->addSelect('saved.personal_note', 'saved.is_starred', 'saved.created_at as saved_at')
            ->orderByDesc('saved.created_at')->orderByDesc('saved.id')->paginate(20)->withQueryString();
    }

    public function detail(string $slug): array
    {
        $summary = $this->publishedQuery()->where('item.slug', $slug)->first();
        abort_unless($summary, 404, 'Mục kiến thức không tồn tại hoặc chưa được xuất bản.');
        $item = LearningItem::findOrFail($summary->id);
        $payload = app(ContentRevisionService::class)->payload('knowledge', $item, false);
        $payload['content'] = app(KnowledgeTaxonomyService::class)->sanitizeHtml($payload['content'] ?? '');
        foreach ($payload['grammar_rules'] ?? [] as $key => $rule) {
            $payload['grammar_rules'][$key]['explanation'] = app(KnowledgeTaxonomyService::class)->sanitizeHtml($rule['explanation'] ?? '');
        }

        return compact('summary', 'payload');
    }
}
