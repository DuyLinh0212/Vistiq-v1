<?php

namespace VistiqLearner\Http\Controllers\Grammar;

use Illuminate\View\View;
use VistiqLearner\Http\Controllers\StaticPageController;

class GrammarController extends StaticPageController
{
    private const SCREENS = [
        'grammar' => ['title' => 'Thư viện ngữ pháp', 'frame' => 'U04', 'view' => 'pages.grammar.index', 'section' => 'grammar', 'description' => 'Học ngữ pháp theo chủ điểm, cấp độ và lộ trình.', 'show_heading' => false],
        'grammar-present-perfect' => ['title' => 'Present Perfect', 'frame' => 'U05', 'view' => 'pages.grammar.show', 'section' => 'grammar', 'description' => 'Cấu trúc, cách dùng và ví dụ dễ nhớ.', 'show_heading' => false],
    ];

    public function show(string $screen): View
    {
        return $this->renderKnownScreen($screen, self::SCREENS);
    }
}
