@if(session('status'))
    <div class="panel" role="status" style="margin-bottom: 16px; padding: 12px 16px; color: #155724; background-color: #d4edda; border-color: #c3e6cb; border-radius: 8px;">
        {{ session('status') }}
    </div>
@endif

<section class="panel" style="margin-bottom: 18px; padding: 14px 18px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 12px;">
        <form method="get" action="{{ route('admin.leaderboard.index') }}" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
            <div>
                <label style="font-size: 11px; font-weight: 600; color: #506482; display: block; margin-bottom: 4px;">Kỳ thi</label>
                <select name="exam_id" onchange="this.form.submit()" style="height: 38px; min-width: 220px; padding: 0 10px; border: 1px solid var(--line); border-radius: 8px; font: inherit;">
                    <option value="">Tất cả kỳ thi</option>
                    @foreach($exams as $exam)
                        <option value="{{ $exam->id }}" @selected(($selectedExam->id ?? request('exam_id')) == $exam->id)>{{ $exam->title }}</option>
                    @endforeach
                </select>
            </div>
            @if(request('exam_id'))
                <a href="{{ route('admin.leaderboard.index') }}" class="button button-quiet" style="height: 38px; display: inline-flex; align-items: center;">Xem tất cả</a>
            @endif
        </form>

        @can('results.view')
            <div>
                <a class="button button-quiet" href="{{ route('admin.leaderboard.export', request()->query()) }}" style="height: 38px; display: inline-flex; align-items: center;">
                    Xuất CSV bảng xếp hạng
                </a>
            </div>
        @endcan
    </div>
</section>

<section class="panel" style="margin-bottom: 18px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h2 style="font-size: 16px; margin: 0;">Bảng xếp hạng</h2>
        <small style="color: #8292a8;">Quy tắc tie-breaker: Điểm cao nhất → Thời gian làm ngắn nhất → Nộp bài sớm hơn</small>
    </div>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Hạng</th>
                    <th>Học viên</th>
                    <th>Đề thi</th>
                    <th>Điểm</th>
                    <th>Thời gian làm bài</th>
                    <th>Thời điểm nộp</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rankings as $row)
                    <tr>
                        <td><strong>#{{ $row->rank_position }}</strong></td>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->title }}</td>
                        <td><span class="badge badge-blue" style="font-size: 13px; font-weight: 700;">{{ $row->scaled_score }}</span></td>
                        <td>{{ $row->seconds }}s ({{ gmdate("i:s", (int)$row->seconds) }})</td>
                        <td>{{ $row->submitted_at ? \Carbon\Carbon::parse($row->submitted_at)->format('d/m/Y H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #8292a8; padding: 24px;">Chưa có kết quả đủ điều kiện xếp hạng trong kỳ này.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($rankings, 'links'))
        <div style="margin-top: 14px;">
            {{ $rankings->links() }}
        </div>
    @endif
</section>

{{-- Action Reset tách biệt, yêu cầu xác nhận rõ ràng theo AGENTS.md --}}
@can('content.publish')
    <section class="panel" style="border-color: #eed6d6; background: #fffcfc;">
        <h2 style="font-size: 15px; margin: 0 0 6px; color: #8d2f2f;">Mở kỳ xếp hạng mới (Reset)</h2>
        <p style="font-size: 11px; color: #728094; margin: 0 0 14px;">Thao tác này sẽ thiết lập mốc thời gian bắt đầu kỳ xếp hạng mới cho đề thi. Toàn bộ lịch sử bài làm và điểm số của học viên vẫn được lưu trữ đầy đủ trong hệ thống.</p>
        
        <div style="display: flex; flex-direction: column; gap: 8px;">
            @foreach($exams as $exam)
                <form method="post" action="{{ route('admin.leaderboard.reset', $exam) }}" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; border: 1px solid #f0e1e1; border-radius: 8px; background: #fff;" data-confirm="Mở kỳ xếp hạng mới cho {{ $exam->title }}? Kết quả trước mốc mới sẽ được ẩn khỏi kỳ này; lịch sử và điểm được giữ." data-confirm-label="Mở kỳ xếp hạng mới">
                    @csrf
                    <div>
                        <strong style="font-size: 13px;">{{ $exam->title }}</strong>
                        @if($exam->leaderboard_reset_at)
                            <small style="display: block; color: #8292a8;">Kỳ gần nhất mở lúc: {{ \Carbon\Carbon::parse($exam->leaderboard_reset_at)->format('d/m/Y H:i') }}</small>
                        @endif
                    </div>
                    <button class="button button-quiet" type="submit" style="color: #9c2e2e; border-color: #f0c3c3;">Mở kỳ mới</button>
                </form>
            @endforeach
        </div>
    </section>
@endcan
