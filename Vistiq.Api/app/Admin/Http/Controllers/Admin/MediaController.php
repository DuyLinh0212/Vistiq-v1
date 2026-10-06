<?php

namespace VistiqAdmin\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use VistiqAdmin\Http\Requests\Admin\MediaUploadRequest;
use VistiqCore\Models\MediaAsset;
use VistiqCore\Services\AuditService;
use VistiqCore\Services\MediaLibraryService;
use VistiqCore\Services\MediaService;

class MediaController extends AdminPageController
{
    public function index(Request $request, MediaLibraryService $library): mixed
    {
        $search = $request->string('q')->toString();
        $type = $request->string('type')->toString();
        $query = MediaAsset::query()
            ->when($search !== '', fn (Builder $assets): Builder => $assets->where(function (Builder $assets) use ($search): void {
                $assets->where('title', 'like', '%'.$search.'%')
                    ->orWhere('path', 'like', '%'.$search.'%')
                    ->orWhere('metadata->source', 'like', '%'.$search.'%');
            }))
            ->when($type === 'audio', fn (Builder $assets): Builder => $assets->where('mime_type', 'like', 'audio/%'))
            ->when($type === 'image', fn (Builder $assets): Builder => $assets->where('mime_type', 'like', 'image/%'));
        $assets = $query->latest()->paginate(20)->withQueryString();

        return $this->page('Media', 'pages.admin.workspace.media', [
            'libraryType' => 'media',
            'assets' => $assets,
            'questionsByAssetId' => $library->questionsForAssets($assets->getCollection()),
            'search' => $search,
            'type' => $type,
        ]);
    }

    public function store(MediaUploadRequest $request, MediaService $media, AuditService $audit): mixed
    {
        $asset = $media->store($request->file('file'), auth()->id(), $request->validated('question_id'));
        $asset->update([
            'title' => $request->validated('title'),
            'metadata' => array_filter([
                ...$asset->metadata,
                'question_id' => $request->validated('question_id'),
                ...$request->safe()->only(['source', 'transcript', 'speaker_labels']),
            ], fn (mixed $value): bool => $value !== null && $value !== ''),
        ]);
        $audit->record('media.upload', 'media', $asset->id, [], ['disk' => $asset->disk, 'path' => $asset->path]);

        return $request->expectsJson() ? response()->json(['id' => $asset->id, 'url' => route('admin.media.show', $asset)]) : back()->with('status', 'Đã lưu media.');
    }

    public function show(MediaAsset $asset, MediaService $media): mixed
    {
        return redirect()->away($media->url($asset));
    }
}
