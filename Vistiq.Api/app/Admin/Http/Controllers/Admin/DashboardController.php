<?php

namespace VistiqAdmin\Http\Controllers\Admin;

use Illuminate\Http\Request;
use VistiqCore\Models\ExamTemplate;
use VistiqCore\Models\LearningItem;
use VistiqCore\Models\QuestionBankItem;
use VistiqCore\Services\AnalyticsService;

class DashboardController extends AdminPageController
{
    public function index(AnalyticsService $analytics): mixed
    {
        return $this->page('Tổng quan quản trị', 'pages.admin.workspace.dashboard', ['metrics' => $analytics->dashboard()]);
    }

    public function analytics(AnalyticsService $analytics): mixed
    {
        return $this->index($analytics);
    }

    public function search(Request $request): mixed
    {
        $term = trim($request->string('q')->toString());
        $results = [];
        if ($term !== '') {
            foreach (['knowledge' => LearningItem::class, 'exams' => ExamTemplate::class, 'questions' => QuestionBankItem::class] as $kind => $model) {
                $field = match ($kind) {
                    'knowledge' => 'canonical_text','exams' => 'title','questions' => 'prompt'
                };
                foreach ($model::where($field, 'like', '%'.$term.'%')->limit(10)->get() as $item) {
                    $results[] = ['label' => strip_tags($item->{$field}), 'url' => route('admin.'.$kind.'.edit', $item->id)];
                }
            }
        }

        return $this->page('Tìm kiếm toàn hệ thống', 'pages.admin.workspace.search', compact('results', 'term'));
    }
}
