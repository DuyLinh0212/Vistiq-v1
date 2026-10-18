@php
    $filters = $metrics['filters'] ?? [];
    $hasFilters = !empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['exam_id']) || (!empty($filters['mode']) && $filters['mode'] !== 'all');
@endphp

{{-- Bộ lọc đặt ngay đầu vùng chức năng theo AGENTS.md --}}
<section class="panel" style="margin-bottom: 18px; padding: 14px 18px;">
    <form method="get" action="{{ route('admin.analytics') }}" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
        <div>
            <label style="font-size: 11px; font-weight: 600; color: #506482; display: block; margin-bottom: 4px;">Từ ngày</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" style="height: 38px; padding: 0 10px; border: 1px solid var(--line); border-radius: 8px; font: inherit;">
        </div>

        <div>
            <label style="font-size: 11px; font-weight: 600; color: #506482; display: block; margin-bottom: 4px;">Đến ngày</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" style="height: 38px; padding: 0 10px; border: 1px solid var(--line); border-radius: 8px; font: inherit;">
        </div>

        <div>
            <label style="font-size: 11px; font-weight: 600; color: #506482; display: block; margin-bottom: 4px;">Kỳ thi</label>
            <select name="exam_id" style="height: 38px; min-width: 180px; padding: 0 10px; border: 1px solid var(--line); border-radius: 8px; font: inherit;">
                <option value="">Tất cả kỳ thi</option>
                @foreach($metrics['exams'] ?? [] as $exam)
                    <option value="{{ $exam->id }}" @selected(($filters['exam_id'] ?? '') == $exam->id)>{{ $exam->title }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label style="font-size: 11px; font-weight: 600; color: #506482; display: block; margin-bottom: 4px;">Chế độ</label>
            <select name="mode" style="height: 38px; min-width: 150px; padding: 0 10px; border: 1px solid var(--line); border-radius: 8px; font: inherit;">
                <option value="all" @selected(($filters['mode'] ?? '') === 'all')>Tất cả chế độ</option>
                <option value="exam" @selected(($filters['mode'] ?? '') === 'exam')>Thi chính thức (Exam)</option>
                <option value="practice" @selected(($filters['mode'] ?? '') === 'practice')>Luyện tập (Practice)</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px; align-items: center;">
            <button class="button button-primary" type="submit" style="height: 38px;">Áp dụng lọc</button>
            @if($hasFilters)
                <a href="{{ route('admin.analytics') }}" class="button button-quiet" style="height: 38px; display: inline-flex; align-items: center;">Xóa bộ lọc</a>
            @endif
            @can('results.view')
                <a class="button button-quiet" href="{{ route('admin.analytics.export', request()->query()) }}" style="height: 38px; display: inline-flex; align-items: center;" title="Xuất báo cáo theo bộ lọc hiện tại">
                    Xuất CSV
                </a>
            @endcan
        </div>
    </form>
</section>

{{-- Dải KPI nhỏ theo ngữ cảnh câu hỏi --}}
<div class="assessment-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 18px;">
    <article class="panel" style="padding: 16px;">
        <p style="font-size: 11px; color: #72849e; margin: 0 0 4px;">Tổng lượt thi</p>
        <h2 style="font-size: 24px; margin: 0;">{{ number_format($metrics['attempts']) }}</h2>
        <small style="color: #92a0b3; font-size: 10px;">Có snapshot hợp lệ</small>
    </article>

    <article class="panel" style="padding: 16px;">
        <p style="font-size: 11px; color: #72849e; margin: 0 0 4px;">Đã nộp bài</p>
        <h2 style="font-size: 24px; margin: 0;">{{ number_format($metrics['completed']) }}</h2>
        <small style="color: #177a4a; font-size: 10px;">Hoàn thành bài thi</small>
    </article>

    <article class="panel" style="padding: 16px;">
        <p style="font-size: 11px; color: #72849e; margin: 0 0 4px;">Tỷ lệ hoàn tất</p>
        <h2 style="font-size: 24px; margin: 0; color: #2159d3;">{{ $metrics['completion_rate'] }}%</h2>
        <small style="color: #92a0b3; font-size: 10px;">completed / attempts</small>
    </article>

    <article class="panel" style="padding: 16px;">
        <p style="font-size: 11px; color: #72849e; margin: 0 0 4px;">Điểm trung bình</p>
        <h2 style="font-size: 24px; margin: 0;">{{ $metrics['avg_score'] ?? '—' }}</h2>
        <small style="color: #92a0b3; font-size: 10px;">Bài đã chấm xong</small>
    </article>

    <article class="panel" style="padding: 16px;">
        <p style="font-size: 11px; color: #72849e; margin: 0 0 4px;">Chưa nộp (Drop-off)</p>
        <h2 style="font-size: 24px; margin: 0; color: #c43838;">{{ $metrics['drop_off'] }}%</h2>
        <small style="color: #92a0b3; font-size: 10px;">Bỏ dở giữa chừng</small>
    </article>

    <article class="panel" style="padding: 16px;">
        <p style="font-size: 11px; color: #72849e; margin: 0 0 4px;">Người dùng hoạt động</p>
        <h2 style="font-size: 20px; margin: 0;">{{ $metrics['dau'] }} <span style="font-size: 13px; font-weight: normal; color: #72849e;">DAU</span></h2>
        <small style="color: #92a0b3; font-size: 10px;">WAU: {{ $metrics['wau'] }} · MAU: {{ $metrics['mau'] }}</small>
    </article>

    <article class="panel" style="padding: 16px;">
        <p style="font-size: 11px; color: #72849e; margin: 0 0 4px;">Retention 7 ngày</p>
        <h2 style="font-size: 24px; margin: 0;">{{ $metrics['retention'] }}%</h2>
        <small style="color: #92a0b3; font-size: 10px;">Quay lại sau 1 tuần</small>
    </article>
</div>

{{-- Biểu đồ xu hướng và bảng thay thế --}}
<section class="panel" style="margin-bottom: 18px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
        <h2 style="font-size: 16px; margin: 0;">Xu hướng hoạt động 14 ngày</h2>
        <span style="font-size: 11px; color: #8191a7;">Cập nhật: {{ $metrics['updated_at'] }} ({{ $metrics['timezone'] }})</span>
    </div>

    @if(count($metrics['chart'] ?? []) > 0)
        <div class="assessment-chart" role="img" aria-label="Số lượt thi và người dùng hoạt động trong 14 ngày">
            @foreach($metrics['chart'] as $point)
                <div>
                    <div class="assessment-bar" style="height:{{ max(4, min(180, $point['attempts'] * 8)) }}px" title="{{ $point['date'] }}: {{ $point['attempts'] }} lượt thi"></div>
                    <small>{{ substr($point['date'], 5) }}</small>
                    <p>{{ $point['attempts'] }} lượt · {{ $point['active'] }} người</p>
                </div>
            @endforeach
        </div>
    @else
        <p style="color: #8291a8; text-align: center; padding: 20px;">Không có dữ liệu trong khoảng thời gian này.</p>
    @endif
</section>

{{-- Bảng breakdown độ khó và thời gian theo câu hỏi --}}
<section class="panel" style="margin-bottom: 18px;">
    <h2 style="font-size: 16px; margin-bottom: 14px;">Độ chính xác và thời gian trung bình theo câu hỏi</h2>
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Mã câu</th>
                    <th>Số lượt trả lời</th>
                    <th>Tỷ lệ đúng (Accuracy)</th>
                    <th>Thời gian trung bình (giây)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($metrics['difficulty'] as $row)
                    <tr>
                        <td><strong>{{ $row->item_key }}</strong></td>
                        <td>{{ number_format($row->responses) }}</td>
                        <td>
                            <span style="color: {{ $row->accuracy < 0.4 ? '#c43838' : ($row->accuracy > 0.75 ? '#177a4a' : '#253d60') }}; font-weight: 600;">
                                {{ round($row->accuracy * 100, 1) }}%
                            </span>
                        </td>
                        <td>{{ round($row->seconds, 1) }}s</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: #8391a6; padding: 24px;">Chưa có dữ liệu bài thi được chấm hoàn tất cho bộ lọc hiện tại.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{-- Phần chú giải chỉ số và tính minh bạch dữ liệu --}}
<footer class="panel" style="background: #fbfcfe; border-color: #e2eaf4; padding: 14px 18px; font-size: 11px; color: #72849e;">
    <p style="margin: 0 0 6px;"><strong>Chú giải & Minh bạch dữ liệu:</strong></p>
    <ul style="margin: 0; padding-left: 18px; line-height: 1.6;">
        <li><strong>Tỷ lệ hoàn tất:</strong> Tính bằng <code>(Đã nộp bài / Tổng lượt thi) × 100</code>.</li>
        <li><strong>Điểm trung bình:</strong> Điểm quy đổi (scaled score) trung bình của các bài thi đã hoàn tất chấm điểm.</li>
        <li><strong>Phạm vi loại trừ:</strong> Tự động loại trừ các bài thi bị hủy (status = 'cancelled') và lượt thi không có snapshot.</li>
        <li><strong>Timezone:</strong> Báo cáo được tính toán và đồng bộ theo múi giờ hệ thống <code>{{ $metrics['timezone'] }}</code>.</li>
    </ul>
</footer>
