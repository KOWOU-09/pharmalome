<?php

namespace App\Http\Controllers;

use App\Services\PublicStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicStatsController extends Controller
{
    public function __invoke(Request $request, PublicStatsService $publicStats): JsonResponse
    {
        $stats = $publicStats->get();
        $etag = hash('sha256', json_encode($stats, JSON_THROW_ON_ERROR));
        $response = response()->json($stats)
            ->setEtag($etag)
            ->setPublic()
            ->setMaxAge(30);

        $response->isNotModified($request);

        return $response;
    }
}
