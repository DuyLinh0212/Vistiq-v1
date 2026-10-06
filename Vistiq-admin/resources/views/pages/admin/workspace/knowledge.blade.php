@php
    $typeLabels = [
        'vocabulary' => 'Từ vựng',
        'grammar' => 'Ngữ pháp',
        'collocation' => 'Collocation',
        'phrasal_verb' => 'Cụm động từ',
        'idiom' => 'Thành ngữ',
    ];
    $statusLabels = ['draft' => 'Bản nháp', 'review' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'published' => 'Đã xuất bản', 'archived' => 'Đã lưu trữ'];
    $typesById = $types->keyBy('id');
@endphp

<section class="knowledge-library">
    <nav class="knowledge-tabs" aria-label="Nhóm kiến thức">
        <a href="{{ route('admin.knowledge.index', array_filter(['q' => $search])) }}" @class(['is-active' => $selectedType === ''])>Tất cả <span>{{ $typeCounts->sum() }}</span></a>
        @foreach($types as $type)
            <a href="{{ route('admin.knowledge.index', array_filter(['type' => $type->code, 'q' => $search])) }}" @class(['is-active' => $selectedType === $type->code])>
                {{ $typeLabels[$type->code] ?? $type->label }}
                <span>{{ $typeCounts[$type->id] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    <div class="knowledge-toolbar">
        <form method="get" action="{{ route('admin.knowledge.index') }}" class="knowledge-search">
            @if($selectedType !== '')<input type="hidden" name="type" value="{{ $selectedType }}">@endif
            <label>
                <span class="sr-only">Tìm theo từ hoặc chủ điểm</span>
                <input type="search" name="q" value="{{ $search }}" placeholder="Tìm từ, cụm từ hoặc chủ điểm">
            </label>
            <button class="button button-quiet" type="submit">Tìm kiếm</button>
            @if($search !== '')<a href="{{ route('admin.knowledge.index', array_filter(['type' => $selectedType])) }}">Xóa tìm kiếm</a>@endif
        </form>
        @can('content.edit')
            <a class="button button-primary" href="{{ route('admin.knowledge.create', array_filter(['type' => $selectedType])) }}">+ Thêm kiến thức</a>
        @endcan
    </div>

    <section class="panel knowledge-table-panel">
        <div class="knowledge-table-scroll">
            <table class="knowledge-table">
                <thead><tr><th>Kiến thức</th><th>Nhóm</th><th>Cấp độ</th><th>Trạng thái</th><th>Cập nhật</th><th><span class="sr-only">Thao tác</span></th></tr></thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $metadata = $item->metadata ?? [];
                            $typeCode = $typesById[$item->learning_item_type_id]->code ?? null;
                            $levelLabel = $levelsById[$item->proficiency_level_id] ?? null;
                        @endphp
                        <tr>
                            <td><a class="knowledge-item-link" href="{{ route('admin.knowledge.edit', $item) }}"><strong>{{ $item->canonical_text }}</strong>@if(filled($metadata['meaning_vi'] ?? null))<small>{{ $metadata['meaning_vi'] }}</small>@elseif(filled($metadata['summary'] ?? null))<small>{{ $metadata['summary'] }}</small>@endif</a></td>
                            <td><span class="knowledge-type-pill">{{ $typeLabels[$typeCode] ?? 'Khác' }}</span></td>
                            <td>{{ $levelLabel ?: '—' }}</td>
                            <td><span class="knowledge-status knowledge-status-{{ $item->status }}">{{ $statusLabels[$item->status] ?? $item->status }}</span></td>
                            <td>{{ $item->updated_at?->format('d/m/Y') ?? '—' }}</td>
                            <td><a class="knowledge-edit-link" href="{{ route('admin.knowledge.edit', $item) }}">Chỉnh sửa</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="knowledge-empty">{{ $search !== '' ? 'Không tìm thấy kiến thức phù hợp.' : 'Chưa có kiến thức trong nhóm này.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </section>
</section>
