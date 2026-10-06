<?php

namespace VistiqLearner\Http\Controllers\Site;

use Illuminate\View\View;
use VistiqLearner\Http\Controllers\StaticPageController;

class HomeController extends StaticPageController
{
    private const SCREENS = [
        'home' => ['title' => 'Trang chủ', 'frame' => 'U01', 'view' => 'pages.home.index', 'section' => 'home', 'description' => 'Tra cứu nhanh. Luyện đúng trọng tâm.', 'show_heading' => false],
    ];

    public function index(): View
    {
        return $this->show('home');
    }

    public function show(string $screen): View
    {
        return $this->renderKnownScreen($screen, self::SCREENS);
    }
}
