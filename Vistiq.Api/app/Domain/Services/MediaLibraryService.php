<?php

namespace VistiqCore\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use VistiqCore\Models\ContentRevision;
use VistiqCore\Models\MediaAsset;
use VistiqCore\Models\QuestionBankItem;

class MediaLibraryService
{
    /** @return array<int, Collection<int, QuestionBankItem>> */
    public function questionsForAssets(Collection $assets): array
    {
        if ($assets->isEmpty()) {
            return [];
        }

        $assetIds = $assets->modelKeys();
        $questionIds = $assets->flatMap(function (MediaAsset $asset): array {
            $questionId = filter_var($asset->metadata['question_id'] ?? null, FILTER_VALIDATE_INT);
            $path = $asset->metadata['storage_folder'] ?? $asset->path;

            if (preg_match('/(?:^|\/)Q-(\d{1,10})(?:\/|$)/i', (string) $path, $matches) === 1) {
                $questionId = (int) $matches[1];
            }

            return $questionId ? [(int) $questionId] : [];
        })->unique()->values()->all();
        $stimulusRevisions = ContentRevision::query()
            ->where('kind', 'stimulus')
            ->whereIn('payload->metadata->asset_id', $assetIds)
            ->get(['entity_id', 'payload']);
        $stimulusIds = $stimulusRevisions->pluck('entity_id')->map(fn ($id): int => (int) $id)->unique()->values()->all();
        $assetIdsByStimulusId = $stimulusRevisions->groupBy('entity_id')->map(
            fn (Collection $revisions): array => $revisions->pluck('payload.metadata.asset_id')->map(fn ($id): int => (int) $id)->unique()->values()->all(),
        );

        $questions = QuestionBankItem::query()
            ->with(['questionType', 'examPart.examType', 'examPart.skill', 'draftRevision', 'publishedRevision', 'stimulus'])
            ->where(function (Builder $query) use ($assetIds, $questionIds, $stimulusIds): void {
                if ($questionIds !== []) {
                    $query->whereIn('id', $questionIds);
                }

                $matchesRevision = function (Builder $revision) use ($assetIds): void {
                    $revision->whereIn('payload->metadata->audio_id', $assetIds)
                        ->orWhereIn('payload->metadata->image_id', $assetIds);
                };

                $query->orWhereHas('draftRevision', $matchesRevision)
                    ->orWhereHas('publishedRevision', $matchesRevision)
                    ->orWhereHas('stimulus', fn (Builder $stimulus): Builder => $stimulus->whereIn('metadata->asset_id', $assetIds));

                if ($stimulusIds !== []) {
                    $query->orWhereIn('learning_stimulus_id', $stimulusIds);
                }
            })
            ->get();

        $questionsByAssetId = [];

        foreach ($questions as $question) {
            $questionAssetIds = [];

            foreach ([$question->draftRevision, $question->publishedRevision] as $revision) {
                $metadata = $revision?->payload['metadata'] ?? [];
                $questionAssetIds[] = $metadata['audio_id'] ?? null;
                $questionAssetIds[] = $metadata['image_id'] ?? null;
            }

            $questionAssetIds[] = $question->stimulus?->metadata['asset_id'] ?? null;
            $questionAssetIds = [...$questionAssetIds, ...($assetIdsByStimulusId[(string) $question->learning_stimulus_id] ?? [])];

            foreach (array_intersect($assetIds, array_map('intval', array_filter($questionAssetIds))) as $assetId) {
                $questionsByAssetId[$assetId] ??= collect();
                $questionsByAssetId[$assetId]->push($question);
            }
        }

        foreach ($assets as $asset) {
            $questionId = filter_var($asset->metadata['question_id'] ?? null, FILTER_VALIDATE_INT);
            $path = $asset->metadata['storage_folder'] ?? $asset->path;

            if (preg_match('/(?:^|\/)Q-(\d{1,10})(?:\/|$)/i', (string) $path, $matches) === 1) {
                $questionId = (int) $matches[1];
            }

            $question = $questions->firstWhere('id', (int) $questionId);

            if ($question && ! ($questionsByAssetId[$asset->id] ?? collect())->contains('id', $question->id)) {
                $questionsByAssetId[$asset->id] ??= collect();
                $questionsByAssetId[$asset->id]->push($question);
            }
        }

        return $questionsByAssetId;
    }
}
