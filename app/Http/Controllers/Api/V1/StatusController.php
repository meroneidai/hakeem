<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ConnectionProbe;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    public function __invoke(ConnectionProbe $probe): JsonResponse
    {
        $snapshot = $probe->snapshot();

        return response()->json([
            'ok' => true,
            'locale' => app()->getLocale(),
            'integrations' => collect($snapshot)
                ->map(fn (array $row) => [
                    'ready' => $row['ready'],
                    'detail' => $row['detail'],
                ])
                ->all(),
        ]);
    }
}
