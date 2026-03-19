<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

class MetricsController extends Controller
{
    public function __invoke(CollectorRegistry $registry): \Illuminate\Http\Response
    {
        $renderer = new RenderTextFormat();
        $result = $renderer->render($registry->getMetricFamilySamples());

        return response($result, 200, [
            'Content-Type' => RenderTextFormat::MIME_TYPE,
        ]);
    }
}