<?php

namespace App\Http\Controllers;

use App\Services\EdgeTextToSpeech;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgentSpeechController extends Controller
{
    public function __invoke(Request $request, EdgeTextToSpeech $tts): BinaryFileResponse|JsonResponse|Response
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:600'],
        ]);

        $path = $tts->synthesize($validated['text']);

        if ($path === null) {
            return response()->json(['error' => 'tts_unavailable'], 502);
        }

        return response()->file($path, [
            'Content-Type' => 'audio/mpeg',
            'Cache-Control' => 'private, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
