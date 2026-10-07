@php
    $isEditing = $question !== null;
    $selectedType = old('question_type_id', $payload['type'] ?? $questionTypes->firstWhere('code', 'multiple_choice')?->id);
    $metadata = old('metadata', $payload['metadata'] ?? []);
    $options = old('options', $payload['options'] ?? ['A' => '', 'B' => '', 'C' => '', 'D' => '']);
    $correctOptions = old('correct_options', $payload['correct_options'] ?? []);
    $selectedImageId = old('metadata.image_id', $metadata['image_id'] ?? '');
    $selectedAudioId = old('metadata.audio_id', $metadata['audio_id'] ?? '');
    $imageAssets = $assets->filter(fn ($asset) => str_starts_with($asset->mime_type, 'image/'));
    $audioAssets = $assets->filter(fn ($asset) => str_starts_with($asset->mime_type, 'audio/'));
    $selectedImage = $imageAssets->firstWhere('id', (int) $selectedImageId);
    $selectedAudio = $audioAssets->firstWhere('id', (int) $selectedAudioId);
@endphp
@if($isEditing)
    @include('shared.workflow-actions', ['kind' => 'question', 'entityId' => $question->id, 'revision' => $revision])
@endif

<form id="question-form" class="panel question-editor" method="post" action="{{ $isEditing ? route('admin.questions.update', $question) : route('admin.questions.store') }}" data-question-editor data-unsaved-form @if($isEditing && auth()->user()->hasPermission('content.edit'))data-autosave @endif>
    @csrf
    @if($isEditing) @method('PUT') @endif
    <input type="hidden" name="revision_counter" value="{{ $question?->revision_counter ?? 0 }}">

    <div class="question-editor-heading"><div><span class="eyebrow">THÔNG TIN CÂU HỎI</span><h2>{{ $isEditing ? 'Q-'.str_pad((string) $question->id, 5, '0', STR_PAD_LEFT) : 'Câu hỏi mới' }}</h2></div><p class="form-help">Chọn dạng câu hỏi trước; biểu mẫu sẽ chỉ hiện các trường phù hợp.</p></div>
    <div class="question-editor-grid">
        <label>Kỳ thi<select data-editor-exam>@foreach($parts->pluck('examType')->unique('id') as $examType)<option value="{{ $examType->id }}">{{ $examType->name }}</option>@endforeach</select></label>
        <label>Kỹ năng<select data-editor-skill>@foreach(['listening', 'reading', 'writing', 'speaking'] as $skill)<option>{{ $skill }}</option>@endforeach</select></label>
        <label>Part<select name="exam_part_id" data-editor-part required>@foreach($parts as $part)<option value="{{ $part->id }}" data-exam="{{ $part->exam_type_id }}" data-exam-code="{{ $part->examType->code }}" data-skill="{{ $part->skill?->code }}" @selected((string) old('exam_part_id', $payload['exam_part_id'] ?? $question?->exam_part_id) === (string) $part->id)>{{ $part->examType->name }} · {{ $part->name }}</option>@endforeach</select></label>
        <label>Dạng câu hỏi<select name="question_type_id" data-question-type required>@foreach($questionTypes as $type)<option value="{{ $type->id }}" data-code="{{ $type->code }}" @selected((string) $selectedType === (string) $type->id)>{{ $type->name }}</option>@endforeach</select></label>
        <label>Độ khó<select name="question_difficulty_id" required>@foreach($difficulties as $difficulty)<option value="{{ $difficulty->id }}" @selected(($question?->question_difficulty_id ?? $difficulties->firstWhere('code', 'medium')?->id) === $difficulty->id)>{{ $difficulty->name }}</option>@endforeach</select></label>
        <label>Tags<input name="tags" value="{{ old('tags', $tags) }}" placeholder="grammar, listening"></label>
        <label>Nhóm câu hỏi<select name="question_group_id"><option value="">Không nhóm</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected(($payload['group_id'] ?? null) === $group->id)>{{ $group->title }}</option>@endforeach</select></label>
        <label>Thứ tự trong nhóm<input name="group_order" type="number" min="0" value="{{ $question?->group_order ?? 0 }}"></label>
    </div>

    <label class="question-editor-wide">Đoạn đọc / bài nghe dùng chung<select name="learning_stimulus_id" data-stimulus-select><option value="">Không liên kết</option>@foreach($stimuli as $stimulus)<option value="{{ $stimulus->id }}" data-kind="{{ $stimulus->stimulus_type }}" @selected((string) ($payload['stimulus_ids'][0] ?? $selectedStimulusId) === (string) $stimulus->id)>{{ $stimulus->stimulus_type === 'audio' ? 'Audio' : 'Passage' }} · {{ $stimulus->title }}</option>@endforeach</select><span class="form-help">Liên kết tài nguyên dùng chung cho một nhóm câu hỏi.</span></label>

    <section class="question-editor-section question-editor-wide"><div class="section-head"><div><h3>Nội dung</h3><p class="form-help">Có thể dán văn bản đã định dạng.</p></div></div><label>Câu hỏi<textarea name="prompt" data-rich-editor rows="5">{{ old('prompt', $payload['prompt'] ?? '') }}</textarea></label></section>

    <section class="question-editor-section question-editor-wide" data-type-family="choice matching"><div class="section-head"><div><h3>Phương án trả lời</h3><p class="form-help">Đánh dấu đáp án đúng. Dạng matching dùng cột nhãn để chọn phương án.</p></div></div>
        <div class="question-option-list">@foreach(range('A', 'J') as $optionKey)<div class="question-option-row" data-option-row @if(!array_key_exists($optionKey, $options) && !in_array($optionKey, ['A', 'B', 'C', 'D'], true))hidden @endif><span class="question-option-key">{{ $optionKey }}</span><input name="options_values[{{ $optionKey }}]" value="{{ $options[$optionKey] ?? '' }}" placeholder="Nội dung phương án {{ $optionKey }}"><label class="question-correct-option" data-choice-answer><input type="checkbox" name="correct_options[]" value="{{ $optionKey }}" @checked(in_array($optionKey, $correctOptions, true))><span>Đúng</span></label></div>@endforeach</div>
        <button class="button button-quiet" type="button" data-add-question-option>Thêm phương án</button>
    </section>

    <label class="question-editor-wide" data-type-family="text">Đáp án chấp nhận<textarea name="accepted_answers_text" rows="3" placeholder="Mỗi dòng một đáp án">{{ implode("\n", old('accepted_answers', $payload['accepted_answers'] ?? [])) }}</textarea></label>
    <label class="question-editor-wide" data-type-family="matching">Các cặp matching<textarea name="answer_map_text" rows="4" placeholder="1 | A&#10;2 | C">@foreach(($metadata['answer_map'] ?? []) as $row => $answer){{ $row }} | {{ $answer }}{{ "\n" }}@endforeach</textarea><span class="form-help">Mỗi dòng: nhãn câu hỏi | mã phương án.</span></label>
    <label class="question-editor-wide" data-type-family="matching">Nhãn đoạn / nhóm<textarea name="metadata[labels_text]" rows="3" placeholder="A | Hướng dẫn thanh toán&#10;B | Thời gian giao hàng">@foreach(($metadata['labels'] ?? []) as $key => $label){{ $key }} | {{ $label }}{{ "\n" }}@endforeach</textarea></label>
    <label class="question-editor-wide" data-type-family="text">Các ô trống<textarea name="blanks_text" rows="4" placeholder="Nhãn ô | đáp án 1; đáp án 2">@foreach(($metadata['blanks'] ?? []) as $blank){{ ($blank['label'] ?? '') }} | {{ implode('; ', $blank['accepted_answers'] ?? []) }}{{ "\n" }}@endforeach</textarea><span class="form-help">Dùng khi một câu có nhiều ô cần điền.</span></label>

    <section class="question-editor-section question-editor-wide"><div class="section-head"><div><h3>Tài nguyên và giới hạn trả lời</h3><p class="form-help">Ảnh và audio lưu riêng; CSV có thể import trước, rồi gắn tài nguyên ở đây.</p></div></div>
        <div class="question-editor-grid">
            <label>Ảnh<select name="metadata[image_id]" data-image-select><option value="">Chưa gắn ảnh</option>@foreach($imageAssets as $asset)<option value="{{ $asset->id }}" data-url="{{ route('admin.media.show', $asset) }}" @selected((string) $selectedImageId === (string) $asset->id)>{{ $asset->title }}</option>@endforeach</select></label>
            <label>Audio<select name="metadata[audio_id]" data-audio-select><option value="">Chưa gắn audio</option>@foreach($audioAssets as $asset)<option value="{{ $asset->id }}" data-url="{{ route('admin.media.show', $asset) }}" @selected((string) $selectedAudioId === (string) $asset->id)>{{ $asset->title }}</option>@endforeach</select></label>
            <label>Variant IELTS<select name="metadata[variant]"><option value="">Không áp dụng</option><option value="academic" @selected(($metadata['variant'] ?? '') === 'academic')>Academic</option><option value="general" @selected(($metadata['variant'] ?? '') === 'general')>General Training</option></select></label>
            <label>Số từ tối đa<input type="number" min="0" max="100" name="metadata[max_words]" value="{{ $metadata['max_words'] ?? '' }}"></label>
            <label>Số từ tối thiểu<input type="number" min="1" max="10000" name="metadata[min_words]" value="{{ $metadata['min_words'] ?? '' }}"></label>
            <label>Số tối đa<input type="number" min="0" max="20" name="metadata[max_numbers]" value="{{ $metadata['max_numbers'] ?? '' }}"></label>
        </div>
        <div data-type-family="essay speaking" class="question-editor-grid question-writing-fields">
            <label class="question-editor-wide">Cue card<textarea name="metadata[cue_card]" rows="3">{{ $metadata['cue_card'] ?? '' }}</textarea></label>
            <label class="question-editor-wide">Câu hỏi tiếp nối<textarea name="metadata[follow_ups_text]" rows="3" placeholder="Mỗi dòng một câu">{{ implode("\n", $metadata['follow_ups'] ?? []) }}</textarea></label>
            <label class="question-editor-wide">Bài mẫu<textarea name="metadata[model_answer]" rows="4">{{ $metadata['model_answer'] ?? '' }}</textarea></label>
        </div>
        <div class="question-media-previews">
            <figure data-image-preview @if(!$selectedImage)hidden @endif><img src="{{ $selectedImage ? route('admin.media.show', $selectedImage) : '' }}" alt="Ảnh đang gắn với câu hỏi"></figure>
            <div data-audio-preview @if(!$selectedAudio)hidden @endif><audio controls preload="none" src="{{ $selectedAudio ? route('admin.media.show', $selectedAudio) : '' }}">Trình duyệt không phát được audio.</audio></div>
        </div>
        @if($isEditing)
            <div class="question-media-upload" data-media-upload data-upload-url="{{ route('admin.media.store') }}" data-question-id="{{ $question->id }}">
                <label>Thêm ảnh hoặc audio<input type="file" accept="image/jpeg,image/png,image/webp,audio/mpeg,audio/wav,audio/mp4,audio/ogg" data-media-file></label>
                <button class="button button-quiet" type="button" data-upload-media>Upload tài nguyên cho Q-{{ str_pad((string) $question->id, 5, '0', STR_PAD_LEFT) }}</button>
                <span class="form-help" data-media-upload-status>Ảnh được lưu Cloudinary; audio được lưu R2 theo thư mục mã câu hỏi.</span>
            </div>
        @else
            <p class="form-help">Lưu nháp trước để nhận mã câu hỏi và upload tài nguyên vào đúng thư mục.</p>
        @endif
    </section>

    <label class="question-editor-wide" data-type-family="essay speaking">Rubric<textarea name="metadata[rubric_text]" rows="4" placeholder="task_response | Mô tả tiêu chí&#10;coherence | Mô tả tiêu chí&#10;lexical_resource | Mô tả tiêu chí&#10;grammar | Mô tả tiêu chí">@foreach(($metadata['rubric'] ?? []) as $criterion => $guidance){{ $criterion }} | {{ $guidance }}{{ "\n" }}@endforeach</textarea><span class="form-help">Điền hướng dẫn cho bốn tiêu chí phù hợp Writing hoặc Speaking trước khi duyệt.</span></label>
    <label class="question-editor-wide">Giải thích<textarea name="explanation" data-rich-editor rows="4">{{ old('explanation', $payload['explanation'] ?? '') }}</textarea></label>
    @can('content.edit')<button class="button button-primary" type="submit">{{ $isEditing ? 'Lưu thay đổi' : 'Tạo câu hỏi' }}</button>@endcan
    <span data-autosave-status role="status"></span>
</form>
