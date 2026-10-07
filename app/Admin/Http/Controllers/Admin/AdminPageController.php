<?php

namespace VistiqAdmin\Http\Controllers\Admin;

use Illuminate\View\View;
use VistiqAdmin\Http\Controllers\StaticPageController;

abstract class AdminPageController extends StaticPageController
{
    protected function page(string $title, string $view, array $data = []): View
    {
        $view = [
            'pages.admin.workspace.stimuli' => 'pages.admin.stimuli.index',
        ][$view] ?? $view;

        return $this->renderScreen(
            request()->route()?->getName() ?? 'admin',
            [
                'title' => $title,
                'frame' => '',
                'view' => $view,
                'section' => 'admin',
                'description' => '',
            ],
        )->with($data);
    }
}
