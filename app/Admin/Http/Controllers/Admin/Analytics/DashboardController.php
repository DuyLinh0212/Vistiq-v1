<?php

namespace VistiqAdmin\Http\Controllers\Admin\Analytics;

use Illuminate\Http\Request;
use VistiqAdmin\Http\Controllers\Admin\AdminPageController;
use VistiqAdmin\Http\Requests\Admin\Analytics\AnalyticsFilterRequest;
use VistiqCore\Services\Analytics\AnalyticsService;
use VistiqCore\Services\Analytics\ExportService;

class DashboardController extends AdminPageController
{
    /**
     * Admin overview dashboard.
     */
    public function index(AnalyticsService $analytics): mixed
    {
        return $this->page('Tổng quan quản trị', 'pages.admin.analytics.dashboard.workspace', [
            'metrics' => $analytics->dashboard(),
        ]);
    }

    /**
     * Detailed analytics screen with filtering support.
     */
    public function analytics(AnalyticsFilterRequest $request, AnalyticsService $analytics): mixed
    {
        return $this->page('Phân tích và thống kê', 'pages.admin.analytics.dashboard.workspace', [
            'metrics' => $analytics->dashboard($request),
        ]);
    }

    /**
     * Export dashboard metrics to CSV matching active filters.
     */
    public function export(AnalyticsFilterRequest $request, AnalyticsService $analytics, ExportService $export): mixed
    {
        $metrics = $analytics->dashboard($request);
        $csv = $export->dashboardCsv($metrics);
        $filename = 'analytics-report-'.now()->format('YmdHis').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
