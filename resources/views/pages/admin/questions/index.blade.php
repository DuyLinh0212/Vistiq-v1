@php
    $statusLabels = ['published' => 'Đã xuất bản', 'draft' => 'Bản nháp', 'review' => 'Chờ duyệt', 'approved'=>'Đã duyệt', 'archived'=>'Đã lưu trữ'];
@endphp

@if ($errors->any())
    <div class="admin-alert admin-alert-error">
        <b>Không thể import CSV.</b>
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

@if (session('import_summary'))
    @php
        $importSummary = session('import_summary');
        $importHasErrors = $importSummary['failed'] > 0;
    @endphp
    <div class="admin-toast {{ $importHasErrors ? 'admin-toast-warning' : '' }}" role="{{ $importHasErrors ? 'alert' : 'status' }}" aria-live="polite" data-admin-toast data-admin-toast-timeout="{{ $importHasErrors ? 0 : 6000 }}">
        <span>Đã xử lý {{ number_format($importSummary['total']) }} dòng: <b>{{ number_format($importSummary['imported']) }} câu hỏi thành công</b>, {{ number_format($importSummary['failed']) }} dòng lỗi.</span>
        @if (session('import_errors'))
            <details class="import-errors"><summary>Xem chi tiết dòng lỗi</summary><ul>@foreach (session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul></details>
        @endif
        <button type="button" data-dismiss-admin-toast aria-label="Đóng thông báo">×</button>
    </div>
@endif

<div class="admin-tabs tab-row">
    <a class="{{ $filters['exam'] === '' ? 'is-active' : '' }}" href="{{ route('admin.questions.index', request()->except('exam', 'page')) }}">Tất cả <span>{{ number_format($stats['total']) }}</span></a>
    @foreach ($examTypes as $examType)
        <a class="{{ $filters['exam'] === $examType->code ? 'is-active' : '' }}" href="{{ route('admin.questions.index', array_merge(request()->except('page'), ['exam' => $examType->code])) }}">{{ $examType->name }} <span>{{ number_format($examCounts[$examType->code] ?? 0) }}</span></a>
    @endforeach
</div>

<div class="question-stat-grid">
    <article class="admin-stat-card"><span class="category-icon blue">@include('pages.dictionary.icon', ['name' => 'grammar'])</span><small>Tổng câu hỏi</small><b>{{ number_format($stats['total']) }}</b><span>Trong ngân hàng</span></article>
    <article class="admin-stat-card"><span class="category-icon mint">@include('pages.dictionary.icon', ['name' => 'check'])</span><small>Đã xuất bản</small><b>{{ number_format($stats['published']) }}</b><span>Sẵn sàng cho học viên</span></article>
    <article class="admin-stat-card"><span class="category-icon violet">@include('pages.dictionary.icon', ['name' => 'book'])</span><small>Bài đọc IELTS</small><b>{{ number_format($stats['readings']) }}</b><span><a class="text-link" href="{{ route('admin.readings.index') }}">Mở kho bài đọc</a></span></article>
    <article class="admin-stat-card"><span class="category-icon coral">@include('pages.dictionary.icon', ['name' => 'listening'])</span><small>Audio IELTS</small><b>{{ number_format($stats['audio']) }}</b><span><a class="text-link" href="{{ route('admin.audio.index') }}">Mở kho audio</a></span></article>
</div>

<form class="admin-toolbar" method="get" action="{{ route('admin.questions.index') }}" data-question-search>
    <div class="search-bar"><span>@include('pages.dictionary.icon', ['name' => 'search'])</span><input name="q" value="{{ $filters['search'] }}" placeholder="Tìm nội dung hoặc mã câu hỏi (Q-00001)..." aria-label="Tìm câu hỏi"></div>
    @foreach (['exam', 'part', 'type', 'difficulty', 'status', 'missing'] as $filterName)
        @if ($filters[$filterName] !== '')<input type="hidden" name="{{ $filterName }}" value="{{ $filters[$filterName] }}">@endif
    @endforeach
    <button class="button button-quiet" type="submit">@include('pages.dictionary.icon', ['name' => 'search']) Tìm kiếm</button>
    <a class="button button-quiet" href="{{ route('admin.questions.index') }}">Đặt lại</a>
    <a class="button button-primary" href="{{ route('admin.questions.create') }}">@include('pages.dictionary.icon', ['name' => 'plus']) Tạo câu hỏi</a>
</form>

<details class="csv-import">
    <summary class="button button-quiet">@include('pages.dictionary.icon', ['name' => 'collection']) Import câu hỏi CSV</summary>
    <form class="panel csv-import-form" method="post" action="{{ route('admin.questions.import') }}" enctype="multipart/form-data" data-question-import data-progress-url="{{ route('admin.questions.import-progress', ['token' => 'IMPORT_TOKEN']) }}">
        @csrf
        <input type="hidden" name="import_key" value="">
        <input type="hidden" name="offset" value="0">
        <div class="section-head"><div><span class="eyebrow">NẠP HÀNG LOẠT</span><h3>Import câu hỏi và nhóm audio Listening</h3><p class="form-help">Bộ mẫu có 41 câu minh họa, đủ 20 dạng câu hỏi. Sửa nội dung và xóa dòng không dùng trước khi import.</p></div></div>
        <div class="csv-template-downloads" aria-label="Tải mẫu câu hỏi CSV">
            <a class="button button-quiet" href="{{ route('admin.questions.import-template', ['template' => 'all']) }}">Mẫu tổng hợp CSV</a>
            <a class="button button-quiet" href="{{ route('admin.questions.import-template', ['template' => 'toeic']) }}">Mẫu TOEIC · 7 Part</a>
            <a class="button button-quiet" href="{{ route('admin.questions.import-template', ['template' => 'ielts']) }}">Mẫu IELTS · 4 kỹ năng</a>
            <a class="button button-quiet" href="{{ route('admin.questions.import-template', ['template' => 'listening-groups']) }}">Mẫu nhóm audio Listening</a>
            <a class="button button-quiet" href="{{ route('admin.questions.import-template', ['template' => 'bundle']) }}">Tải bộ mẫu ZIP</a>
            <a class="text-link" href="{{ route('admin.questions.import-template', ['template' => 'guide']) }}">Tải hướng dẫn</a>
        </div>
        <label class="admin-label" for="question-csv-file">File CSV
            <input class="admin-input" id="question-csv-file" type="file" name="file" accept=".csv,.txt,text/csv" required>
            <span class="form-help">Bắt buộc có cột <code>exam_part</code> và <code>prompt</code>. Có thể dùng mã như <code>part-5</code>, <code>multiple_choice</code>, <code>medium</code>.</span>
        </label>
        <div class="admin-form-grid">
            <label>Loại câu hỏi mặc định
                <select name="default_question_type" required>
                    @foreach ($questionTypes as $questionType)<option value="{{ $questionType->id }}" @selected(old('default_question_type', $questionTypes->first()?->id) == $questionType->id)>{{ $questionType->name }}</option>@endforeach
                </select>
            </label>
            <label>Độ khó mặc định
                <select name="default_difficulty" required>
                    @foreach ($difficulties as $difficulty)<option value="{{ $difficulty->id }}" @selected(old('default_difficulty', $difficulties->first()?->id) == $difficulty->id)>{{ $difficulty->name }}</option>@endforeach
                </select>
            </label>
            <label>Trạng thái sau khi import
                <select name="default_status" required>
                    <option value="draft">Bản nháp · rà soát trước khi xuất bản</option>
                </select>
            </label>
        </div>
        <p class="form-help">Trắc nghiệm đơn dùng <code>option_a</code> đến <code>option_d</code> và <code>correct_option</code>. Điền đáp án dùng <code>accepted_answers</code>, phân tách biến thể bằng <code>|</code>. Matching, nhiều đáp án, nhiều ô trống và rubric Writing/Speaking dùng <code>payload_json</code>. Chỉ ô loại câu hỏi hoặc độ khó bỏ trống mới dùng giá trị mặc định.</p>
        <p class="form-help">Để gom các câu cùng bài nghe, lặp cùng mã ở <code>audio_group</code> trên các dòng. CSV chỉ tạo nhóm câu hỏi, không chứa audio. Sau khi import, <a href="{{ route('admin.audio.index') }}">tải audio riêng</a> rồi <a href="{{ route('admin.groups.index') }}">gắn audio vào nhóm</a>. File cũ thiếu <code>audio_group</code> vẫn có thể dùng mã <code>Audio script ID: CODE</code> trong <code>sample_note</code> để gom câu.</p>
        @include('pages.admin.questions.csv-guide')
        <div class="csv-import-actions"><span class="form-help">Tối đa 2.000 dòng / 10 MB mỗi lần. Những dòng hợp lệ được lưu dù có dòng khác bị lỗi.</span><button class="button button-primary" type="submit">Import câu hỏi</button></div>
        <div class="import-progress" data-import-progress hidden aria-live="polite">
            <div class="import-progress-heading"><b data-import-progress-label>Đang tải file…</b><span data-import-progress-count>0%</span></div>
            <progress max="100" value="0" data-import-progress-bar aria-label="Tiến độ import câu hỏi"></progress>
        </div>
        <div class="admin-alert admin-alert-error" data-import-feedback hidden role="alert"></div>
    </form>
</details>

<form class="admin-filter-row" method="get" action="{{ route('admin.questions.index') }}" data-question-filters>
    <input type="hidden" name="q" value="{{ $filters['search'] }}">
    <label class="sr-only" for="filter-exam">Kỳ thi</label>
    <select id="filter-exam" name="exam"><option value="">Tất cả kỳ thi</option>@foreach ($examTypes as $examType)<option value="{{ $examType->code }}" @selected($filters['exam'] === $examType->code)>{{ $examType->name }}</option>@endforeach</select>
    <label class="sr-only" for="filter-part">Part</label>
    <select id="filter-part" name="part"><option value="">Tất cả Part / kỹ năng</option>@foreach ($parts as $partOption)<option value="{{ $partOption->code }}" @selected($filters['part'] === $partOption->code)>{{ $partOption->examType->name }} · {{ $partOption->name }}</option>@endforeach</select>
    <label class="sr-only" for="filter-type">Loại câu hỏi</label>
    <select id="filter-type" name="type"><option value="">Tất cả loại câu hỏi</option>@foreach ($filterQuestionTypes as $questionType)<option value="{{ $questionType->code }}" @selected($filters['type'] === $questionType->code)>{{ $questionType->name }}</option>@endforeach</select>
    <label class="sr-only" for="filter-difficulty">Độ khó</label>
    <select id="filter-difficulty" name="difficulty"><option value="">Tất cả độ khó</option>@foreach ($difficulties as $difficulty)<option value="{{ $difficulty->code }}" @selected($filters['difficulty'] === $difficulty->code)>{{ $difficulty->name }}</option>@endforeach</select>
    <label class="sr-only" for="filter-status">Trạng thái</label>
    <select id="filter-status" name="status"><option value="">Mọi trạng thái</option>@foreach ($statusLabels as $statusCode => $statusLabel)<option value="{{ $statusCode }}" @selected($filters['status'] === $statusCode)>{{ $statusLabel }}</option>@endforeach</select>
    <label class="sr-only" for="filter-missing">Thông tin câu hỏi</label>
    <select id="filter-missing" name="missing"><option value="">Mọi câu hỏi</option><option value="1" @selected($filters['missing'])>Thiếu tài nguyên / thông tin</option></select>
    <noscript><button class="button button-quiet" type="submit">Lọc</button></noscript>
    <script type="application/json" data-question-filter-catalog>{!! json_encode($filterCatalog, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
</form>

<form id="question-bulk" class="assessment-actions" method="post" action="{{ route('admin.questions.bulk') }}">@csrf
<script type="application/json" data-question-matching-ids>{!! json_encode($matchingQuestionIds) !!}</script><span data-question-selection-summary aria-live="polite"></span><input type="hidden" name="action" value="">
@can('content.edit')<button class="button button-danger" type="button" data-delete-selected-questions>Xóa câu đã chọn chưa dùng trong đề</button>@endcan<a class="button button-quiet" href="{{ route('admin.questions.export') }}">Export CSV</a><a href="{{ route('admin.questions.index') }}">Đặt lại bộ lọc</a></form>
<div class="import-progress bulk-delete-progress" data-delete-progress hidden aria-live="polite">
    <div class="import-progress-heading"><b data-delete-progress-label>Đang xóa câu hỏi…</b><span data-delete-progress-count>0%</span></div>
    <progress max="100" value="0" data-delete-progress-bar aria-label="Tiến độ xóa câu hỏi"></progress>
</div>
<section class="panel admin-table">
    <div class="admin-table-head"><span><input type="checkbox" data-select-all aria-label="Chọn toàn bộ câu phù hợp bộ lọc"></span><span>Mã câu hỏi / nội dung</span><span>Kỳ thi · dạng bài</span><span>Độ khó</span><span>Trạng thái</span><span>Cập nhật</span><span></span></div>
    @forelse ($questions as $question)
        @php
            $status = $question->learningItem?->status==='archived'?'archived':($question->draftRevision?->status ?? $question->learningItem?->status ?? 'draft');
            $statusTone = $status === 'published' ? 'badge-mint' : ($status === 'draft' ? 'badge-soft' : 'badge-amber');
        @endphp
        @php($missingResources = $missingResourcesByQuestion[$question->id] ?? [])
        <div class="admin-table-row {{ $missingResources !== [] ? 'question-row-incomplete' : '' }}">
            <span><input type="checkbox" form="question-bulk" name="ids[]" value="{{ $question->id }}" aria-label="Chọn câu hỏi {{ $question->id }}"></span>
            <span><b>Q-{{ str_pad((string) $question->id, 5, '0', STR_PAD_LEFT) }}</b><small>{{ \Illuminate\Support\Str::limit(strip_tags($question->draftRevision?->payload['prompt'] ?? $question->prompt), 105) }}</small>@if($missingResources !== [])<small class="question-missing-label">Thiếu: {{ implode(', ', $missingResources) }}</small>@endif</span>
            <span>{{ $question->examPart?->examType?->name ?? 'Chưa chọn' }} · {{ $question->examPart?->name ?? 'Chưa chọn Part' }}<small>{{ $question->questionType?->name }}</small></span>
            <span><span class="badge badge-blue">{{ $question->difficulty?->name ?? '—' }}</span></span>
            <span><span class="badge {{ $statusTone }}">{{ $statusLabels[$status] ?? $status }}</span></span>
            <span>{{ optional($question->updated_at)->format('d/m/Y') }}</span>
            <a href="{{ route('admin.questions.edit', $question) }}" aria-label="Chỉnh sửa câu hỏi Q-{{ $question->id }}">@include('pages.dictionary.icon', ['name' => 'edit'])</a>
        </div>
    @empty
        <div class="admin-empty"><span class="category-icon blue">@include('pages.dictionary.icon', ['name' => 'grammar'])</span><h3>Chưa có câu hỏi phù hợp</h3><p>Hãy tạo câu hỏi đầu tiên hoặc thay đổi bộ lọc.</p><a class="button button-primary" href="{{ route('admin.questions.create') }}">Tạo câu hỏi</a></div>
    @endforelse
    @if ($questions->hasPages())<div class="pagination">{{ $questions->onEachSide(1)->links() }}</div>@endif
</section>
