<?php

namespace VistiqLearner\Http\Controllers\Knowledge;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use VistiqLearner\Http\Controllers\StaticPageController;
use VistiqLearner\Http\Requests\Knowledge\KnowledgeFilterRequest;

class KnowledgeController extends StaticPageController
{
    private const SCREENS = [
        'knowledge' => ['title' => 'Kho kiến thức cá nhân', 'frame' => 'U06', 'view' => 'pages.learner.knowledge.saved.index', 'section' => 'knowledge', 'description' => 'Lưu lại những điều quan trọng trong quá trình học.', 'show_heading' => false],
        'knowledge-list' => ['title' => 'Danh sách mục đã lưu', 'frame' => 'U35', 'view' => 'pages.learner.knowledge.saved.list', 'section' => 'knowledge', 'description' => 'Tìm, phân loại và xem lại kiến thức của bạn.', 'show_heading' => false],
        'knowledge-review' => ['title' => 'Ôn tập kho kiến thức', 'frame' => 'U36', 'view' => 'pages.learner.knowledge.saved.review', 'section' => 'knowledge', 'description' => 'Ôn lại mục đã lưu theo tiến độ học tập.', 'show_heading' => false],
        'saved-detail' => ['title' => 'take into account', 'frame' => 'U24', 'view' => 'pages.learner.knowledge.saved.show', 'section' => 'knowledge', 'description' => 'Cân nhắc, tính đến khi đưa ra quyết định.', 'show_heading' => false],
    ];

    public function show(string $screen): View
    {
        return $this->renderKnownScreen($screen, self::SCREENS);
    }

    public function index(KnowledgeFilterRequest $request): View
    {
        $type = $request->validated('type') ?? 'all';
        $q = $request->validated('q');
        $level = $request->validated('level');

        $query = DB::table('learning_items')
            ->join('learning_item_types as item_type', 'item_type.id', '=', 'learning_items.learning_item_type_id')
            ->leftJoin('proficiency_levels as level', 'level.id', '=', 'learning_items.proficiency_level_id')
            ->where('learning_items.status', 'published')
            ->where(fn ($sub) => $sub->whereNull('learning_items.published_at')->orWhere('learning_items.published_at', '<=', now()))
            ->where('item_type.code', '!=', 'question')
            ->when($type && $type !== 'all', fn ($builder) => $builder->where('item_type.code', $type))
            ->when($q, fn ($builder) => $builder->where('learning_items.canonical_text', 'like', "%{$q}%"))
            ->when($level, fn ($builder) => $builder->where('level.code', $level))
            ->select([
                'learning_items.id',
                'learning_items.canonical_text',
                'learning_items.slug',
                'item_type.code as type_code',
                'item_type.label as type_label',
                'level.code as level_code',
                'level.label as level_label',
            ])
            ->orderBy('learning_items.canonical_text');

        $items = $query->paginate(15)->withQueryString();
        $types = DB::table('learning_item_types')->where('code', '!=', 'question')->orderBy('sort_order')->get();
        $levels = DB::table('proficiency_levels')->orderBy('sort_order')->get();

        $page = [
            'title' => 'Kho kiến thức',
            'view' => 'pages.learner.knowledge.saved.index',
            'section' => 'knowledge',
            'shell' => 'user',
            'slug' => 'knowledge',
            'description' => 'Khám phá và lưu lại những điều quan trọng trong quá trình học.',
            'show_heading' => false,
        ];

        return view('shared.demo-page', [
            'page' => $page,
            'items' => $items,
            'types' => $types,
            'levels' => $levels,
            'filters' => [
                'type' => $type,
                'q' => $q,
                'level' => $level,
            ],
        ]);
    }

    public function savedList(KnowledgeFilterRequest $request): View
    {
        return $this->show('knowledge-list');
    }

    public function detail(string $slug): View
    {
        $item = DB::table('learning_items')
            ->join('learning_item_types as item_type', 'item_type.id', '=', 'learning_items.learning_item_type_id')
            ->leftJoin('proficiency_levels as level', 'level.id', '=', 'learning_items.proficiency_level_id')
            ->where('learning_items.status', 'published')
            ->where(fn ($sub) => $sub->whereNull('learning_items.published_at')->orWhere('learning_items.published_at', '<=', now()))
            ->where('learning_items.slug', $slug)
            ->select([
                'learning_items.id',
                'learning_items.canonical_text',
                'learning_items.slug',
                'item_type.code as type_code',
                'item_type.label as type_label',
                'level.code as level_code',
                'level.label as level_label',
            ])
            ->first();

        if (! $item) {
            if (isset(self::SCREENS[$slug])) {
                return $this->show($slug);
            }
            abort(404, 'Mục kiến thức không tồn tại hoặc chưa được xuất bản.');
        }

        $page = [
            'title' => $item->canonical_text,
            'view' => 'pages.learner.knowledge.saved.show',
            'section' => 'knowledge',
            'shell' => 'user',
            'slug' => $slug,
            'description' => 'Chi tiết kiến thức.',
            'show_heading' => false,
        ];

        return view('shared.demo-page', [
            'page' => $page,
            'item' => $item,
        ]);
    }
}
