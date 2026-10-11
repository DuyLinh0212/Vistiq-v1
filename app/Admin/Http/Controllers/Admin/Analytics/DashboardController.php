<?php

namespace VistiqAdmin\Http\Controllers\Admin\Analytics;

use VistiqAdmin\Http\Controllers\Admin\AdminPageController;
use VistiqCore\Services\Analytics\AnalyticsService;

class DashboardController extends AdminPageController
{
    public function index(AnalyticsService $analytics): mixed
    {
        return $this->page('Tổng quan quản trị', 'pages.admin.analytics.dashboard.workspace', ['metrics' => $analytics->dashboard()]);
    }

    public function analytics(AnalyticsService $analytics): mixed
    {
        return $this->index($analytics);
    }

}
