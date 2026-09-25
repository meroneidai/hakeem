<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SiteAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AgentController extends Controller
{
    public function store(Request $request, SiteAgent $agent): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'conversation_id' => ['nullable', 'uuid'],
            'locale' => ['nullable', 'string', Rule::in(array_keys(config('hakeem.locales', ['ar' => true, 'en' => true])))],
            'mode' => ['nullable', Rule::in(['text', 'voice'])],
        ]);

        if (isset($data['locale']) && is_string($data['locale'])) {
            app()->setLocale($data['locale']);
        }

        $user = $request->user() ?? Auth::guard('sanctum')->user();

        return response()->json(
            $agent->reply(
                $data['message'],
                $user,
                $data['conversation_id'] ?? null,
                [
                    'mode' => $data['mode'] ?? 'text',
                    'locale' => app()->getLocale(),
                ],
            ),
            200,
            [],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
        );
    }
}
