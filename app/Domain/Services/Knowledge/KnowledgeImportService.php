<?php

namespace VistiqCore\Services\Knowledge;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use VistiqCore\Models\LearningItem;
use VistiqCore\Services\Assessment\ContentApproval\ContentRevisionService;

class KnowledgeImportService
{
    public function __construct(
        private readonly ContentRevisionService $revisions,
        private readonly KnowledgeTaxonomyService $taxonomy,
    ) {}

    /**
     * Preview the contents of an uploaded file without persisting.
     *
     * @return array{headers: array<string>, rows: array<array<string, string>>, total: int, errors: array<array{row: int, message: string}>}
     */
    public function preview(UploadedFile $file, string $type): array
    {
        $rows = $this->parseFile($file);
        $headers = array_shift($rows) ?? [];
        $errors = [];
        $parsed = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // 1-indexed + header row
            $mapped = $this->mapRowToData($headers, $row, $type);
            if ($mapped === null) {
                $errors[] = ['row' => $rowNumber, 'message' => 'Dòng thiếu trường bắt buộc (english).'];
                continue;
            }
            $parsed[] = $mapped;
        }

        return [
            'headers' => $headers,
            'rows' => $parsed,
            'total' => count($parsed),
            'errors' => $errors,
        ];
    }

    /**
     * Import rows and create draft knowledge items.
     *
     * @param  array<array<string, mixed>>  $rows  Validated row data from preview
     * @return array{imported: int, skipped: int, errors: array<array{row: int, message: string}>}
     */
    public function import(array $rows, string $type, string $duplicatePolicy = 'skip'): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $typeId = DB::table('learning_item_types')->where('code', $type)->value('id');
        if (! $typeId) {
            return ['imported' => 0, 'skipped' => count($rows), 'errors' => [['row' => 0, 'message' => "Loại kiến thức '{$type}' không tồn tại."]]];
        }

        foreach ($rows as $index => $data) {
            $rowNumber = $index + 1;

            try {
                $english = trim($data['english'] ?? '');
                if ($english === '') {
                    $errors[] = ['row' => $rowNumber, 'message' => 'Trường english bắt buộc.'];
                    $skipped++;
                    continue;
                }

                // Duplicate check
                $existing = LearningItem::where('canonical_text', $english)
                    ->where('learning_item_type_id', $typeId)
                    ->first();

                if ($existing) {
                    if ($duplicatePolicy === 'skip') {
                        $skipped++;
                        continue;
                    }
                    // 'overwrite' policy: update the existing item's revision
                    $data['type'] = $type;
                    $this->revisions->save('knowledge', $existing, $data, $existing->revision_counter ?? 0);
                    $imported++;
                    continue;
                }

                DB::transaction(function () use ($data, $type, $typeId, &$imported): void {
                    $item = LearningItem::create([
                        'learning_item_type_id' => $typeId,
                        'canonical_text' => $data['english'],
                        'slug' => Str::slug($data['english']).'-'.Str::random(12),
                        'created_by' => auth()->id(),
                        'status' => 'draft',
                    ]);

                    $data['type'] = $type;
                    $this->revisions->save('knowledge', $item, $data, 0);
                    $imported++;
                });
            } catch (\Throwable $e) {
                $errors[] = ['row' => $rowNumber, 'message' => 'Lỗi: '.Str::limit($e->getMessage(), 120)];
                $skipped++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Export knowledge items to CSV format.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return string CSV content
     */
    public function exportCsv($query): string
    {
        $typeLabels = $this->taxonomy->types()->pluck('label', 'id');
        $levelLabels = $this->taxonomy->levels()->pluck('label', 'id');

        $headers = ['ID', 'English', 'Type', 'Level', 'Status', 'Updated'];
        $output = fopen('php://temp', 'r+');
        // BOM for Excel UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $headers);

        $query->chunk(500, function ($items) use ($output, $typeLabels, $levelLabels): void {
            foreach ($items as $item) {
                fputcsv($output, [
                    $item->id,
                    $this->sanitizeCsvField($item->canonical_text),
                    $this->sanitizeCsvField($typeLabels[$item->learning_item_type_id] ?? ''),
                    $this->sanitizeCsvField($levelLabels[$item->proficiency_level_id] ?? ''),
                    $item->status,
                    $item->updated_at?->toDateTimeString() ?? '',
                ]);
            }
        });

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Sanitize field to prevent CSV formula injection.
     */
    private function sanitizeCsvField(string $value): string
    {
        if (in_array(substr($value, 0, 1), ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * Parse an uploaded CSV/TSV file into rows.
     *
     * @return array<array<string>>
     */
    private function parseFile(UploadedFile $file): array
    {
        $content = file_get_contents($file->getPathname());

        // Detect and convert encoding
        $encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        // Remove BOM
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        $rows = [];
        $delimiter = str_contains($lines[0] ?? '', "\t") ? "\t" : ',';

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $rows[] = str_getcsv($line, $delimiter);
        }

        return $rows;
    }

    /**
     * Map a CSV row to knowledge item data using header names.
     *
     * @return array<string, mixed>|null
     */
    private function mapRowToData(array $headers, array $row, string $type): ?array
    {
        $map = [];
        foreach ($headers as $i => $header) {
            $key = Str::snake(trim(strtolower($header)));
            $map[$key] = trim($row[$i] ?? '');
        }

        if (empty($map['english'] ?? '')) {
            return null;
        }

        return array_filter([
            'english' => $map['english'] ?? '',
            'meaning_vi' => $map['meaning_vi'] ?? $map['vietnamese'] ?? $map['nghia'] ?? '',
            'definition_en' => $map['definition_en'] ?? $map['definition'] ?? '',
            'ipa' => $map['ipa'] ?? $map['pronunciation'] ?? '',
            'tags' => $map['tags'] ?? $map['tag'] ?? '',
            'summary' => $map['summary'] ?? '',
        ], fn ($v) => $v !== '');
    }
}
