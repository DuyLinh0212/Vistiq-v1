@php($labels=['in_progress'=>'Đang làm','paused'=>'Tạm dừng','expired'=>'Hết giờ, chờ nộp','submitted'=>'Đã nộp','pending_grading'=>'Chờ chấm','completed'=>'Hoàn tất'])
<h1>Lượt làm bài</h1>
<section class="panel">
<form method="get" class="admin-form-grid">
    <label>ID người học<input name="user" type="number" min="1" value="{{ $filters['user']??'' }}"></label>
    <label>ID đề<input name="exam" type="number" min="1" value="{{ $filters['exam']??'' }}"></label>
    <label>Trạng thái<select name="status"><option value="">Tất cả</option>@foreach($labels as $value=>$label)<option value="{{ $value }}" @selected(($filters['status']??'')===$value)>{{ $label }}</option>@endforeach</select></label>
    <label>Từ ngày<input type="date" name="from" value="{{ $filters['from']??'' }}"></label><label>Đến ngày<input type="date" name="to" value="{{ $filters['to']??'' }}"></label>
    <label>Điểm tối thiểu<input type="number" name="min_score" min="0" step="any" value="{{ $filters['min_score']??'' }}"></label><label>Điểm tối đa<input type="number" name="max_score" min="0" step="any" value="{{ $filters['max_score']??'' }}"></label>
    <label>Báo cáo<select name="reports"><option value="">Tất cả</option>@foreach(['pending'=>'Chờ xử lý','any'=>'Có báo cáo','none'=>'Không có báo cáo'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['reports']??'')===$value)>{{ $label }}</option>@endforeach</select></label>
    <button class="button button-primary">Lọc lượt thi</button><a class="button button-quiet" href="{{ route('admin.attempts.index') }}">Xóa bộ lọc</a>
</form>
<div class="table-responsive"><table class="admin-table"><caption>Lượt làm đề · Mode Exam</caption><thead><tr><th>Lượt</th><th>Học viên</th><th>Nguồn đề</th><th>Trạng thái</th><th>Điểm</th><th>Bắt đầu / Nộp bài</th></tr></thead><tbody>
@forelse($attempts as $attempt)<tr><td><a href="{{ route('admin.attempts.show',$attempt) }}">#{{ $attempt->id }}</a></td><td>{{ $attempt->user?->name??'Người học' }}</td><td>{{ $attempt->snapshot['title']??$attempt->exam?->title??'Đề cũ' }}</td><td>{{ $labels[$attempt->status]??$attempt->status }}</td><td>{{ $attempt->scaled_score??'Chưa có điểm cuối' }}</td><td>{{ $attempt->started_at->format('d/m/Y H:i') }}<br>{{ $attempt->submitted_at?->format('d/m/Y H:i')??'Chưa nộp' }}</td></tr>@empty<tr><td colspan="6">Chưa có lượt thi phù hợp với bộ lọc.</td></tr>@endforelse
</tbody></table></div>{{ $attempts->links() }}
</section>
