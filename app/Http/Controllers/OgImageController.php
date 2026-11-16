<?php

namespace App\Http\Controllers;

use App\Helpers\OgImageHelper;
use App\Models\Report;
use Illuminate\Http\Response;

class OgImageController extends Controller
{
    public function report(string $slug): Response
    {
        $report = Report::query()
            ->approved()
            ->where('slug', $slug)
            ->firstOrFail();

        $reportsCount = Report::query()
            ->approved()
            ->where('target_id', $report->target_id)
            ->count();

        $commentsCount = $report->comments()->count();

        return response(OgImageHelper::reportCard($report, $reportsCount, $commentsCount), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
