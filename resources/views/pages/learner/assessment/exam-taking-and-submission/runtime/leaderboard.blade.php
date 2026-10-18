@extends('layouts.app')
@php($page=['title'=>'Bảng xếp hạng · '.$exam->title,'section'=>'exams'])
@section('content')
<main class="assessment-page">
    <nav class="assessment-actions" aria-label="Điều hướng đề thi"><a href="{{ route('exams.index') }}">Kho đề thi</a><a href="{{ route('attempts.history') }}">Lượt làm của tôi</a></nav>
    <h1>Bảng xếp hạng · {{ $exam->title }}</h1>
    <section class="panel">
        <p>Mỗi người một lượt tốt nhất. Điểm cao hơn xếp trước; bằng điểm thì thời gian ngắn hơn xếp trước. Cùng điểm và thời gian được đồng hạng.</p>
        <div class="table-responsive"><table class="admin-table"><caption>Kết quả đã chấm xong và được phép công bố</caption>
            <thead><tr><th scope="col">Hạng</th><th scope="col">Tên</th><th scope="col">Điểm</th><th scope="col">Thời gian làm</th></tr></thead>
            <tbody>@forelse($rankings as $row)<tr @if($row['is_current_user']) aria-label="Lượt tốt nhất của bạn" @endif>
                <td>{{ $row['rank'] }}</td><td>{{ $row['name'] }} @if($row['is_current_user'])<strong>· Bạn</strong>@endif</td><td>{{ $row['score'] }}</td><td>{{ intdiv($row['seconds'],60) }} phút {{ $row['seconds']%60 }} giây</td>
            </tr>@empty<tr><td colspan="4">Chưa có lượt làm đủ điều kiện công bố.</td></tr>@endforelse</tbody>
        </table></div>{{ $rankings->links() }}
    </section>
</main>
@endsection
