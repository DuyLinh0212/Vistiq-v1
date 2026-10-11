<?php

namespace VistiqLearner\Http\Controllers\Knowledge;

use Illuminate\View\View;
use VistiqLearner\Http\Controllers\StaticPageController;

class KnowledgeController extends StaticPageController
{
    private const SCREENS = [
        'knowledge' => ['title' => 'Kho kiến thức cá nhân', 'frame' => 'U06', 'view' => 'pages.learner.knowledge.saved.index', 'section' => 'knowledge', 'description' => 'Lưu lại những điều quan trọng trong quá trình học.', 'show_heading' => false],
        'knowledge-list' => ['title' => 'Danh sách mục đã lưu', 'frame' => 'U35', 'view' => 'pages.learner.knowledge.saved.list', 'section' => 'knowledge', 'description' => 'Tìm, phân loại và xem lại kiến thức của bạn.', 'show_heading' => false],
        'knowledge-review' => ['title' => 'Ôn tập kho kiến thức', 'frame' => 'U36', 'view' => 'pages.learner.knowledge.saved.review', 'section' => 'knowledge', 'description' => 'Ôn lại mục đã lưu theo tiến độ học tập.', 'show_heading' => false],
        'saved-detail' => ['title' => 'take into account', 'frame' => 'U24', 'view' => 'pages.learner.knowledge.saved.show', 'section' => 'knowledge', 'description' => 'Cân nhắc, tính đến khi đưa ra quyết định.', 'show_heading' => false],
    ];

    public function show(string $screen): View
    {
        return $this->renderKnownScreen($screen, self::SCREENS);
    }
}
