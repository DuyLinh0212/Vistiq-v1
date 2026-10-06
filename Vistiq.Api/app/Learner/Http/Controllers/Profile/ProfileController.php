<?php

namespace VistiqLearner\Http\Controllers\Profile;

use Illuminate\View\View;
use VistiqLearner\Http\Controllers\StaticPageController;

class ProfileController extends StaticPageController
{
    private const SCREENS = [
        'history' => ['title' => 'Lịch sử học tập', 'frame' => 'U23', 'view' => 'pages.profile.history', 'section' => 'profile', 'description' => 'Theo dõi hành trình học tập và các bài đã làm.'],
        'profile' => ['title' => 'Hồ sơ cá nhân', 'frame' => 'U37', 'view' => 'pages.profile.show', 'section' => 'profile', 'description' => 'Quản lý thông tin và theo dõi tiến độ học tập.'],
    ];

    public function show(string $screen): View
    {
        return $this->renderKnownScreen($screen, self::SCREENS);
    }
}
