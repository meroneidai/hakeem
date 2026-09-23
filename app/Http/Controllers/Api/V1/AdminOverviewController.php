<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Services\MarketplaceInsights;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Data plane for a future admin Hermes agent. No LLM — structured snapshot only.
 */
class AdminOverviewController extends Controller
{
    public function __invoke(Request $request, MarketplaceInsights $insights): JsonResponse
    {
        abort_unless($request->user()?->isInternalStaff(), 403);
        abort_unless($request->user()->can(Permission::ViewAnalytics->value), 403);

        return response()->json([
            'agent' => [
                'status' => 'planned',
                'note' => 'Admin Hermes will consume this snapshot. No generative model is attached yet.',
            ],
            'insights' => $insights->snapshot(),
        ]);
    }
}
