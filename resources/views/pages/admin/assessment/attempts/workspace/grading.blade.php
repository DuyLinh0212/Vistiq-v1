<h1>Chấm Writing / Speaking</h1>
@forelse($answers as $answer)@php($item=collect($answer->attempt->snapshot['items'])->firstWhere('key',$answer->item_key))
@php($keepInput=(string)old('grading_answer_id')===(string)$answer->id)
<article class="panel"><span class="badge badge-soft">{{ $answer->grade?'ĐÃ CHẤM':'CHỜ CHẤM' }}</span><h2>Lượt #{{ $answer->exam_attempt_id }} · {{ $answer->attempt->user->name }} · {{ $answer->item_key }}</h2><p>{{ $answer->attempt->snapshot['title']??'Đề cũ' }} · Revision đề #{{ $answer->attempt->exam_revision_id }}</p><div>{!! app(\VistiqCore\Services\Assessment\QuestionBank\QuestionRegistry::class)->sanitize($item['prompt']??'') !!}</div><p>{{ $answer->response['text']??'' }}</p>@if($answer->response['asset_id']??null)<audio controls aria-label="Recording câu {{ $answer->item_key }}" src="{{ route('admin.media.show',$answer->response['asset_id']) }}"></audio>@endif
<details><summary>Rubric đã ghim</summary><pre>{{ json_encode($item['metadata']['rubric']??[],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre><p>{{ $item['metadata']['model_answer']??'' }}</p></details>
<form method="post" action="{{ route('admin.grading.store',$answer) }}" class="admin-form-grid" @if($answer->grade) data-confirm="Chấm lại câu {{ $answer->item_key }} của lượt #{{ $answer->exam_attempt_id }}? Kết quả sẽ được cập nhật và ghi audit." data-confirm-label="Lưu điểm điều chỉnh" @endif>@csrf
<input type="hidden" name="grade_token" value="{{ hash('sha256',json_encode($answer->grade??[],JSON_THROW_ON_ERROR)) }}">
<input type="hidden" name="grading_answer_id" value="{{ $answer->id }}">
@foreach($gradingCriteria[$item['type']] as $criterion)<label>{{ $criterion }} (0–9, bước 0,5)<input type="number" step="0.5" min="0" max="9" required name="criteria[{{ $criterion }}]" value="{{ $keepInput?old('criteria.'.$criterion):($answer->grade['criteria'][$criterion]??'') }}"></label>@endforeach
<label class="admin-form-wide">Feedback<textarea name="feedback" required maxlength="20000">{{ $keepInput?old('feedback'):($answer->grade['feedback']??'') }}</textarea></label>
@if($answer->grade)<label>Lý do điều chỉnh (bắt buộc, tối đa 2.000 ký tự)<textarea name="reason" required maxlength="2000">{{ $keepInput?old('reason'):'' }}</textarea></label>@error('reason')<p role="alert">{{ $message }}</p>@enderror @endif
<p>Người chấm gần nhất: {{ $answer->grade['reviewer_id']??'Chưa chấm' }} · {{ $answer->grade['graded_at']??'' }}</p>
<button class="button button-primary">{{ $answer->grade?'Lưu điểm điều chỉnh':'Công bố điểm Estimated' }}</button></form>
<form method="post" action="{{ route('admin.grading.ai',$answer) }}">@csrf<button class="button button-quiet">Yêu cầu gợi ý AI</button></form>
@foreach($suggestions->get($answer->id,collect()) as $suggestion)<details><summary>AI #{{ $suggestion->id }} · {{ $suggestion->status }} · Estimated</summary><pre>{{ $suggestion->result??$suggestion->error??'Đang xử lý' }}</pre><p>Reviewer kiểm tra gợi ý và công bố bằng form chấm bài.</p></details>@endforeach</article>
@empty<div class="panel">Chưa có bài tự luận cần chấm.</div>@endforelse{{ $answers->links() }}
