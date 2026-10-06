@php
    $audioOnly = $libraryType === 'audio';
    $indexRoute = $audioOnly ? route('admin.audio.index') : route('admin.media.index');
    $formatFileSize = static function (int $size): string {
        if ($size >= 1048576) {
            return number_format($size / 1048576, 1).' MB';
        }

        return number_format(max(1, $size) / 1024, 0).' KB';
    };
@endphp

<section class="media-library">
    <header class="media-library-header">
        <div>
            <h1>{{ $audioOnly ? 'Kho Audio' : 'Thư viện Media' }}</h1>
            <p>{{ $assets->total() }} tài nguyên{{ $audioOnly ? ' audio' : '' }} trong hệ thống</p>
        </div>
        <form method="get" action="{{ $indexRoute }}" class="media-library-filters">
            <label class="media-library-search">
                <span class="sr-only">Tìm theo tên, tệp hoặc nguồn</span>
                <input type="search" name="q" value="{{ $search }}" placeholder="Tìm theo tên, tệp hoặc nguồn">
            </label>
            @unless($audioOnly)
                <label>
                    <span class="sr-only">Loại tài nguyên</span>
                    <select name="type" aria-label="Lọc theo loại tài nguyên">
                        <option value="">Tất cả loại</option>
                        <option value="audio" @selected($type === 'audio')>Audio</option>
                        <option value="image" @selected($type === 'image')>Hình ảnh</option>
                    </select>
                </label>
            @endunless
            <button class="button button-quiet" type="submit">Tìm kiếm</button>
            @if($search !== '' || (!$audioOnly && $type !== ''))
                <a class="media-library-reset" href="{{ $indexRoute }}">Xóa lọc</a>
            @endif
        </form>
    </header>

    <div class="media-library-list">
        @forelse($assets as $asset)
            @php
                $assetQuestions = $questionsByAssetId[$asset->id] ?? collect();
                $isAudio = str_starts_with((string) $asset->mime_type, 'audio/');
                $isImage = str_starts_with((string) $asset->mime_type, 'image/');
                $metadata = $asset->metadata ?? [];
                $speakerLabels = $metadata['speaker_labels'] ?? null;
                if (is_string($speakerLabels) && str_starts_with(trim($speakerLabels), '[')) {
                    $speakerLabels = json_decode($speakerLabels, true) ?: $speakerLabels;
                }
                $storageName = match ($asset->disk) {
                    'cloudinary' => 'Cloudinary',
                    'r2', 's3' => 'R2',
                    default => 'Kho nội bộ',
                };
            @endphp

            <article class="media-library-card">
                <div class="media-library-preview {{ $isAudio ? 'is-audio' : '' }}">
                    @if($isAudio)
                        <span class="media-library-kind">Audio</span>
                        <audio controls preload="none" src="{{ route('admin.media.show', $asset) }}" aria-label="Phát {{ $asset->title }}">
                            Trình duyệt không phát được audio.
                        </audio>
                    @elseif($isImage)
                        <img src="{{ route('admin.media.show', $asset) }}" alt="{{ $asset->title }}" loading="lazy">
                    @else
                        <span class="media-library-file" aria-hidden="true">{{ strtoupper(pathinfo($asset->path, PATHINFO_EXTENSION) ?: 'FILE') }}</span>
                    @endif
                </div>

                <div class="media-library-content">
                    <div class="media-library-title-row">
                        <div>
                            <h2>{{ $asset->title }}</h2>
                            <p class="media-library-subtitle">{{ $formatFileSize((int) $asset->size) }} <span aria-hidden="true">·</span> Lưu trên {{ $storageName }}</p>
                        </div>
                        <span class="media-library-kind">{{ $isAudio ? 'Audio' : ($isImage ? 'Hình ảnh' : 'Tệp') }}</span>
                    </div>

                    <section class="media-library-questions" aria-label="Câu hỏi sử dụng tài nguyên này">
                        <h3>Câu hỏi đang sử dụng</h3>
                        @forelse($assetQuestions as $question)
                            <a class="media-library-question" href="{{ route('admin.questions.edit', $question) }}">
                                <span class="media-library-question-code">Q-{{ str_pad((string) $question->id, 5, '0', STR_PAD_LEFT) }}</span>
                                <span>{{ $question->questionType?->name ?? 'Chưa phân loại' }}</span>
                                @if($question->examPart)
                                    <small>{{ $question->examPart->examType?->name }} · {{ $question->examPart->name }} · {{ $question->examPart->skill?->name }}</small>
                                @endif
                            </a>
                        @empty
                            <p class="media-library-unassigned">Chưa gắn với câu hỏi nào</p>
                        @endforelse
                    </section>

                    @if(filled($metadata['source'] ?? null) || filled($metadata['transcript'] ?? null) || filled($speakerLabels))
                        <dl class="media-library-metadata">
                            @if(filled($metadata['source'] ?? null))
                                <div><dt>Nguồn</dt><dd>{{ $metadata['source'] }}</dd></div>
                            @endif
                            @if(filled($speakerLabels))
                                <div><dt>Người nói</dt><dd>{{ is_array($speakerLabels) ? implode(', ', $speakerLabels) : $speakerLabels }}</dd></div>
                            @endif
                            @if(filled($metadata['transcript'] ?? null))
                                <div class="media-library-transcript"><dt>Bản chép lời</dt><dd>{{ $metadata['transcript'] }}</dd></div>
                            @endif
                        </dl>
                    @endif
                </div>
            </article>
        @empty
            <div class="panel media-library-empty">
                <h2>{{ $audioOnly ? 'Chưa có audio' : 'Chưa có media' }}</h2>
                <p>{{ $search !== '' || (!$audioOnly && $type !== '') ? 'Không tìm thấy tài nguyên phù hợp. Hãy thử đổi bộ lọc.' : 'Tài nguyên đã tải lên sẽ xuất hiện tại đây.' }}</p>
            </div>
        @endforelse
    </div>

    {{ $assets->links() }}
</section>
