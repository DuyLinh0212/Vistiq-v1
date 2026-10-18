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
        <label>Kỳ thi<select name="exam_type"><option value="">Tất cả</option>@foreach($examTypes as $type)<option value="{{ $type->id }}" @selected((string)($filters['exam_type']??'')===(string)$type->id)>{{ $type->name }}</option>@endforeach</select></label>
        <label>Part<select name="part"><option value="">Tất cả</option>@foreach($parts as $part)<option value="{{ $part->id }}" @selected((string)($filters['part']??'')===(string)$part->id)>{{ $part->exam_type_name }} · {{ $part->name }}</option>@endforeach</select></label>
        <label>Từ ngày<input name="from" type="date" value="{{ $filters['from']??'' }}"></label>
        <label>Đến ngày<input name="to" type="date" value="{{ $filters['to']??'' }}"></label>
        <button class="button button-primary">Lọc hàng đợi</button>
    </form>
    <div class="table-responsive">
        <table class="admin-table"><caption>Revision đang chờ duyệt</caption>
            <thead><tr><th>Loại</th><th>Nội dung</th><th>Người gửi</th><th>Revision</th><th>Thời điểm cập nhật</th><th>Kiểm tra nội dung</th><th>Thao tác</th></tr></thead>
            <tbody>@forelse($entries as $entry)<tr>
                <td>{{ $entry->kind==='exam'?'Đề thi':'Câu hỏi' }}</td>
                <td>#{{ $entry->id }} · {{ \Illuminate\Support\Str::limit(strip_tags($entry->title), 120) }}</td>
                <td>{{ $entry->author_name??'Không rõ' }}</td><td>{{ $entry->revision }}</td><td>{{ $entry->submitted_at }}</td>
                <td>@if($entry->blockers)<details><summary>{{ count($entry->blockers) }} lỗi cần sửa</summary><ul>@foreach($entry->blockers as $blocker)<li>{{ $blocker }}</li>@endforeach</ul></details>@else Sẵn sàng duyệt @endif</td>
                <td><a href="{{ route('admin.content-approval.show',[$entry->kind,$entry->id]) }}">Mở duyệt revision {{ $entry->revision }}</a></td>
            </tr>@empty<tr><td colspan="7">Không có revision chờ duyệt phù hợp. Biên tập viên gửi duyệt từ module nội dung hoặc đề thi.</td></tr>@endforelse</tbody>
        </table>
    </div>
    {{ $entries->links() }}
</section>
