<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => ['required', Rule::in(['ios', 'android', 'web'])],
            'token' => ['required', 'string', 'max:512'],
        ]);

        $device = Device::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'token' => $data['token'],
            ],
            [
                'platform' => $data['platform'],
                'last_seen_at' => now(),
            ],
        );

        if ($request->user()->app_installed_at === null) {
            $request->user()->forceFill([
                'app_installed_at' => now(),
                'last_app_seen_at' => now(),
            ])->save();
        } else {
            $request->user()->forceFill(['last_app_seen_at' => now()])->save();
        }

        return response()->json([
            'ok' => true,
            'app_installed' => true,
            'device_id' => $device->id,
        ]);
    }
}
