<h1>Duyệt nội dung</h1>
<section class="panel">
    <nav class="assessment-actions" aria-label="Loại nội dung">
        @foreach([''=>'Tất cả','question'=>'Câu hỏi','exam'=>'Đề thi'] as $kind=>$label)
            <a class="button button-quiet" href="{{ route('admin.content-approval.index', array_merge(request()->except('page'), ['kind'=>$kind])) }}" @if(($filters['kind']??'')===$kind) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <form method="get" class="admin-form-grid">
        <input type="hidden" name="kind" value="{{ $filters['kind']??'' }}">
        <label>Tìm nội dung<input name="q" maxlength="255" value="{{ $filters['q']??'' }}"></label>
        <label>ID người gửi<input name="author" type="number" min="1" value="{{ $filters['author']??'' }}"></label>
        <label>Revision<input name="revision" type="number" min="1" value="{{ $filters['revision']??'' }}"></label>
        <label>Từ ngày<input name="from" type="date" value="{{ $filters['from']??'' }}"></label>
        <label>Đến ngày<input name="to" type="date" value="{{ $filters['to']??'' }}"></label>
        <button class="button button-primary">Lọc hàng đợi</button>
    </form>
    <div class="table-responsive">
        <table class="admin-table"><caption>Revision đang chờ duyệt</caption>
            <thead><tr><th>Loại</th><th>Nội dung</th><th>Người gửi</th><th>Revision</th><th>Thời điểm cập nhật</th><th>Thao tác</th></tr></thead>
            <tbody>@forelse($entries as $entry)<tr>
                <td>{{ $entry->kind==='exam'?'Đề thi':'Câu hỏi' }}</td>
                <td>#{{ $entry->id }} · {{ \Illuminate\Support\Str::limit(strip_tags($entry->title), 120) }}</td>
                <td>{{ $entry->author_name??'Không rõ' }}</td><td>{{ $entry->revision }}</td><td>{{ $entry->submitted_at }}</td>
                <td><a href="{{ route('admin.content-approval.show',[$entry->kind,$entry->id]) }}">Mở duyệt revision {{ $entry->revision }}</a></td>
            </tr>@empty<tr><td colspan="6">Không có revision chờ duyệt phù hợp. Biên tập viên gửi duyệt từ module nội dung hoặc đề thi.</td></tr>@endforelse</tbody>
        </table>
    </div>
    {{ $entries->links() }}
</section>
